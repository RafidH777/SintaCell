<?php
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik', 'pengelola_stok']);

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='update') {
    $nama    = sanitize($conn, $_POST['nama']    ?? '');
    $email   = sanitize($conn, $_POST['email']   ?? '');
    $telepon = sanitize($conn, $_POST['telepon'] ?? '');
    $alamat  = sanitize($conn, $_POST['alamat']  ?? '');
    $conn->query("UPDATE users SET nama='$nama',email='$email',telepon='$telepon',alamat='$alamat' WHERE id=$userId");
    $_SESSION['nama'] = $nama;
}

// Redirect back ke halaman sebelumnya
$role = $_SESSION['role'] ?? 'kasir';
$fallback = $role === 'pengelola_stok' ? 'stok_manager.php' : ($role === 'pemilik' ? '../pages/pemilik_barang.php' : 'kasir.php');
$ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
header('Location: '.$ref);
exit;
?>
