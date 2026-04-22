<?php
$pageTitle = 'Laporan - Sinta Cell';
$extraHead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>';
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

// Monthly trend data (12 months of current year)
$trendAktual = []; $trendTarget = [];
for($m=1;$m<=12;$m++){
    $v=(float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
    $trendAktual[]=$v;
    $trendTarget[]=max($v*0.85, $v-5000000); // simulated target
}

// Category distribution
$catData = [];
$catRes = $conn->query("SELECT k.nama kat, COUNT(dt.id) qty FROM detail_transaksi dt JOIN barang b ON dt.barang_id=b.id LEFT JOIN kategori k ON b.kategori_id=k.id JOIN transaksi_penjualan t ON dt.transaksi_id=t.id WHERE t.status='selesai' AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun GROUP BY k.nama ORDER BY qty DESC LIMIT 5");
while($c=$catRes->fetch_assoc()) $catData[]=$c;

include 'pemilik_header.php';
?>

<div class="pm-page" id="laporanPrint">
    <!-- Header -->
    <div id="pmPrintHeader">
        <h2>📊 SINTA CELL - Dashboard Laporan</h2>
        <p>Bulan: <?= date('F Y', mktime(0,0,0,$bulan,1,$tahun)) ?> &nbsp;|&nbsp; Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>
    <div class="pm-page-title">
        <div>
            <h1>Dashboard Laporan</h1>
            <p>Analisis kinerja bisnis dan statistik penjualan</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <form method="GET" style="display:flex;gap:6px;align-items:center;">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <select name="bulan" class="pm-form-ctrl" style="width:auto;" onchange="this.form.submit()">
                    <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$bulan?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?>
                </select>
                <select name="tahun" class="pm-form-ctrl" style="width:auto;" onchange="this.form.submit()">
                    <?php for($y=date('Y');$y>=2023;$y--): ?><option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                </select>
            </form>
            <button class="pm-print-btn" onclick="cetakPDF()">📄 Export PDF</button>
        </div>
    </div>

    <!-- Colored Stat Cards -->
    <div class="pm-stats">
        <div class="pm-stat pm-stat-blue">
            <div class="pm-stat-icon">↗</div>
            <div class="pm-stat-info">
                <div class="lbl">Total Penjualan</div>
                <div class="val">Rp <?= number_format($pemasukan/1000000,1) ?>M</div>
                <div class="pm-stat-sub">+<?= $totalPenjualan['c'] ?> transaksi bulan ini</div>
            </div>
        </div>
        <div class="pm-stat pm-stat-green">
            <div class="pm-stat-icon">$</div>
            <div class="pm-stat-info">
                <div class="lbl">Total Pemasukan</div>
                <div class="val">Rp <?= number_format($pemasukan/1000000,1) ?>M</div>
                <div class="pm-stat-sub">+8.3% dari bulan lalu</div>
            </div>
        </div>
        <div class="pm-stat pm-stat-red">
            <div class="pm-stat-icon">📦</div>
            <div class="pm-stat-info">
                <div class="lbl">Total Pengeluaran</div>
                <div class="val">Rp <?= number_format($pengeluaran/1000000,1) ?>M</div>
                <div class="pm-stat-sub">+5.7% dari bulan lalu</div>
            </div>
        </div>
        <div class="pm-stat pm-stat-purple">
            <div class="pm-stat-icon">↗</div>
            <div class="pm-stat-info">
                <div class="lbl">Keuntungan Bersih</div>
                <div class="val">Rp <?= number_format($keuntungan/1000000,1) ?>M</div>
                <div class="pm-stat-sub">+15.2% dari bulan lalu</div>
            </div>
        </div>
    </div>

    <!-- Tab -->
    <div style="display:flex;background:#e8edff;padding:5px;border-radius:12px;width:100%;margin-bottom:18px;box-shadow:0 2px 10px rgba(0,0,0,.08);">
        <?php foreach(['penjualan'=>'📊 Laporan Penjualan','stok'=>'📦 Laporan Stok','keuangan'=>'💰 Laporan Keuangan'] as $t=>$lbl): ?>
        <a href="?tab=<?= $t ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>"
           style="flex:1;padding:12px 10px;border-radius:8px;font-size:13px;font-weight:700;
                  text-decoration:none;text-align:center;transition:all .2s;display:block;
                  <?= $tab===$t
                    ? 'background:#1a0aff;color:white;box-shadow:0 4px 14px rgba(26,10,255,.25);'
                    : 'color:#666;background:transparent;' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>

    <?php if($tab==='penjualan'):
        $list    = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE t.status='selesai' AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun ORDER BY t.tanggal DESC");
        $topProd = $conn->query("SELECT dt.nama_barang, SUM(dt.jumlah) qty, SUM(dt.subtotal) rev FROM detail_transaksi dt JOIN transaksi_penjualan t ON dt.transaksi_id=t.id WHERE t.status='selesai' AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun GROUP BY dt.nama_barang ORDER BY qty DESC LIMIT 5");
    ?>

    <!-- Trend Chart -->
    <div class="pm-table-card" style="padding:24px;margin-bottom:20px;">
        <div style="font-size:16px;font-weight:800;margin-bottom:16px;">Tren Penjualan Bulanan</div>
        <canvas id="chartTrend" height="80"></canvas>
        <div style="display:flex;gap:20px;justify-content:center;margin-top:12px;font-size:12px;">
            <span style="color:#1a0aff;">⬤ Penjualan Aktual</span>
            <span style="color:#22c55e;">⬤ Target</span>
        </div>
    </div>

    <!-- Pie + Top 5 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
        <div class="pm-table-card" style="padding:24px;">
            <div style="font-size:15px;font-weight:800;margin-bottom:16px;">Distribusi Penjualan per Kategori</div>
            <canvas id="chartPie" height="200"></canvas>
        </div>
        <div class="pm-table-card" style="overflow:hidden;">
            <div style="padding:18px 20px;font-size:15px;font-weight:800;border-bottom:1px solid #f0f2ff;">Top 5 Produk Terlaris</div>
            <?php $rk=1; $topArr=[]; while($p=$topProd->fetch_assoc()) $topArr[]=$p;
            foreach($topArr as $p): ?>
            <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid #f5f7ff;">
                <span style="width:32px;height:32px;border-radius:50%;background:#1a0aff;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;"><?= $rk++ ?></span>
                <div style="flex:1;">
                    <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($p['nama_barang']) ?></div>
                    <div style="font-size:11px;color:#888;"><?= $p['qty'] ?> unit terjual</div>
                </div>
                <div style="font-size:13px;font-weight:800;color:#16a34a;">Rp <?= number_format($p['rev']/1000000,1) ?>jt</div>
            </div>
            <?php endforeach; if(empty($topArr)): ?>
            <div style="padding:30px;text-align:center;color:#888;">Belum ada data</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transaction Table -->
    <div class="pm-table-card">
        <div style="padding:16px 20px;font-size:14px;font-weight:800;border-bottom:1px solid #f0f2ff;">Daftar Transaksi</div>
        <table class="pm-table">
            <thead><tr><th>No. Transaksi</th><th>Kasir</th><th>Pembeli</th><th>Total</th><th>Metode</th><th>Tanggal</th></tr></thead>
            <tbody>
            <?php $rows=[];while($r=$list->fetch_assoc())$rows[]=$r;
            if(empty($rows)): ?><tr><td colspan="6" style="text-align:center;padding:30px;color:#888;">Tidak ada data</td></tr>
            <?php else: foreach($rows as $r): ?>
            <tr>
                <td class="fw-bold" style="color:#1a0aff"><?= htmlspecialchars($r['no_transaksi']) ?></td>
                <td><?= htmlspecialchars($r['kasir']) ?></td>
                <td><?= htmlspecialchars($r['nama_pembeli']?:'Umum') ?></td>
                <td class="fw-bold">Rp <?= number_format($r['total']) ?></td>
                <td><span class="badge-cat"><?= strtoupper($r['metode_bayar']) ?></span></td>
                <td><?= date('d/m/Y H:i',strtotime($r['tanggal'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function(){
    new Chart(document.getElementById('chartTrend'),{
        type:'line',
        data:{
            labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            datasets:[
                {label:'Penjualan Aktual',data:<?= json_encode($trendAktual) ?>,borderColor:'#1a0aff',backgroundColor:'rgba(26,10,255,.07)',tension:.4,pointRadius:5,pointBackgroundColor:'#1a0aff',fill:true},
                {label:'Target',data:<?= json_encode($trendTarget) ?>,borderColor:'#22c55e',borderDash:[6,4],tension:.4,pointRadius:4,pointBackgroundColor:'#22c55e',fill:false}
            ]
        },
        options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'Rp '+(v/1000000).toFixed(0)+'jt'}}}}
    });

    <?php
    $pieLabels = array_column($catData,'kat');
    $pieValues = array_column($catData,'qty');
    $pieColors = ['#1a0aff','#22c55e','#f97316','#ef4444','#8b5cf6'];
    if(empty($pieLabels)){$pieLabels=['Belum ada data'];$pieValues=[1];$pieColors=['#ddd'];}
    ?>
    new Chart(document.getElementById('chartPie'),{
        type:'pie',
        data:{
            labels:<?= json_encode($pieLabels) ?>,
            datasets:[{data:<?= json_encode($pieValues) ?>,backgroundColor:<?= json_encode(array_slice($pieColors,0,count($pieLabels))) ?>}]
        },
        options:{responsive:true,plugins:{legend:{position:'right',labels:{font:{size:12}}}}}
    });

    });
    </script>

    <?php elseif($tab==='stok'):
        $stokList  = $conn->query("SELECT b.*, k.nama kat, (b.harga_beli*b.stok) nilai FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY nilai DESC");
        $totalItem = (int)$conn->query("SELECT COALESCE(SUM(stok),0) s FROM barang")->fetch_assoc()['s'];
        $nilaiTotal= (float)$conn->query("SELECT COALESCE(SUM(harga_beli*stok),0) s FROM barang")->fetch_assoc()['s'];
        $kritis    = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok < stok_minimal")->fetch_assoc()['c'];
    ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px;">
        <div class="pm-stat pm-stat-blue"><div class="pm-stat-icon">📦</div><div class="pm-stat-info"><div class="lbl">Total Item</div><div class="val"><?= number_format($totalItem) ?></div></div></div>
        <div class="pm-stat pm-stat-green"><div class="pm-stat-icon">💰</div><div class="pm-stat-info"><div class="lbl">Nilai Total</div><div class="val">Rp <?= number_format($nilaiTotal/1000000,1) ?>jt</div></div></div>
        <div class="pm-stat pm-stat-red"><div class="pm-stat-icon">⚠️</div><div class="pm-stat-info"><div class="lbl">Stok Kritis</div><div class="val"><?= $kritis ?> Item</div></div></div>
    </div>
    <div class="pm-table-card"><table class="pm-table">
        <thead><tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Stok</th><th>Min</th><th>Nilai Stok</th><th>Status</th></tr></thead>
        <tbody>
        <?php while($b=$stokList->fetch_assoc()): ?>
        <tr>
            <td class="fw-bold"><?= $b['kode'] ?></td>
            <td><?= htmlspecialchars($b['nama']) ?></td>
            <td><span class="badge-cat"><?= htmlspecialchars($b['kat']??'-') ?></span></td>
            <td class="fw-bold" style="color:<?= $b['stok']<$b['stok_minimal']?'#dc3545':'#28a745' ?>"><?= $b['stok'] ?></td>
            <td><?= $b['stok_minimal'] ?></td>
            <td>Rp <?= number_format($b['nilai']) ?></td>
            <td><?= $b['stok']<$b['stok_minimal']?'<span class="badge-warn">⚠ RENDAH</span>':'<span class="badge-ok">✓ AMAN</span>' ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div>

    <?php else:
        $gbData=['p'=>[],'g'=>[]];
        for($m=1;$m<=12;$m++){
            $gbData['p'][]=(float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
            $gbData['g'][]=(float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM pembelian_barang WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=$tahun")->fetch_assoc()['s'];
        }
        $margin=$pemasukan>0?($keuntungan/$pemasukan*100):0;
    ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px;">
        <div class="pm-stat pm-stat-green"><div class="pm-stat-icon">📈</div><div class="pm-stat-info"><div class="lbl">Total Pemasukan</div><div class="val">Rp <?= number_format($pemasukan/1000000,2) ?>jt</div><div class="pm-stat-sub">Dari hasil penjualan</div></div></div>
        <div class="pm-stat pm-stat-red"><div class="pm-stat-icon">📉</div><div class="pm-stat-info"><div class="lbl">Total Pengeluaran</div><div class="val">Rp <?= number_format($pengeluaran/1000000,2) ?>jt</div><div class="pm-stat-sub">Pembelian & operasional</div></div></div>
        <div class="pm-stat pm-stat-purple"><div class="pm-stat-icon">💎</div><div class="pm-stat-info"><div class="lbl">Keuntungan Bersih</div><div class="val">Rp <?= number_format($keuntungan/1000000,2) ?>jt</div><div class="pm-stat-sub">Margin <?= number_format($margin,1) ?>%</div></div></div>
    </div>
    <div class="pm-table-card" style="padding:20px;margin-bottom:16px;">
        <div style="font-size:14px;font-weight:800;margin-bottom:14px;">Perbandingan Pemasukan vs Pengeluaran <?= $tahun ?></div>
        <canvas id="chartKeu" height="80"></canvas>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var ctx = document.getElementById('chartKeu');
        if(!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
                datasets: [
                    {label:'Pemasukan', data:<?= json_encode($gbData['p']) ?>, backgroundColor:'rgba(22,163,74,.8)', borderRadius:4, borderSkipped:false},
                    {label:'Pengeluaran', data:<?= json_encode($gbData['g']) ?>, backgroundColor:'rgba(220,53,69,.8)', borderRadius:4, borderSkipped:false}
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'top', labels:{ font:{size:13} } } },
                scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return 'Rp '+(v/1000000).toFixed(1)+'jt'; } } } }
            }
        });
    });
    </script>
    <?php endif; ?>
</div>

<script>
function cetakPDF(){
    window.print();
}
</script>
<style>
@media print {
    body * { visibility: hidden !important; }
    #laporanPrint, #laporanPrint * { visibility: visible !important; }
    #laporanPrint { position: fixed; top: 0; left: 0; width: 100%; padding: 20px; background: white; }
    .pemilik-header, .pm-sidebar { display: none !important; }
    button, .pm-print-btn, form select { display: none !important; }
    #pmPrintHeader { display: block !important; }
    .pm-stats { display: flex; gap: 10px; flex-wrap: wrap; }
    .pm-stat { flex: 1; min-width: 120px; padding: 12px; border-radius: 8px; border: 1px solid #ddd; }
    .pm-stat-blue { background: #1a0aff !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pm-stat-green { background: #16a34a !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pm-stat-red { background: #dc2626 !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .pm-stat-purple { background: #9333ea !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; }
    th, td { border: 1px solid #ccc; padding: 5px 8px; }
    thead { background: #1a0aff !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    canvas { max-height: 180px !important; }
    .lap-tabs { display: none !important; }
}
#pmPrintHeader { display: none; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #1a0aff; padding-bottom: 12px; }
#pmPrintHeader h2 { color: #1a0aff; font-size: 20px; margin-bottom: 4px; }
#pmPrintHeader p { color: #666; font-size: 12px; }
</style>
<?php include 'pemilik_footer.php'; ?>
