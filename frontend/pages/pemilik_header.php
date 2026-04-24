<?php
require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/nav.php';
requireLogin();
requireRole('pemilik');

$user        = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$uid         = (int)$_SESSION['user_id'];
$ud          = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sinta Cell') ?></title>
    <link rel="stylesheet" href="../../frontend/css/kasir.css">
    <link rel="stylesheet" href="../../frontend/css/pemilik.css">
    <?= $extraHead ?? '' ?>
</head>
<body>

<nav class="topnav">
    <div class="topnav-left">
        <div class="topnav-logo">
            <div class="logo-icon">👤</div>
            <div><div class="logo-name">Sinta Cell</div>
            </div>
        </div>
    </div>
    <div class="topnav-menu">
        <a href="pemilik_barang.php"   class="nav-item <?= $currentPage==='pemilik_barang.php'  ?'active':'' ?>">Kelola Data Barang</a>
        <a href="pemilik_laporan.php"  class="nav-item <?= $currentPage==='pemilik_laporan.php' ?'active':'' ?>">Laporan</a>
        <a href="pemilik_pembelian.php" class="nav-item <?= $currentPage==='pemilik_pembelian.php'?'active':'' ?>">Pembelian</a>
    </div>
    <div class="topnav-right">
        <div class="profile-btn" onclick="toggleProfilePanel()">
            <div class="profile-avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($user['nama']) ?> - Pemilik</div>
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
            <div class="pp-name"><?= htmlspecialchars($user['nama']) ?> - Pemilik</div>
            <div class="pp-email">@<?= htmlspecialchars($user['username']) ?></div>
        </div>
        <a href="logout.php" class="pp-logout-btn" style="margin-top:auto;">Keluar</a>
    </div>
    <div class="profile-panel-right">
        <button class="pp-close" onclick="closeProfilePanel()">✕</button>
        <form method="POST" action="profil_kasir.php">
            <input type="hidden" name="action" value="update">
            <div class="pp-form-grid">
                <div class="pp-field"><label>Nama</label><input type="text" name="nama" value="<?= htmlspecialchars($ud['nama']) ?>" class="pp-input"></div>
                <div class="pp-field"><label>Id Pegawai</label><input type="text" value="<?= htmlspecialchars($ud['id_pegawai']??'-') ?>" class="pp-input" disabled></div>
                <div class="pp-field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($ud['email']??'') ?>" class="pp-input"></div>
                <div class="pp-field"><label>Jabatan</label><input type="text" value="Pemilik" class="pp-input" disabled></div>
                <div class="pp-field pp-field-full"><label>Telepon</label><input type="text" name="telepon" value="<?= htmlspecialchars($ud['telepon']??'') ?>" class="pp-input"></div>
                <div class="pp-field pp-field-full"><label>Alamat</label><input type="text" name="alamat" value="<?= htmlspecialchars($ud['alamat']??'') ?>" class="pp-input"></div>
            </div>
            <button type="submit" class="pp-save-btn">Simpan Perubahan</button>
        </form>
    </div>
</div>

<div class="page-content">
