<?php
$pageTitle = 'Dashboard - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole('pemilik');

$totalBarang     = (int)$conn->query("SELECT COUNT(*) c FROM barang")->fetch_assoc()['c'];
$stokRendah      = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok < stok_minimal")->fetch_assoc()['c'];
$totalTrxBulan   = (int)$conn->query("SELECT COUNT(*) c FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch_assoc()['c'];
$totalPemasukan  = (float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch_assoc()['s'];
$totalPengeluaran= (float)$conn->query("SELECT COALESCE(SUM(total),0) s FROM pembelian_barang WHERE status='selesai' AND MONTH(tanggal)=MONTH(NOW()) AND YEAR(tanggal)=YEAR(NOW())")->fetch_assoc()['s'];
$keuntungan      = $totalPemasukan - $totalPengeluaran;

$recentTrx   = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id ORDER BY t.created_at DESC LIMIT 8");
$stokKritis  = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE b.stok < b.stok_minimal ORDER BY b.stok ASC LIMIT 5");

$grafikData = [];
for ($m=1; $m<=12; $m++) {
    $r = $conn->query("SELECT COALESCE(SUM(total),0) s FROM transaksi_penjualan WHERE status='selesai' AND MONTH(tanggal)=$m AND YEAR(tanggal)=YEAR(NOW())");
    $grafikData[] = (float)$r->fetch_assoc()['s'];
}

include 'header.php';
?>

<div class="page-title d-flex justify-between align-center">
    <div>
        <h1>📊 Dashboard</h1>
        <p>Selamat datang, <?= htmlspecialchars($user['nama']) ?>! Ringkasan bulan <?= date('F Y') ?>.</p>
    </div>
    <div style="font-size:13px;color:var(--gray);">📅 <?= date('d F Y') ?></div>
</div>

<div class="stats-grid">
    <div class="stat-card blue-solid">
        <div class="stat-icon" style="background:rgba(255,255,255,.2);">💳</div>
        <div class="stat-info"><div class="stat-label">Total Transaksi Bulan Ini</div><div class="stat-value"><?= $totalTrxBulan ?> Trx</div></div>
    </div>
    <div class="stat-card green-solid">
        <div class="stat-icon" style="background:rgba(255,255,255,.2);">📈</div>
        <div class="stat-info"><div class="stat-label">Total Pemasukan</div><div class="stat-value">Rp <?= number_format($totalPemasukan/1000,1) ?>K</div></div>
    </div>
    <div class="stat-card red-solid">
        <div class="stat-icon" style="background:rgba(255,255,255,.2);">📉</div>
        <div class="stat-info"><div class="stat-label">Total Pengeluaran</div><div class="stat-value">Rp <?= number_format($totalPengeluaran/1000,1) ?>K</div></div>
    </div>
    <div class="stat-card purple-solid">
        <div class="stat-icon" style="background:rgba(255,255,255,.2);">💎</div>
        <div class="stat-info"><div class="stat-label">Keuntungan Bersih</div><div class="stat-value">Rp <?= number_format($keuntungan/1000,1) ?>K</div></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-header"><strong>📊 Tren Penjualan Bulanan <?= date('Y') ?></strong></div>
        <div class="card-body"><canvas id="chartPenjualan" height="180"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header" style="background:linear-gradient(135deg,#dc3545,#fd7e14);color:white;border-radius:10px 10px 0 0;">
            <strong>⚠️ <?= $stokRendah ?> Item Stok Kritis!</strong>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr><th>Barang</th><th>Stok</th><th>Min</th></tr></thead>
                <tbody>
                <?php while($row=$stokKritis->fetch_assoc()): ?>
                <tr>
                    <td><div class="fw-bold"><?= htmlspecialchars($row['nama']) ?></div><div class="small text-muted"><?= htmlspecialchars($row['kategori']??'-') ?></div></td>
                    <td class="text-danger fw-bold"><?= $row['stok'] ?></td>
                    <td><?= $row['stok_minimal'] ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:12px 16px;"><a href="stok.php" class="btn btn-danger btn-sm">Kelola Stok →</a></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong>🧾 Transaksi Terbaru</strong>
        <a href="riwayat.php" class="btn btn-sm btn-outline">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>No. Transaksi</th><th>Kasir</th><th>Pembeli</th><th>Total</th><th>Metode</th><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($trx=$recentTrx->fetch_assoc()): ?>
            <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($trx['no_transaksi']) ?></td>
                <td><?= htmlspecialchars($trx['kasir']) ?></td>
                <td><?= htmlspecialchars($trx['nama_pembeli']?:'Umum') ?></td>
                <td>Rp <?= number_format($trx['total']) ?></td>
                <td><span class="badge badge-primary"><?= strtoupper($trx['metode_bayar']) ?></span></td>
                <td><?= date('d/m/Y H:i',strtotime($trx['tanggal'])) ?></td>
                <td><span class="badge badge-success"><?= ucfirst($trx['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
new Chart(document.getElementById('chartPenjualan'),{
    type:'line',
    data:{
        labels:['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'],
        datasets:[{
            label:'Penjualan (Rp)',
            data:<?= json_encode($grafikData) ?>,
            borderColor:'#ffffff', backgroundColor:'rgba(255,255,255,.15)',
            borderWidth:2, tension:.4, fill:true,
            pointBackgroundColor:'#ffffff', pointRadius:4
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false}},
        scales:{
            y:{beginAtZero:true, ticks:{color:'#333', callback:v=>'Rp '+(v/1000).toFixed(0)+'K'},grid:{color:'rgba(0,0,0,.05)'}},
            x:{ticks:{color:'#333'}}
        }
    }
});
</script>
<?php include 'footer.php'; ?>
