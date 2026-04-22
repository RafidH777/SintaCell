<?php
$pageTitle = 'Kelola Stok - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['pengelola_stok','pemilik']);

$filter = $_GET['filter'] ?? 'semua';
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = "1=1";
if ($search) $where .= " AND b.nama LIKE '%$search%'";
if ($filter==='rendah') $where .= " AND b.stok < b.stok_minimal";
if ($filter==='aman')   $where .= " AND b.stok >= b.stok_minimal";

$barangList  = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE $where ORDER BY b.stok ASC");
$stokRendah  = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok < stok_minimal")->fetch_assoc()['c'];
$stokAman    = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok >= stok_minimal")->fetch_assoc()['c'];
$totalBarang = $stokRendah + $stokAman;

include 'header.php';
?>

<div class="page-title d-flex justify-between align-center">
    <div><h1>🏷️ Kelola Stok</h1><p>Pantau dan kelola ketersediaan stok barang</p></div>
</div>

<?php if($stokRendah>0): ?>
<div class="alert alert-warning mb-2">
    ⚠️ <strong><?= $stokRendah ?> Item Stok Minimal!</strong> Segera lakukan restock.
</div>
<?php endif; ?>

<div class="stats-grid mb-2" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card"><div class="stat-icon blue">📦</div><div class="stat-info"><div class="stat-label">Total Barang</div><div class="stat-value"><?= $totalBarang ?></div></div></div>
    <div class="stat-card"><div class="stat-icon red">⚠️</div><div class="stat-info"><div class="stat-label">Stok Rendah</div><div class="stat-value text-danger"><?= $stokRendah ?></div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><div class="stat-label">Stok Aman</div><div class="stat-value text-success"><?= $stokAman ?></div></div></div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
            <div class="search-wrapper" style="flex:1;">
                <input type="text" name="q" style="width:100%;padding:8px 12px 8px 34px;border:1px solid var(--gray-medium);border-radius:6px;font-size:13px;"
                       placeholder="Cari barang..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <div style="display:flex;gap:6px;">
                <a href="?filter=semua"  class="filter-btn <?= $filter==='semua'  ?'active-gray':'' ?>">Semua</a>
                <a href="?filter=rendah" class="filter-btn <?= $filter==='rendah' ?'active-red':'' ?>">🔴 Rendah</a>
                <a href="?filter=aman"   class="filter-btn <?= $filter==='aman'   ?'active-green':'' ?>">✅ Aman</a>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Stok Saat Ini</th><th>Stok Minimal</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while($b=$barangList->fetch_assoc()):
                $isRendah = $b['stok'] < $b['stok_minimal'];
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($b['kode']) ?></td>
                <td class="fw-bold"><?= htmlspecialchars($b['nama']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($b['kategori']??'-') ?></span></td>
                <td><span class="fw-bold <?= $isRendah?'text-danger':'text-success' ?>"><?= $b['stok'] ?></span></td>
                <td><?= $b['stok_minimal'] ?></td>
                <td><?= $isRendah?'<span class="badge badge-danger">RENDAH</span>':'<span class="badge badge-success">AMAN</span>' ?></td>
                <td><button class="btn btn-sm btn-danger" onclick="openUpdateStok(<?= $b['id'] ?>,'<?= addslashes($b['nama']) ?>',<?= $b['stok'] ?>,<?= $b['stok_minimal'] ?>)">📦 Restock</button></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Update Stok -->
<div class="modal-overlay" id="modalUpdateStok">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div><div class="modal-title">📦 Update Stok</div><div class="modal-subtitle">Tambah stok barang</div></div>
            <button class="modal-close" onclick="closeModal('modalUpdateStok')">✕</button>
        </div>
        <div class="modal-body">
            <div id="stokInfoCard" style="background:#e8edff;border-radius:8px;padding:14px;margin-bottom:16px;">
                <div id="stokNama" class="fw-bold text-primary" style="font-size:15px;margin-bottom:8px;"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div><div class="small text-muted">Stok Saat Ini</div><div class="fw-bold" id="stokSaatIni"></div></div>
                    <div><div class="small text-muted">Stok Minimal</div><div class="fw-bold text-danger" id="stokMinimal"></div></div>
                </div>
            </div>
            <input type="hidden" id="updateBarangId">
            <div class="form-group">
                <label class="form-label required">Kuantitas Tambah</label>
                <input type="number" class="form-control" id="updateQty" placeholder="Jumlah yang ditambah" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Alasan / Catatan</label>
                <textarea class="form-control" id="updateCatatan" rows="2" placeholder="Contoh: Restock dari supplier..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalUpdateStok')">Batal</button>
            <button class="btn btn-primary" onclick="simpanUpdateStok()">💾 Simpan Update</button>
        </div>
    </div>
</div>

<script>
function openUpdateStok(id, nama, stok, minimal){
    document.getElementById('updateBarangId').value=id;
    document.getElementById('stokNama').textContent=nama;
    document.getElementById('stokSaatIni').textContent=stok;
    document.getElementById('stokMinimal').textContent=minimal;
    document.getElementById('updateQty').value='';
    document.getElementById('updateCatatan').value='';
    openModal('modalUpdateStok');
}
async function simpanUpdateStok(){
    const id=document.getElementById('updateBarangId').value;
    const qty=parseInt(document.getElementById('updateQty').value);
    const catatan=document.getElementById('updateCatatan').value;
    if(!qty||qty<1){ showToast('Masukkan jumlah yang valid!','error'); return; }
    const res=await fetch('../../backend/api/stok.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update',barang_id:id,qty,catatan})});
    const result=await res.json();
    if(result.success){ showToast('Stok berhasil diupdate!'); closeModal('modalUpdateStok'); setTimeout(()=>location.reload(),800); }
    else showToast(result.message||'Gagal','error');
}
</script>
<?php include 'footer.php'; ?>
