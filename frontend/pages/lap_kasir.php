<?php
$pageTitle = 'Laporan - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik']);

$userId  = (int)$_SESSION['user_id'];
$jabatan = $_SESSION['jabatan'];

$periode = $_GET['periode'] ?? 'hari';
$tanggal = sanitize($conn, $_GET['tanggal'] ?? date('Y-m-d'));
$bulan   = intval($_GET['bulan'] ?? date('m'));
$tahun   = intval($_GET['tahun'] ?? date('Y'));

$whereUser = $jabatan === 'kasir' ? "AND t.kasir_id=$userId" : "";

// Tentukan range waktu
switch ($periode) {
    case 'hari':
        $whereWaktu = "AND DATE(t.tanggal)='$tanggal'";
        $label = "Hari: ".date('d F Y', strtotime($tanggal));
        break;
    case 'bulan':
        $whereWaktu = "AND MONTH(t.tanggal)=$bulan AND YEAR(t.tanggal)=$tahun";
        $label = "Bulan: ".date('F Y', mktime(0,0,0,$bulan,1,$tahun));
        break;
    case 'tahun':
        $whereWaktu = "AND YEAR(t.tanggal)=$tahun";
        $label = "Tahun: $tahun";
        break;
    default:
        $whereWaktu = "AND DATE(t.tanggal)='$tanggal'";
        $label = "Hari ini";
}

$where = "t.status='selesai' $whereUser $whereWaktu";

$stats = $conn->query("SELECT COUNT(*) total_trx, COALESCE(SUM(total),0) total_omzet, COALESCE(SUM(diskon),0) total_diskon FROM transaksi_penjualan t WHERE $where")->fetch_assoc();
$list  = $conn->query("SELECT t.*, u.nama kasir_nama FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE $where ORDER BY t.tanggal DESC LIMIT 200");

// Top produk
$topProduk = $conn->query("SELECT dt.nama_barang, SUM(dt.jumlah) qty, SUM(dt.subtotal) rev
    FROM detail_transaksi dt JOIN transaksi_penjualan t ON dt.transaksi_id=t.id
    WHERE $where GROUP BY dt.nama_barang ORDER BY qty DESC LIMIT 5");

include 'kasir_header.php';
?>

<div class="lap-page" id="printArea">
    <div id="printHeader">
        <h2>📊 SINTA CELL - Laporan Penjualan</h2>
        <p>Periode: <?= $label ?> &nbsp;|&nbsp; Kasir: <?= htmlspecialchars($user['nama']) ?> &nbsp;|&nbsp; Dicetak: <?= date('d/m/Y H:i') ?></p>
    </div>
    <!-- Filter -->
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;">
        <button class="pm-print-btn pm-print-btn-gray" onclick="window.print()">🖨️ Print</button>
        <button class="pm-print-btn" onclick="cetakPDF()">📄 Export PDF</button>
    </div>
    <div class="lap-filter">
        <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="?periode=hari&tanggal=<?= date('Y-m-d') ?>" class="lap-filter-btn <?= $periode==='hari'?'active':'' ?>">Hari Ini</a>
            <a href="?periode=bulan&bulan=<?= date('m') ?>&tahun=<?= date('Y') ?>" class="lap-filter-btn <?= $periode==='bulan'?'active':'' ?>">Bulan Ini</a>
            <a href="?periode=tahun&tahun=<?= date('Y') ?>" class="lap-filter-btn <?= $periode==='tahun'?'active':'' ?>">Tahun Ini</a>

            <?php if ($periode==='hari'): ?>
            <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>"
                   style="padding:8px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;font-family:Arial,sans-serif;"
                   onchange="this.form.submit()">
            <input type="hidden" name="periode" value="hari">
            <?php elseif ($periode==='bulan'): ?>
            <select name="bulan" style="padding:8px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;" onchange="this.form.submit()">
                <?php for($m=1;$m<=12;$m++): ?><option value="<?= $m ?>" <?= $m==$bulan?'selected':'' ?>><?= date('F',mktime(0,0,0,$m,1)) ?></option><?php endfor; ?>
            </select>
            <select name="tahun" style="padding:8px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;" onchange="this.form.submit()">
                <?php for($y=date('Y');$y>=2023;$y--): ?><option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
            </select>
            <input type="hidden" name="periode" value="bulan">
            <?php else: ?>
            <select name="tahun" style="padding:8px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;" onchange="this.form.submit()">
                <?php for($y=date('Y');$y>=2023;$y--): ?><option value="<?= $y ?>" <?= $y==$tahun?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
            </select>
            <input type="hidden" name="periode" value="tahun">
            <?php endif; ?>
        </form>
        <span style="font-size:13px;color:var(--gray);margin-left:8px;">📅 <?= $label ?></span>
    </div>

    <!-- Stats -->
    <div class="lap-stat-grid">
        <div class="lap-stat">
            <div class="lap-stat-label">Total Transaksi</div>
            <div class="lap-stat-value text-blue"><?= $stats['total_trx'] ?></div>
            <div style="font-size:12px;color:var(--gray);">transaksi selesai</div>
        </div>
        <div class="lap-stat">
            <div class="lap-stat-label">Total Omzet</div>
            <div class="lap-stat-value" style="color:var(--success);">Rp <?= number_format($stats['total_omzet']) ?></div>
            <div style="font-size:12px;color:var(--gray);">total pendapatan</div>
        </div>
        <div class="lap-stat">
            <div class="lap-stat-label">Total Diskon</div>
            <div class="lap-stat-value" style="color:var(--danger);">Rp <?= number_format($stats['total_diskon']) ?></div>
            <div style="font-size:12px;color:var(--gray);">total diskon diberikan</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;">
        <!-- Tabel Transaksi -->
        <div class="order-table-wrap">
            <table class="order-table">
                <thead>
                    <tr><th>No. Transaksi</th><th>Pembeli</th><th>Total</th><th>Metode</th><th>Waktu</th></tr>
                </thead>
                <tbody>
                <?php
                $rows = [];
                while($r=$list->fetch_assoc()) $rows[]=$r;
                if(empty($rows)):
                ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--gray);">Tidak ada transaksi pada periode ini</td></tr>
                <?php else: foreach($rows as $r): ?>
                <tr>
                    <td class="fw-bold text-blue"><?= htmlspecialchars($r['no_transaksi']) ?></td>
                    <td><?= htmlspecialchars($r['nama_pembeli']?:'Umum') ?></td>
                    <td class="fw-bold">Rp <?= number_format($r['total']) ?></td>
                    <td><span style="background:#e8edff;color:var(--blue);padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;"><?= strtoupper($r['metode_bayar']) ?></span></td>
                    <td><?= date('d/m H:i', strtotime($r['tanggal'])) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Produk -->
        <div style="background:white;border-radius:16px;box-shadow:var(--shadow);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-size:15px;font-weight:800;">🏆 Top Produk</div>
            <?php $rank=1; while($p=$topProduk->fetch_assoc()): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border);">
                <span style="font-size:18px;font-weight:800;color:var(--blue);min-width:22px;"><?= $rank++ ?></span>
                <div style="flex:1;">
                    <div class="fw-bold" style="font-size:13px;"><?= htmlspecialchars($p['nama_barang']) ?></div>
                    <div style="font-size:11px;color:var(--gray);"><?= $p['qty'] ?> unit terjual</div>
                </div>
                <div style="font-size:12px;font-weight:700;color:var(--success);">Rp <?= number_format($p['rev']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #printArea, #printArea * { visibility: visible !important; }
    #printArea { position: fixed; top: 0; left: 0; width: 100%; padding: 20px; background: white; }
    .topnav, .kasir-right, #printArea > div:first-child { display: none !important; }
    .lap-filter, .lap-filter-btn { display: none !important; }
    .pm-print-btn, .pm-print-btn-gray { display: none !important; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 10px; font-size: 11px; }
    thead { background: #1a0aff !important; color: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .lap-stat-grid { display: flex; gap: 10px; margin-bottom: 16px; }
    .lap-stat { border: 1px solid #ddd; border-radius: 8px; padding: 12px; flex: 1; }
    #printHeader { display: block !important; }
}
#printHeader { display: none; text-align:center; margin-bottom: 20px; border-bottom: 2px solid #1a0aff; padding-bottom: 12px; }
#printHeader h2 { color: #1a0aff; font-size: 20px; margin-bottom: 4px; }
#printHeader p { color: #666; font-size: 12px; }
</style>

<script>
function cetakPDF(){
    window.print();
}
</script>
<?php include 'kasir_footer.php'; ?>
