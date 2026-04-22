<?php
$pageTitle = 'Riwayat Transaksi - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();

$search  = sanitize($conn, $_GET['q'] ?? '');
$tanggal = sanitize($conn, $_GET['tanggal'] ?? '');
$where   = "1=1";
if ($search)  $where .= " AND (t.no_transaksi LIKE '%$search%' OR t.nama_pembeli LIKE '%$search%')";
if ($tanggal) $where .= " AND DATE(t.tanggal)='$tanggal'";

$list = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE $where ORDER BY t.tanggal DESC LIMIT 100");

include 'header.php';
?>
<div class="page-title d-flex justify-between align-center">
    <div><h1>📜 Riwayat Transaksi</h1><p>Semua riwayat transaksi penjualan</p></div>
</div>
<div class="card mb-2"><div class="card-body">
    <form method="GET" style="display:flex;gap:10px;">
        <div class="search-wrapper" style="flex:1;"><input type="text" name="q" style="width:100%;padding:8px 12px 8px 34px;border:1px solid var(--gray-medium);border-radius:6px;font-size:13px;" placeholder="Cari no. transaksi atau pembeli..." value="<?= htmlspecialchars($search) ?>"></div>
        <input type="date" name="tanggal" class="form-control" style="width:auto;" value="<?= htmlspecialchars($tanggal) ?>">
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>
</div></div>
<div class="card">
    <div class="table-responsive"><table>
        <thead><tr><th>No. Transaksi</th><th>Kasir</th><th>Pembeli</th><th>Total</th><th>Bayar</th><th>Kembalian</th><th>Metode</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php while($trx=$list->fetch_assoc()): ?>
        <tr>
            <td class="fw-bold text-primary"><?= htmlspecialchars($trx['no_transaksi']) ?></td>
            <td><?= htmlspecialchars($trx['kasir']) ?></td>
            <td><?= htmlspecialchars($trx['nama_pembeli']?:'Umum') ?></td>
            <td class="fw-bold">Rp <?= number_format($trx['total']) ?></td>
            <td>Rp <?= number_format($trx['bayar']) ?></td>
            <td>Rp <?= number_format($trx['kembalian']) ?></td>
            <td><span class="badge badge-primary"><?= strtoupper($trx['metode_bayar']) ?></span></td>
            <td><?= date('d/m/Y H:i',strtotime($trx['tanggal'])) ?></td>
            <td><span class="badge <?= $trx['status']==='selesai'?'badge-success':'badge-danger' ?>"><?= ucfirst($trx['status']) ?></span></td>
            <td><button class="btn btn-sm btn-outline" onclick="lihatDetail(<?= $trx['id'] ?>)">Detail</button></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div>
</div>

<div class="modal-overlay" id="modalDetail">
    <div class="modal"><div class="modal-header"><div><div class="modal-title">🧾 Detail Transaksi</div></div><button class="modal-close" onclick="closeModal('modalDetail')">✕</button></div>
    <div class="modal-body" id="detailContent">Loading...</div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('modalDetail')">Tutup</button></div></div>
</div>

<script>
async function lihatDetail(id){
    openModal('modalDetail');
    document.getElementById('detailContent').innerHTML='Loading...';
    const res=await fetch('../../backend/api/riwayat.php?id='+id);
    const r=await res.json();
    if(r.success){
        const t=r.data.transaksi; const items=r.data.items;
        let html=items.map(i=>`<tr><td>${i.nama_barang}</td><td>Rp ${parseInt(i.harga_satuan).toLocaleString('id-ID')}</td><td>${i.jumlah}</td><td>Rp ${parseInt(i.subtotal).toLocaleString('id-ID')}</td></tr>`).join('');
        document.getElementById('detailContent').innerHTML=`<p><strong>${t.no_transaksi}</strong> &bull; ${t.tanggal} &bull; Kasir: ${t.kasir}</p>
        <table><thead><tr><th>Barang</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody>${html}</tbody></table>
        <div style="margin-top:12px;text-align:right;">
            <div>Subtotal: <strong>Rp ${parseInt(t.subtotal).toLocaleString('id-ID')}</strong></div>
            <div>Diskon: <strong>Rp ${parseInt(t.diskon).toLocaleString('id-ID')}</strong></div>
            <div style="font-size:16px;">Total: <strong>Rp ${parseInt(t.total).toLocaleString('id-ID')}</strong></div>
        </div>`;
    }
}
</script>
<?php include 'footer.php'; ?>
