<?php
$pageTitle = 'Kelola Data Barang - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole('pemilik');

$search      = sanitize($conn, $_GET['q'] ?? '');
$where       = $search ? "WHERE b.nama LIKE '%$search%' OR b.kode LIKE '%$search%'" : "";
$barangList  = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id $where ORDER BY b.kode");
$kategoriList= $conn->query("SELECT * FROM kategori ORDER BY nama");

$totalProduk    = (int)$conn->query("SELECT COUNT(*) c FROM barang")->fetch_assoc()['c'];
$nilaiInventory = (float)$conn->query("SELECT COALESCE(SUM(harga_beli*stok),0) s FROM barang")->fetch_assoc()['s'];
$potensiRevenue = (float)$conn->query("SELECT COALESCE(SUM(harga_jual*stok),0) s FROM barang")->fetch_assoc()['s'];
$potensiProfit  = $potensiRevenue - $nilaiInventory;

$kategoriArr = [];
while ($k=$kategoriList->fetch_assoc()) $kategoriArr[]=$k;

include 'pemilik_header.php';
?>

<div class="pm-page">
    <div class="pm-page-title">
        <div>
            <h1>Kelola Data Barang</h1>
            <p>Master data produk dan inventory management</p>
        </div>
        <button class="pm-add-btn" onclick="openModal('modalTambah')">+ Tambah Barang Baru</button>
    </div>

    <!-- Stats -->
    <div class="pm-stats">
        <div class="pm-stat pm-stat-blue"><div class="pm-stat-icon">📦</div><div class="pm-stat-info"><div class="lbl">Total Produk</div><div class="val"><?= $totalProduk ?></div></div></div>
        <div class="pm-stat pm-stat-green"><div class="pm-stat-icon">💰</div><div class="pm-stat-info"><div class="lbl">Nilai Inventory</div><div class="val">Rp <?= number_format($nilaiInventory/1000000,1) ?>jt</div></div></div>
        <div class="pm-stat" style="background:linear-gradient(135deg,#ea580c,#f97316)!important"><div class="pm-stat-icon" style="color:white">📈</div><div class="pm-stat-info"><div class="lbl" style="color:rgba(255,255,255,.85)!important">Potensi Revenue</div><div class="val" style="color:white!important">Rp <?= number_format($potensiRevenue/1000000,1) ?>jt</div></div></div>
        <div class="pm-stat pm-stat-purple"><div class="pm-stat-icon">💎</div><div class="pm-stat-info"><div class="lbl">Potensi Profit</div><div class="val">Rp <?= number_format($potensiProfit/1000000,1) ?>jt</div></div></div>
    </div>

    <!-- Search -->
    <form method="GET" style="margin-bottom:0;">
        <div class="pm-search-box">
            <span style="color:#aaa;">🔍</span>
            <input type="text" name="q" placeholder="Cari barang berdasarkan nama, kode, atau kategori..."
                   value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()">
        </div>
    </form>

    <!-- Table -->
    <div class="pm-table-card">
        <table class="pm-table">
            <thead>
                <tr>
                    <th>Gambar</th><th>Kode</th><th>Nama Barang</th><th>Kategori</th>
                    <th>Harga Beli</th><th>Harga Jual</th><th>Margin</th>
                    <th>Stok</th><th>Min. Stok</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($b=$barangList->fetch_assoc()):
                $profit = $b['harga_jual'] - $b['harga_beli'];
                $margin = $b['harga_jual']>0 ? ($profit/$b['harga_jual']*100) : 0;
                $stokRendah = $b['stok'] < $b['stok_minimal'];
            ?>
            <tr style="<?= $stokRendah ? 'background:#fff8f8;' : '' ?>">
                <td>
                    <?php if(!empty($b['gambar'])): ?>
                        <img src="../../uploads/barang/<?= htmlspecialchars($b['gambar']) ?>"
                             style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                    <?php else: ?>
                        <span style="font-size:28px;">📦</span>
                    <?php endif; ?>
                </td>
                <td class="fw-bold"><?= htmlspecialchars($b['kode']) ?></td>
                <td><?= htmlspecialchars($b['nama']) ?></td>
                <td><span class="badge-cat"><?= htmlspecialchars($b['kategori']??'-') ?></span></td>
                <td>Rp <?= number_format($b['harga_beli']) ?></td>
                <td class="fw-bold" style="color:#1a0aff">Rp <?= number_format($b['harga_jual']) ?></td>
                <td><div class="margin-plus">+Rp <?= number_format($profit) ?></div><div style="font-size:10px;color:#888">(<?= number_format($margin,1) ?>%)</div></td>
                <td class="fw-bold <?= $stokRendah?'text-danger':'' ?>" style="color:<?= $stokRendah?'#dc3545':'#28a745' ?>"><?= $b['stok'] ?></td>
                <td><?= $b['stok_minimal'] ?></td>
                <td>
                    <button class="act-btn-edit" onclick='editBarang(<?= json_encode($b) ?>)' title="Edit">✏️</button>
                    <button class="act-btn-del"  onclick="hapusBarang(<?= $b['id'] ?>,'<?= addslashes($b['nama']) ?>')" title="Hapus">🗑️</button>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal-box">
        <div class="modal-hd"><div><div class="modal-hd-title">📦 Tambah Barang Baru</div><div class="modal-hd-sub">Lengkapi data barang</div></div><button class="modal-hd-close" onclick="closeModal('modalTambah')">✕</button></div>
        <form onsubmit="submitBarang(event,'tambah')">
        <div class="modal-bd">
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Kode Barang</label><input type="text" class="pm-form-ctrl" name="kode" placeholder="BRG001" required></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Kategori</label>
                    <select class="pm-form-ctrl" name="kategori_id" required><option value="">Pilih...</option>
                    <?php foreach($kategoriArr as $k): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="pm-form-group"><label class="pm-form-lbl req">Nama Barang</label><input type="text" class="pm-form-ctrl" name="nama" placeholder="Nama lengkap barang" required></div>
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Harga Beli</label><input type="number" class="pm-form-ctrl" name="harga_beli" min="0" required></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Harga Jual</label><input type="number" class="pm-form-ctrl" name="harga_jual" min="0" required></div>
            </div>
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Stok Awal</label><input type="number" class="pm-form-ctrl" name="stok" min="0" value="0" required></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Stok Minimal</label><input type="number" class="pm-form-ctrl" name="stok_minimal" min="0" value="10" required></div>
            </div>
            <div class="pm-form-group">
                <label class="pm-form-lbl">Gambar Barang</label>
                <input type="file" class="pm-form-ctrl" id="inputGambarTambah" accept="image/*" onchange="previewGambar('prevTambah',this)">
                <img id="prevTambah" src="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-top:6px;">
            </div>
        </div>
        <div class="modal-ft"><button type="button" class="modal-btn-sec" onclick="closeModal('modalTambah')">Batal</button><button type="submit" class="modal-btn-prim">+ Tambah Barang</button></div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box">
        <div class="modal-hd"><div><div class="modal-hd-title">✏️ Edit Barang</div><div class="modal-hd-sub">Ubah data barang</div></div><button class="modal-hd-close" onclick="closeModal('modalEdit')">✕</button></div>
        <form onsubmit="submitBarang(event,'edit')">
        <input type="hidden" name="id" id="editId">
        <div class="modal-bd">
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Kode</label><input type="text" class="pm-form-ctrl" name="kode" id="editKode" required></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Kategori</label>
                    <select class="pm-form-ctrl" name="kategori_id" id="editKat" required><option value="">Pilih...</option>
                    <?php foreach($kategoriArr as $k): ?><option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="pm-form-group"><label class="pm-form-lbl req">Nama Barang</label><input type="text" class="pm-form-ctrl" name="nama" id="editNama" required></div>
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Harga Beli</label><input type="number" class="pm-form-ctrl" name="harga_beli" id="editHB" min="0" required oninput="calcMargin()"></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Harga Jual</label><input type="number" class="pm-form-ctrl" name="harga_jual" id="editHJ" min="0" required oninput="calcMargin()"></div>
            </div>
            <div class="pm-form-row">
                <div class="pm-form-group"><label class="pm-form-lbl req">Stok</label><input type="number" class="pm-form-ctrl" name="stok" id="editStok" min="0" required></div>
                <div class="pm-form-group"><label class="pm-form-lbl req">Stok Minimal</label><input type="number" class="pm-form-ctrl" name="stok_minimal" id="editSM" min="0" required></div>
            </div>
            <div id="marginInfo" style="background:#e8f5e9;border-radius:8px;padding:10px 14px;font-size:13px;"></div>
            <div class="pm-form-group" style="margin-top:12px;">
                <label class="pm-form-lbl">Gambar Barang</label>
                <img id="prevEdit" src="" style="display:none;width:80px;height:80px;object-fit:cover;border-radius:8px;margin-bottom:6px;display:block;">
                <input type="file" class="pm-form-ctrl" id="inputGambarEdit" accept="image/*" onchange="previewGambar('prevEdit',this)">
            </div>
        </div>
        <div class="modal-ft"><button type="button" class="modal-btn-sec" onclick="closeModal('modalEdit')">Batal</button><button type="submit" class="modal-btn-prim">💾 Simpan</button></div>
        </form>
    </div>
</div>

<script>
function editBarang(b){
    document.getElementById('editId').value=b.id;
    document.getElementById('editKode').value=b.kode;
    document.getElementById('editNama').value=b.nama;
    document.getElementById('editKat').value=b.kategori_id;
    document.getElementById('editHB').value=b.harga_beli;
    document.getElementById('editHJ').value=b.harga_jual;
    document.getElementById('editStok').value=b.stok;
    document.getElementById('editSM').value=b.stok_minimal;
    // Tampilkan gambar lama jika ada
    const prevEdit = document.getElementById('prevEdit');
    if(b.gambar){ prevEdit.src='../../uploads/barang/'+b.gambar; prevEdit.style.display='block'; }
    else { prevEdit.style.display='none'; }
    calcMargin(); openModal('modalEdit');
}
function calcMargin(){
    const hb=parseFloat(document.getElementById('editHB').value)||0;
    const hj=parseFloat(document.getElementById('editHJ').value)||0;
    const m=hj>0?((hj-hb)/hj*100).toFixed(1):0;
    document.getElementById('marginInfo').innerHTML=`Margin: <strong style="color:#28a745">Rp ${(hj-hb).toLocaleString('id-ID')},- (${m}%)</strong>`;
}
function previewGambar(previewId, input){
    if(!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById(previewId);
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
async function submitBarang(e, mode){
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.action = mode;

    // Upload gambar dulu jika ada
    const fileInput = document.getElementById(mode==='tambah' ? 'inputGambarTambah' : 'inputGambarEdit');
    if(fileInput && fileInput.files[0]){
        const fd = new FormData();
        fd.append('gambar', fileInput.files[0]);
        const upRes = await fetch('../../backend/api/barang.php', {method:'POST', body:fd});
        const upData = await upRes.json();
        if(upData.success) data.gambar = upData.data.nama_file;
        else { showToast('Gagal upload gambar','error'); return; }
    }

    const res = await fetch('../../backend/api/barang.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)});
    const r = await res.json();
    if(r.success){ showToast(r.message); setTimeout(()=>location.reload(), 800); }
    else showToast(r.message||'Gagal','error');
}
async function hapusBarang(id,nama){
    if(!confirm(`Hapus barang "${nama}"?`))return;
    const res=await fetch('../../backend/api/barang.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'hapus',id})});
    const r=await res.json();
    if(r.success){showToast('Barang dihapus');setTimeout(()=>location.reload(),800);}
    else showToast(r.message||'Gagal','error');
}
</script>
<?php include 'pemilik_footer.php'; ?>
