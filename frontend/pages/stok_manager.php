<?php
$pageTitle = 'Kelola Stok - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['pengelola_stok','pemilik']);

$filter = $_GET['filter'] ?? 'semua';
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = "1=1";
if ($search) $where .= " AND b.nama LIKE '%$search%' OR b.kode LIKE '%$search%'";
if ($filter === 'rendah') $where .= " AND b.stok < b.stok_minimal";
if ($filter === 'aman')   $where .= " AND b.stok >= b.stok_minimal";

$barangList  = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE $where ORDER BY b.stok ASC");
$stokRendah  = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok < stok_minimal")->fetch_assoc()['c'];
$stokAman    = (int)$conn->query("SELECT COUNT(*) c FROM barang WHERE stok >= stok_minimal")->fetch_assoc()['c'];
$totalBarang = $stokRendah + $stokAman;

$user     = currentUser();
$uid      = (int)$_SESSION['user_id'];
$ud       = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$jabatanLabel = ['pemilik'=>'Pemilik','kasir'=>'Cashier','pengelola_stok'=>'Pengelola Stok'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../frontend/css/kasir.css">
    <link rel="stylesheet" href="../../frontend/css/pemilik.css">
</head>
<body>

<!-- TOP NAVBAR - stok manager (no menu links, just logo + profile) -->
<nav class="topnav">
    <div class="topnav-left">
        <div class="topnav-logo">
            <div class="logo-icon">👤</div>
            <div><div class="logo-name">Sinta Cell</div><div class="logo-sub">Toko Handphone</div></div>
        </div>
    </div>
    <div class="topnav-menu"></div><!-- empty center -->
    <div class="topnav-right">
        <div class="profile-btn" onclick="toggleProfilePanel()">
            <div class="profile-avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($user['nama']) ?> - <?= $jabatanLabel[$user['jabatan']] ?></div>
                <div class="profile-email">@<?= htmlspecialchars($user['username']) ?></div>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Panel -->
<div class="profile-overlay" id="profileOverlay" onclick="closeProfilePanel()"></div>
<div class="profile-panel" id="profilePanel">
    <div class="profile-panel-left">
        <div class="pp-user">
            <div class="pp-avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
            <div class="pp-name"><?= htmlspecialchars($user['nama']) ?> - <?= $jabatanLabel[$user['jabatan']] ?></div>
            <div class="pp-email">@<?= htmlspecialchars($user['username']) ?></div>
        </div>
        <a href="presensi_kasir.php" class="pp-presensi-btn">Presensi</a>
        <a href="logout.php" class="pp-logout-btn">Keluar</a>
    </div>
    <div class="profile-panel-right">
        <button class="pp-close" onclick="closeProfilePanel()">✕</button>
        <form method="POST" action="profil_kasir.php">
            <input type="hidden" name="action" value="update">
            <div class="pp-form-grid">
                <div class="pp-field"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($ud['nama']) ?>" class="pp-input"></div>
                <div class="pp-field"><label>Id Pegawai</label><input type="text" value="<?= htmlspecialchars($ud['id_pegawai']??'-') ?>" class="pp-input" disabled></div>
                <div class="pp-field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($ud['email']??'') ?>" class="pp-input"></div>
                <div class="pp-field"><label>Jabatan</label><input type="text" value="Pengelola Stok" class="pp-input" disabled></div>
                <div class="pp-field pp-field-full"><label>Telepon</label><input type="text" name="telepon" value="<?= htmlspecialchars($ud['telepon']??'') ?>" class="pp-input"></div>
                <div class="pp-field pp-field-full"><label>Alamat</label><input type="text" name="alamat" value="<?= htmlspecialchars($ud['alamat']??'') ?>" class="pp-input"></div>
            </div>
            <button type="submit" class="pp-save-btn">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div class="page-content">
<div class="pm-page">

    <?php if($stokRendah > 0): ?>
    <div class="pm-alert-banner">
        <div class="alert-icon">⚠️</div>
        <div>
            <div class="alert-title">⚠ <?= $stokRendah ?> Item Stok Minimal!</div>
            <div class="alert-sub">Segera lakukan restock untuk barang-barang berikut</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
        <div class="pm-stat pm-stat-blue"><div class="pm-stat-icon">📦</div><div class="pm-stat-info"><div class="lbl">Total Barang</div><div class="val"><?= $totalBarang ?></div></div></div>
        <div class="pm-stat pm-stat-red"><div class="pm-stat-icon">⚠️</div><div class="pm-stat-info"><div class="lbl">Stok Rendah</div><div class="val"><?= $stokRendah ?></div></div></div>
        <div class="pm-stat pm-stat-green"><div class="pm-stat-icon">📈</div><div class="pm-stat-info"><div class="lbl">Stok Aman</div><div class="val"><?= $stokAman ?></div></div></div>
    </div>

    <!-- Search + Filter -->
    <div style="background:white;border-radius:12px;padding:14px 16px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:14px;display:flex;gap:10px;align-items:center;">
        <div class="pm-search-box" style="flex:1;margin:0;border:none;padding:0;box-shadow:none;">
            <span style="color:#aaa;">🔍</span>
            <input type="text" id="searchInput" placeholder="Cari barang berdasarkan nama atau kode..." value="<?= htmlspecialchars($search) ?>" oninput="filterTable(this.value)">
        </div>
        <a href="?filter=semua"  class="pm-ftab <?= $filter==='semua'  ?'active-blue':'' ?>">Semua</a>
        <a href="?filter=rendah" class="pm-ftab <?= $filter==='rendah' ?'active-red':'' ?>">Stok Rendah</a>
        <a href="?filter=aman"   class="pm-ftab <?= $filter==='aman'   ?'active-green':'' ?>">Stok Aman</a>
    </div>

    <!-- Table -->
    <div class="pm-table-card">
        <table class="pm-table" id="stokTable">
            <thead>
                <tr>
                    <th>Kode</th><th>Nama Barang</th><th>Kategori</th>
                    <th>Stok Saat Ini</th><th>Stok Minimal</th>
                    <th>Status</th><th>Update Terakhir</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($b=$barangList->fetch_assoc()):
                $isRendah = $b['stok'] < $b['stok_minimal'];
            ?>
            <tr data-nama="<?= strtolower(htmlspecialchars($b['nama'])) ?>">
                <td class="fw-bold"><?= htmlspecialchars($b['kode']) ?></td>
                <td class="fw-bold"><?= htmlspecialchars($b['nama']) ?></td>
                <td><span class="badge-cat"><?= htmlspecialchars($b['kategori']??'-') ?></span></td>
                <td class="fw-bold" style="color:<?= $isRendah?'#dc3545':'#28a745' ?>;font-size:15px;"><?= $b['stok'] ?></td>
                <td><?= $b['stok_minimal'] ?></td>
                <td>
                    <?php if($isRendah): ?>
                    <span class="badge-warn">⚠ RENDAH</span>
                    <?php else: ?>
                    <span class="badge-ok">✓ AMAN</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:12px;color:#888;"><?= date('Y-m-d', strtotime($b['updated_at'])) ?></td>
                <td>
                    <?php if($isRendah): ?>
                    <button class="act-btn-restock" onclick="openUpdateStok(<?= $b['id'] ?>,'<?= addslashes($b['nama']) ?>',<?= $b['stok'] ?>,<?= $b['stok_minimal'] ?>)">
                        ↑ Restock
                    </button>
                    <?php else: ?>
                    <button class="act-btn-update" onclick="openUpdateStok(<?= $b['id'] ?>,'<?= addslashes($b['nama']) ?>',<?= $b['stok'] ?>,<?= $b['stok_minimal'] ?>)">
                        Update Stok
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div><!-- /page-content -->

<!-- Modal Update Stok -->
<div class="modal-overlay" id="modalUpdateStok">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-hd">
            <div><div class="modal-hd-title">📦 Update Stok</div><div class="modal-hd-sub">Tambah stok barang</div></div>
            <button class="modal-hd-close" onclick="closeModal('modalUpdateStok')">✕</button>
        </div>
        <div class="modal-bd">
            <div id="stokInfoCard" style="background:#e8edff;border-radius:10px;padding:14px;margin-bottom:16px;">
                <div id="stokNama" class="fw-bold" style="color:#1a0aff;font-size:15px;margin-bottom:8px;"></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                    <div><div style="font-size:11px;color:#888;">Stok Saat Ini</div><div class="fw-bold" id="stokSaatIni" style="font-size:18px;"></div></div>
                    <div><div style="font-size:11px;color:#888;">Stok Minimal</div><div class="fw-bold" id="stokMinimal" style="font-size:18px;color:#dc3545;"></div></div>
                </div>
            </div>
            <input type="hidden" id="updateBarangId">
            <div class="pm-form-group"><label class="pm-form-lbl req">Kuantitas Tambah</label><input type="number" class="pm-form-ctrl" id="updateQty" placeholder="Jumlah yang ditambah" min="1"></div>
            <div class="pm-form-group"><label class="pm-form-lbl">Alasan / Catatan</label><textarea class="pm-form-ctrl" id="updateCatatan" rows="2" placeholder="Contoh: Restock dari supplier..."></textarea></div>
        </div>
        <div class="modal-ft">
            <button class="modal-btn-sec" onclick="closeModal('modalUpdateStok')">Batal</button>
            <button class="modal-btn-prim" onclick="simpanUpdateStok()">💾 Simpan Update</button>
        </div>
    </div>
</div>

<script>
function toggleProfilePanel(){document.getElementById('profilePanel').classList.toggle('open');document.getElementById('profileOverlay').classList.toggle('open');}
function closeProfilePanel(){document.getElementById('profilePanel').classList.remove('open');document.getElementById('profileOverlay').classList.remove('open');}
function showToast(msg,type='success'){const t=document.createElement('div');t.style.cssText=`position:fixed;bottom:24px;right:24px;z-index:9999;background:${type==='success'?'#1a0aff':'#dc3545'};color:white;padding:12px 20px;border-radius:10px;font-size:14px;box-shadow:0 4px 16px rgba(0,0,0,.2);`;t.textContent=msg;document.body.appendChild(t);setTimeout(()=>t.remove(),3000);}
function openModal(id){document.getElementById(id).classList.add('active');}
function closeModal(id){document.getElementById(id).classList.remove('active');}
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.modal-overlay').forEach(o=>{o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('active');});});});

function filterTable(q){document.querySelectorAll('#stokTable tbody tr').forEach(r=>{r.style.display=r.dataset.nama.includes(q.toLowerCase())?'':'none';});}

function openUpdateStok(id,nama,stok,minimal){
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
    if(!qty||qty<1){showToast('Masukkan jumlah valid!','error');return;}
    const res=await fetch('../../backend/api/stok.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'update',barang_id:id,qty,catatan})});
    const r=await res.json();
    if(r.success){showToast('Stok berhasil diupdate!');closeModal('modalUpdateStok');setTimeout(()=>location.reload(),800);}
    else showToast(r.message||'Gagal','error');
}
</script>
</body>
</html>
