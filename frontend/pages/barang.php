<?php
$pageTitle = 'Kelola Data Barang - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['pemilik','pengelola_stok']);

$search     = sanitize($conn, $_GET['q'] ?? '');
$where      = $search ? "WHERE b.nama LIKE '%$search%' OR b.kode LIKE '%$search%'" : "";
$barangList = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id $where ORDER BY b.kode");
$kategoriList= $conn->query("SELECT * FROM kategori ORDER BY nama");

$totalProduk    = (int)$conn->query("SELECT COUNT(*) c FROM barang")->fetch_assoc()['c'];
$nilaiInventory = (float)$conn->query("SELECT COALESCE(SUM(harga_beli*stok),0) s FROM barang")->fetch_assoc()['s'];
$potensiRevenue = (float)$conn->query("SELECT COALESCE(SUM(harga_jual*stok),0) s FROM barang")->fetch_assoc()['s'];
$potensiProfit  = $potensiRevenue - $nilaiInventory;

$kategoriArr = [];
while ($k=$kategoriList->fetch_assoc()) $kategoriArr[]=$k;

include 'header.php';
?>

<div class="page-title d-flex justify-between align-center">
    <div><h1>📦 Kelola Data Barang</h1><p>Master data produk dan inventory management</p></div>
    <?php if($_SESSION['jabatan']==='pemilik'): ?>
    <button class="btn btn-primary" onclick="openModal('modalTambah')">+ Tambah Barang Baru</button>
    <?php endif; ?>
</div>

<div class="stats-grid mb-2">
    <div class="stat-card"><div class="stat-icon blue">📦</div><div class="stat-info"><div class="stat-label">Total Produk</div><div class="stat-value text-primary"><?= $totalProduk ?></div></div></div>
    <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><div class="stat-label">Nilai Inventory</div><div class="stat-value text-success">Rp <?= number_format($nilaiInventory/1000000,1) ?>jt</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">📈</div><div class="stat-info"><div class="stat-label">Potensi Revenue</div><div class="stat-value">Rp <?= number_format($potensiRevenue/1000000,1) ?>jt</div></div></div>
    <div class="stat-card"><div class="stat-icon purple">💎</div><div class="stat-info"><div class="stat-label">Potensi Profit</div><div class="stat-value" style="color:var(--purple)">Rp <?= number_format($potensiProfit/1000000,1) ?>jt</div></div></div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:10px;">
            <div class="search-wrapper" style="flex:1;">
                <input type="text" name="q" style="width:100%;padding:8px 12px 8px 34px;border:1px solid var(--gray-medium);border-radius:6px;font-size:13px;"
                       placeholder="Cari berdasarkan nama atau kode..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Kode</th><th>Nama Barang</th><th>Kategori</th><th>Harga Beli</th><th>Harga Jual</th><th>Margin</th><th>Stok</th><th>Min Stok</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while($b=$barangList->fetch_assoc()):
                $margin = $b['harga_jual']>0 ? (($b['harga_jual']-$b['harga_beli'])/$b['harga_jual']*100) : 0;
            ?>
            <tr>
                <td class="fw-bold"><?= htmlspecialchars($b['kode']) ?></td>
                <td class="fw-bold"><?= htmlspecialchars($b['nama']) ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($b['kategori']??'-') ?></span></td>
                <td>Rp <?= number_format($b['harga_beli']) ?></td>
                <td>Rp <?= number_format($b['harga_jual']) ?></td>
                <td class="text-success"><?= number_format($margin,1) ?>%</td>
                <td class="fw-bold <?= $b['stok']<$b['stok_minimal']?'text-danger':'' ?>"><?= $b['stok'] ?></td>
                <td><?= $b['stok_minimal'] ?></td>
                <td>
                    <?php if($_SESSION['jabatan']==='pemilik'): ?>
                    <button class="btn btn-sm btn-primary" onclick='editBarang(<?= json_encode($b) ?>)'>✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="hapusBarang(<?= $b['id'] ?>,'<?= addslashes($b['nama']) ?>')">🗑️</button>
                    <?php else: ?><span class="text-muted small">-</span><?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <div class="modal-header"><div><div class="modal-title">📦 Tambah Barang Baru</div><div class="modal-subtitle">Lengkapi data barang</div></div><button class="modal-close" onclick="closeModal('modalTambah')">✕</button></div>
        <form onsubmit="submitBarang(event,'tambah')">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Kode Barang</label><input type="text" class="form-control" name="kode" placeholder="BRG001" required></div>
                <div class="form-group"><label class="form-label required">Kategori</label>
                    <select class="form-control" name="kategori_id" required><option value="">Pilih...</option>
                    <?php foreach($kategoriArr as $k): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="form-group"><label class="form-label required">Nama Barang</label><input type="text" class="form-control" name="nama" placeholder="Nama barang" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Harga Beli</label><input type="number" class="form-control" name="harga_beli" min="0" required></div>
                <div class="form-group"><label class="form-label required">Harga Jual</label><input type="number" class="form-control" name="harga_jual" min="0" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Stok Awal</label><input type="number" class="form-control" name="stok" min="0" required></div>
                <div class="form-group"><label class="form-label required">Stok Minimal</label><input type="number" class="form-control" name="stok_minimal" min="0" value="10" required></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button><button type="submit" class="btn btn-primary">+ Tambah Barang</button></div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-header"><div><div class="modal-title">✏️ Edit Barang</div><div class="modal-subtitle">Ubah data barang</div></div><button class="modal-close" onclick="closeModal('modalEdit')">✕</button></div>
        <form onsubmit="submitBarang(event,'edit')">
        <input type="hidden" name="id" id="editId">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Kode Barang</label><input type="text" class="form-control" name="kode" id="editKode" required></div>
                <div class="form-group"><label class="form-label required">Kategori</label>
                    <select class="form-control" name="kategori_id" id="editKategori" required><option value="">Pilih...</option>
                    <?php foreach($kategoriArr as $k): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option><?php endforeach; ?>
                    </select></div>
            </div>
            <div class="form-group"><label class="form-label required">Nama Barang</label><input type="text" class="form-control" name="nama" id="editNama" required></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Harga Beli</label><input type="number" class="form-control" name="harga_beli" id="editHargaBeli" min="0" required oninput="calcMargin()"></div>
                <div class="form-group"><label class="form-label required">Harga Jual</label><input type="number" class="form-control" name="harga_jual" id="editHargaJual" min="0" required oninput="calcMargin()"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Stok</label><input type="number" class="form-control" name="stok" id="editStok" min="0" required></div>
                <div class="form-group"><label class="form-label required">Stok Minimal</label><input type="number" class="form-control" name="stok_minimal" id="editStokMinimal" min="0" required></div>
            </div>
            <div id="marginCard" style="background:#e8f5e9;border-radius:8px;padding:10px;"><div class="small text-muted">Margin Keuntungan</div><div class="fw-bold text-success" id="marginLabel" style="font-size:16px;">-</div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button><button type="submit" class="btn btn-primary">💾 Simpan</button></div>
        </form>
    </div>
</div>

<script>
function editBarang(b){
    document.getElementById('editId').value=b.id;
    document.getElementById('editKode').value=b.kode;
    document.getElementById('editNama').value=b.nama;
    document.getElementById('editKategori').value=b.kategori_id;
    document.getElementById('editHargaBeli').value=b.harga_beli;
    document.getElementById('editHargaJual').value=b.harga_jual;
    document.getElementById('editStok').value=b.stok;
    document.getElementById('editStokMinimal').value=b.stok_minimal;
    calcMargin(); openModal('modalEdit');
}
function calcMargin(){
    const beli=parseFloat(document.getElementById('editHargaBeli').value)||0;
    const jual=parseFloat(document.getElementById('editHargaJual').value)||0;
    const m=jual>0?((jual-beli)/jual*100).toFixed(1):0;
    document.getElementById('marginLabel').textContent=`Rp ${(jual-beli).toLocaleString('id-ID')},- (${m}%)`;
}
async function submitBarang(e, mode){
    e.preventDefault();
    const data=Object.fromEntries(new FormData(e.target));
    data.action=mode;
    const res=await fetch('../../backend/api/barang.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const r=await res.json();
    if(r.success){ showToast(r.message); setTimeout(()=>location.reload(),800); }
    else showToast(r.message||'Gagal','error');
}
async function hapusBarang(id,nama){
    if(!confirm(`Hapus barang "${nama}"?`)) return;
    const res=await fetch('../../backend/api/barang.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'hapus',id})});
    const r=await res.json();
    if(r.success){ showToast('Barang dihapus'); setTimeout(()=>location.reload(),800); }
    else showToast(r.message||'Gagal','error');
}
</script>
<?php include 'footer.php'; ?>
