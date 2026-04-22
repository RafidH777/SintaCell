<?php
$pageTitle = 'Laporan - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole('pemilik');

$tab   = $_GET['tab']   ?? 'penjualan';
$bulan = intval($_GET['bulan'] ?? date('m'));
$tahun = intval($_GET['tahun'] ?? date('Y'));

$totalPenjualan  = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun")->fetch_assoc();
$pengeluaran     = (float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM pembelian_barang WHERE status='selesai' AND MONTH(tanggal)=$bulan AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
$pemasukan       = (float)$totalPenjualan['s'];
$keuntungan      = $pemasukan - $pengeluaran;

include 'header.php';
?>
<div class="page-title d-flex justify-between align-center">
    <div><h1>📈 Dashboard Laporan</h1><p>Analisis kinerja bisnis dan statistik penjualan</p></div>
    <div style="display:flex;gap:8px;align-items:center;">
        <form method="GET" style="display:flex;gap:6px;align-items:center;">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <select name="bulan" class="form-control" style="width:auto;">
                <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$bulan?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?>
            </select>
            <select name="tahun" class="form-control" style="width:auto;">
                <?php for($y=date('Y');$y>=2023;$y--): ?><option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
        <button class="btn btn-success btn-sm" onclick="window.print()">📄 Export PDF</button>
    </div>
</div>

<div class="stats-grid mb-2">
    <div class="stat-card blue-solid"><div class="stat-icon" style="background:rgba(255,255,255,.2);">💳</div><div class="stat-info"><div class="stat-label">Total Penjualan</div><div class="stat-value"><?= $totalPenjualan['c'] ?> Trx</div></div></div>
    <div class="stat-card green-solid"><div class="stat-icon" style="background:rgba(255,255,255,.2);">📈</div><div class="stat-info"><div class="stat-label">Total Pemasukan</div><div class="stat-value">Rp <?= number_format($pemasukan/1000000,2) ?>jt</div></div></div>
    <div class="stat-card red-solid"><div class="stat-icon" style="background:rgba(255,255,255,.2);">📉</div><div class="stat-info"><div class="stat-label">Total Pengeluaran</div><div class="stat-value">Rp <?= number_format($pengeluaran/1000000,2) ?>jt</div></div></div>
    <div class="stat-card purple-solid"><div class="stat-icon" style="background:rgba(255,255,255,.2);">💎</div><div class="stat-info"><div class="stat-label">Keuntungan Bersih</div><div class="stat-value">Rp <?= number_format($keuntungan/1000000,2) ?>jt</div></div></div>
</div>

<div style="display:flex;gap:4px;margin-bottom:16px;background:white;padding:6px;border-radius:10px;box-shadow:var(--shadow);width:fit-content;">
    <?php foreach(['penjualan'=>'💰 Penjualan','stok'=>'📦 Stok','keuangan'=>'💼 Keuangan'] as $t=>$lbl): ?>
    <a href="?tab=<?= $t ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>"
       class="btn btn-sm <?= $tab===$t?'btn-primary':'' ?>"
       style="<?= $tab!==$t?'background:transparent;color:#555;':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</div>

<?php
if ($tab==='penjualan'):
    $list    = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE t.status='selesai' AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun ORDER BY t.tanggal DESC");
    $topProd = $conn->query("SELECT dt.nama_barang, SUM(dt.jumlah) qty, SUM(dt.subtotal) rev FROM detail_transaksi dt JOIN transaksi_penjualan t ON dt.transaksi_id=t.id WHERE t.status='selesai' AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun GROUP BY dt.nama_barang ORDER BY qty DESC LIMIT 5");
    $harian  = []; $days = cal_days_in_month(CAL_GREGORIAN,$bulan,$tahun);
    for($d=1;$d<=$days;$d++){ $tgl="$tahun-".str_pad($bulan,2,'0',STR_PAD_LEFT)."-".str_pad($d,2,'0',STR_PAD_LEFT); $r=$conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE DATE(tanggal)='$tgl' AND status='selesai'"); $harian[]=(float)$r->fetch_assoc()['s']; }
?>
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px;">
    <div class="card"><div class="card-header"><strong>📊 Tren Penjualan Harian</strong></div><div class="card-body"><canvas id="chartH" height="120"></canvas></div></div>
    <div class="card">
        <div class="card-header"><strong>🏆 Top 5 Produk Terlaris</strong></div>
        <div class="card-body" style="padding:0;">
        <?php $r=1; while($p=$topProd->fetch_assoc()): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--gray-light);">
            <span style="font-size:16px;font-weight:bold;color:var(--primary);min-width:20px;"><?= $r++ ?></span>
            <div style="flex:1;"><div class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($p['nama_barang']) ?></div><div class="small text-muted"><?= $p['qty'] ?> unit</div></div>
            <div class="text-success fw-bold small">Rp <?= number_format($p['rev']) ?></div>
        </div>
        <?php endwhile; ?>
        </div>
    </div>
</div>
<div class="card"><div class="card-header"><strong>📋 Daftar Transaksi</strong></div>
<div class="table-responsive"><table><thead><tr><th>No. Transaksi</th><th>Kasir</th><th>Pembeli</th><th>Total</th><th>Metode</th><th>Tanggal</th></tr></thead><tbody>
<?php while($trx=$list->fetch_assoc()): ?>
<tr><td class="fw-bold text-primary"><?= htmlspecialchars($trx['no_transaksi']) ?></td><td><?= htmlspecialchars($trx['kasir']) ?></td><td><?= htmlspecialchars($trx['nama_pembeli']?:'Umum') ?></td><td>Rp <?= number_format($trx['total']) ?></td><td><span class="badge badge-primary"><?= strtoupper($trx['metode_bayar']) ?></span></td><td><?= date('d/m/Y H:i',strtotime($trx['tanggal'])) ?></td></tr>
<?php endwhile; ?>
</tbody></table></div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>new Chart(document.getElementById('chartH'),{type:'bar',data:{labels:Array.from({length:<?= $days ?>},(_,i)=>i+1),datasets:[{label:'Penjualan',data:<?= json_encode($harian) ?>,backgroundColor:'rgba(0,0,255,.7)',borderRadius:4}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+(v/1000).toFixed(0)+'K'}}}}});</script>

<?php elseif($tab==='stok'):
    $stokList  = $conn->query("SELECT b.*, k.nama kategori, (b.harga_beli*b.stok) nilai FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY nilai DESC");
    $totalItem = (int)$conn->query("SELECT COALESCE(SUM(stok),0) s FROM barang")->fetch_assoc()['s'];
    $nilaiTotal= (float)$conn->query("SELECT COALESCE(SUM(harga_beli*stok),0) s FROM barang")->fetch_assoc()['s'];
    $kritis    = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok < stok_minimal")->fetch_assoc()['c'];
?>
<div class="stats-grid mb-2" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card"><div class="stat-icon blue">📦</div><div class="stat-info"><div class="stat-label">Total Item Stok</div><div class="stat-value text-primary"><?= number_format($totalItem) ?></div></div></div>
    <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><div class="stat-label">Nilai Total Stok</div><div class="stat-value text-success">Rp <?= number_format($nilaiTotal/1000000,1) ?>jt</div></div></div>
    <div class="stat-card"><div class="stat-icon red">⚠️</div><div class="stat-info"><div class="stat-label">Stok Kritis</div><div class="stat-value text-danger"><?= $kritis ?> Item</div></div></div>
</div>
<div class="card"><div class="table-responsive"><table><thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Min</th><th>Nilai Stok</th><th>Status</th></tr></thead><tbody>
<?php while($b=$stokList->fetch_assoc()): ?>
<tr><td class="fw-bold"><?= $b['kode'] ?></td><td><?= htmlspecialchars($b['nama']) ?></td><td><span class="badge badge-primary"><?= htmlspecialchars($b['kategori']??'-') ?></span></td><td class="fw-bold <?= $b['stok']<$b['stok_minimal']?'text-danger':'text-success' ?>"><?= $b['stok'] ?></td><td><?= $b['stok_minimal'] ?></td><td>Rp <?= number_format($b['nilai']) ?></td><td><?= $b['stok']<$b['stok_minimal']?'<span class="badge badge-danger">RENDAH</span>':'<span class="badge badge-success">AMAN</span>' ?></td></tr>
<?php endwhile; ?>
</tbody></table></div></div>

<?php elseif($tab==='keuangan'):
    $gbData=['p'=>[],'g'=>[]];
    for($m=1;$m<=12;$m++){
        $gbData['p'][]=(float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
        $gbData['g'][]=(float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM pembelian_barang WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
    }
    $margin = $pemasukan>0?($keuntungan/$pemasukan*100):0;
?>
<div class="card mb-2"><div class="card-header"><strong>💼 Perbandingan Pemasukan vs Pengeluaran <?= $tahun ?></strong></div><div class="card-body"><canvas id="chartK" height="120"></canvas></div></div>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
    <div class="card"><div class="card-body text-center"><div class="small text-muted">Total Pemasukan</div><div class="fw-bold text-success" style="font-size:20px;">Rp <?= number_format($pemasukan/1000000,2) ?>jt</div><div class="small text-muted mt-1">Dari hasil penjualan</div></div></div>
    <div class="card"><div class="card-body text-center"><div class="small text-muted">Total Pengeluaran</div><div class="fw-bold text-danger" style="font-size:20px;">Rp <?= number_format($pengeluaran/1000000,2) ?>jt</div><div class="small text-muted mt-1">Pembelian & operasional</div></div></div>
    <div class="card"><div class="card-body text-center"><div class="small text-muted">Keuntungan Bersih</div><div class="fw-bold" style="font-size:20px;color:var(--purple);">Rp <?= number_format($keuntungan/1000000,2) ?>jt</div><div class="small text-muted mt-1">Margin <?= number_format($margin,1) ?>%</div></div></div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>new Chart(document.getElementById('chartK'),{type:'bar',data:{labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],datasets:[{label:'Pemasukan',data:<?= json_encode($gbData['p']) ?>,backgroundColor:'rgba(40,167,69,.7)',borderRadius:4},{label:'Pengeluaran',data:<?= json_encode($gbData['g']) ?>,backgroundColor:'rgba(220,53,69,.7)',borderRadius:4}]},options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+(v/1000000).toFixed(1)+'jt'}}}}});</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
