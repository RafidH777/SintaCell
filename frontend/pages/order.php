<?php
$pageTitle = 'Order - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik']);

$userId  = (int)$_SESSION['user_id'];
$jabatan = $_SESSION['jabatan'];
$search  = sanitize($conn, $_GET['q'] ?? '');
$tanggal = sanitize($conn, $_GET['tanggal'] ?? '');
$urutan  = sanitize($conn, $_GET['urut'] ?? 'terbaru');

// Kasir hanya lihat ordernya sendiri, pemilik lihat semua
$whereUser = $jabatan === 'kasir' ? "AND t.kasir_id=$userId" : "";
$where = "1=1 $whereUser";
if ($search)  $where .= " AND (t.no_transaksi LIKE '%$search%' OR t.nama_pembeli LIKE '%$search%')";
if ($tanggal) $where .= " AND DATE(t.tanggal)='$tanggal'";

$orderBy = $urutan === 'terlama' ? 't.tanggal ASC' : 't.tanggal DESC';

$list = $conn->query("SELECT t.*, u.nama kasir_nama,
    (SELECT COUNT(*) FROM detail_transaksi WHERE transaksi_id=t.id) jml_barang,
    (SELECT GROUP_CONCAT(nama_barang SEPARATOR ', ') FROM detail_transaksi WHERE transaksi_id=t.id) daftar_barang
    FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id
    WHERE $where ORDER BY $orderBy LIMIT 100");

include 'kasir_header.php';
?>

<div class="order-page">
    <div class="order-filter-row">
        <form method="GET" style="display:flex;gap:10px;align-items:center;flex:1;">
            <select name="urut" class="filter-select-btn" onchange="this.form.submit()">
                <option value="terbaru" <?= $urutan==='terbaru'?'selected':'' ?>>Terbaru</option>
                <option value="terlama" <?= $urutan==='terlama'?'selected':'' ?>>Terlama</option>
            </select>
            <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>"
                   style="padding:8px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;font-family:Arial,sans-serif;"
                   onchange="this.form.submit()">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Cari pesanan..."
                   style="padding:8px 16px;border:1.5px solid var(--border);border-radius:20px;font-size:13px;font-family:Arial,sans-serif;outline:none;">
            <button type="submit" style="padding:8px 20px;background:var(--blue);color:white;border:none;border-radius:20px;font-size:13px;font-weight:700;cursor:pointer;">Cari</button>
        </form>
    </div>

    <div class="order-table-wrap">
        <table class="order-table">
            <thead>
                <tr>
                    <th>Id_Pesanan</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th>Jumlah Barang</th>
                    <th>Total Bayar</th>
                    <th>Daftar Barang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $rows = [];
            while($r = $list->fetch_assoc()) $rows[] = $r;
            if (empty($rows)):
            ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--gray);">Belum ada data pesanan</td></tr>
            <?php else: foreach($rows as $r): ?>
            <tr>
                <td class="fw-bold text-blue"><?= htmlspecialchars($r['no_transaksi']) ?></td>
                <td><?= htmlspecialchars($r['kasir_nama']) ?></td>
                <td><?= htmlspecialchars($r['nama_pembeli'] ?: 'Umum') ?></td>
                <td><?= $r['jml_barang'] ?></td>
                <td class="fw-bold"><?= number_format($r['total']) ?>,-</td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(substr($r['daftar_barang'] ?? '-', 0, 40)) ?></td>
                <td>
                    <button class="detail-btn" onclick="lihatDetail(<?= $r['id'] ?>)">Detail Pesanan</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal-overlay" id="modalDetail">
    <div class="modal-box">
        <div class="modal-hd">
            <div><div class="modal-hd-title">🧾 Detail Pesanan</div></div>
            <button class="modal-hd-close" onclick="closeModal('modalDetail')">✕</button>
        </div>
        <div class="modal-bd" id="detailContent">
            <div style="text-align:center;padding:20px;color:var(--gray);">Memuat...</div>
        </div>
        <div class="modal-ft">
            <button class="modal-btn-sec" onclick="closeModal('modalDetail')">Tutup</button>
        </div>
    </div>
</div>

<script>
async function lihatDetail(id) {
    openModal('modalDetail');
    document.getElementById('detailContent').innerHTML='<div style="text-align:center;padding:20px;color:var(--gray);">Memuat...</div>';
    const res    = await fetch('../../backend/api/riwayat.php?id='+id);
    const result = await res.json();
    if (result.success) {
        const t=result.data.transaksi; const items=result.data.items;
        let itemsHtml=items.map(i=>`
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                <span>${i.nama_barang} <span style="color:var(--gray);">×${i.jumlah}</span></span>
                <strong>Rp ${parseInt(i.subtotal).toLocaleString('id-ID')}</strong>
            </div>`).join('');
        document.getElementById('detailContent').innerHTML=`
            <div style="margin-bottom:12px;">
                <div class="fw-bold text-blue" style="font-size:15px;">${t.no_transaksi}</div>
                <div style="font-size:12px;color:var(--gray);">${t.tanggal} &bull; Kasir: ${t.kasir}</div>
                <div style="font-size:12px;color:var(--gray);">Pembeli: ${t.nama_pembeli||'Umum'}</div>
            </div>
            <div>${itemsHtml}</div>
            <div style="margin-top:14px;padding:12px;background:#f0f2ff;border-radius:12px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;"><span>Subtotal</span><span>Rp ${parseInt(t.subtotal).toLocaleString('id-ID')}</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;"><span>Diskon</span><span>Rp ${parseInt(t.diskon).toLocaleString('id-ID')}</span></div>
                <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;"><span>Total</span><span style="color:var(--blue)">Rp ${parseInt(t.total).toLocaleString('id-ID')}</span></div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-top:4px;"><span>Metode</span><span><strong>${t.metode_bayar.toUpperCase()}</strong></span></div>
            </div>`;
    }
}
</script>

<?php include 'kasir_footer.php'; ?>
