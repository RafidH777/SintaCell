<?php
$pageTitle = 'Profil - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();

$userId   = (int)$_SESSION['user_id'];
$userData = $conn->query("SELECT * FROM users WHERE id=$userId")->fetch_assoc();
$error    = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';
    if ($action==='update') {
        $nama    = sanitize($conn,$_POST['nama']);
        $email   = sanitize($conn,$_POST['email']);
        $telepon = sanitize($conn,$_POST['telepon']);
        $alamat  = sanitize($conn,$_POST['alamat']);
        $conn->query("UPDATE users SET nama='$nama',email='$email',telepon='$telepon',alamat='$alamat' WHERE id=$userId");
        $_SESSION['nama']=$nama;
        header('Location: profil.php?msg=Profil+berhasil+diupdate'); exit;
    } elseif ($action==='password') {
        $old=$_POST['old_password']; $new=$_POST['new_password']; $conf=$_POST['confirm_password'];
        if (!password_verify($old,$userData['password'])) $error='Password lama salah!';
        elseif ($new!==$conf) $error='Konfirmasi password tidak cocok!';
        elseif (strlen($new)<6) $error='Password minimal 6 karakter!';
        else { $conn->query("UPDATE users SET password='".password_hash($new,PASSWORD_DEFAULT)."' WHERE id=$userId"); header('Location: profil.php?msg=Password+berhasil+diubah'); exit; }
    }
}

$jabatanLabel=['pemilik'=>'Pemilik','kasir'=>'Kasir','pengelola_stok'=>'Pengelola Stok'];
include 'header.php';
?>
<div class="page-title"><h1>👤 Profil Akun</h1><p>Kelola informasi akun Anda</p></div>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">
    <div>
        <div class="card" style="text-align:center;padding:30px 20px;">
            <div style="width:90px;height:90px;border-radius:50%;background:var(--purple);color:white;display:inline-flex;align-items:center;justify-content:center;font-size:36px;font-weight:bold;margin-bottom:12px;"><?= strtoupper(substr($userData['nama'],0,1)) ?></div>
            <div class="fw-bold" style="font-size:17px;"><?= htmlspecialchars($userData['nama']) ?></div>
            <div class="text-muted small mb-2"><?= htmlspecialchars($userData['email']??'-') ?></div>
            <span class="badge badge-primary"><?= $jabatanLabel[$userData['jabatan']] ?></span>
            <div class="mt-1 small text-muted">ID: <?= htmlspecialchars($userData['id_pegawai']??'-') ?></div>
        </div>
        <div class="card mt-2"><div class="card-body">
            <div class="small text-muted">Username</div><div class="fw-bold mb-2"><?= htmlspecialchars($userData['username']) ?></div>
            <div class="small text-muted">Jabatan</div><div class="fw-bold mb-2"><?= $jabatanLabel[$userData['jabatan']] ?></div>
            <div class="small text-muted">Bergabung</div><div class="fw-bold"><?= date('d/m/Y',strtotime($userData['created_at'])) ?></div>
        </div></div>
    </div>
    <div>
        <div class="card mb-2">
            <div class="card-header"><strong>✏️ Edit Profil</strong></div>
            <div class="card-body">
                <form method="POST"><input type="hidden" name="action" value="update">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label required">Nama Lengkap</label><input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($userData['nama']) ?>" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($userData['email']??'') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Telepon</label><input type="text" class="form-control" name="telepon" value="<?= htmlspecialchars($userData['telepon']??'') ?>"></div>
                        <div class="form-group"><label class="form-label">Alamat</label><input type="text" class="form-control" name="alamat" value="<?= htmlspecialchars($userData['alamat']??'') ?>"></div>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><strong>🔒 Ganti Password</strong></div>
            <div class="card-body">
                <form method="POST"><input type="hidden" name="action" value="password">
                    <div class="form-group"><label class="form-label required">Password Lama</label><input type="password" class="form-control" name="old_password" required></div>
                    <div class="form-row">
                        <div class="form-group"><label class="form-label required">Password Baru</label><input type="password" class="form-control" name="new_password" required minlength="6"></div>
                        <div class="form-group"><label class="form-label required">Konfirmasi</label><input type="password" class="form-control" name="confirm_password" required></div>
                    </div>
                    <button type="submit" class="btn btn-danger">🔒 Ganti Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
