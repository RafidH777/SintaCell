<?php
require_once __DIR__ . '/../../backend/config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
requireLogin();
requireRole(['kasir']);

$user        = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$jabatanLabel= ['pemilik'=>'Pemilik','kasir'=>'Cashier','pengelola_stok'=>'Pengelola Stok'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sinta Cell') ?></title>
    <link rel="stylesheet" href="../../frontend/css/kasir.css">
    <?= $extraHead ?? '' ?>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="topnav">
    <div class="topnav-left">
        <div class="topnav-logo">
            <div class="logo-icon">👤</div>
            <div>
                <div class="logo-name">Sinta Cell</div>
            </div>
        </div>
    </div>

    <div class="topnav-menu">
        <a href="kasir.php"   class="nav-item <?= $currentPage==='kasir.php'  ?'active':'' ?>">Kasir</a>
        <a href="order.php"   class="nav-item <?= $currentPage==='order.php'  ?'active':'' ?>">Order</a>
        <a href="lap_kasir.php" class="nav-item <?= $currentPage==='lap_kasir.php'?'active':'' ?>">Laporan</a>
    </div>

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

<!-- PROFILE PANEL (slide from right) -->
<div class="profile-overlay" id="profileOverlay" onclick="closeProfilePanel()"></div>
<div class="profile-panel" id="profilePanel">
    <div class="profile-panel-left">
        <div class="pp-user">
            <div class="pp-avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
            <div class="pp-name"><?= htmlspecialchars($user['nama']) ?> - <?= $jabatanLabel[$user['jabatan']] ?></div>
            <div class="pp-email">@<?= htmlspecialchars($user['username']) ?></div>
        </div>
        <div class="pp-actions">
            <a href="presensi_kasir.php" class="pp-presensi-btn">Presensi</a>
            <a href="logout.php" class="pp-logout-btn">Keluar</a>
        </div>
    </div>
    <div class="profile-panel-right">
        <button class="pp-close" onclick="closeProfilePanel()">✕</button>
        <form method="POST" action="profil_kasir.php">
            <input type="hidden" name="action" value="update">
            <div class="pp-form-grid">
                <div class="pp-field">
                    <label>Nama</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" class="pp-input">
                </div>
                <div class="pp-field">
                    <label>Id Pegawai</label>
                    <?php
                    $uid = (int)$_SESSION['user_id'];
                    $ud  = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
                    ?>
                    <input type="text" value="<?= htmlspecialchars($ud['id_pegawai']??'-') ?>" class="pp-input" disabled>
                </div>
                <div class="pp-field">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($ud['email']??'') ?>" class="pp-input">
                </div>
                <div class="pp-field">
                    <label>Jabatan</label>
                    <input type="text" value="<?= $jabatanLabel[$user['jabatan']] ?>" class="pp-input" disabled>
                </div>
                <div class="pp-field pp-field-full">
                    <label>Telepon</label>
                    <input type="text" name="telepon" value="<?= htmlspecialchars($ud['telepon']??'') ?>" class="pp-input">
                </div>
                <div class="pp-field pp-field-full">
                    <label>Alamat</label>
                    <input type="text" name="alamat" value="<?= htmlspecialchars($ud['alamat']??'') ?>" class="pp-input">
                </div>
            </div>
            <button type="submit" class="pp-save-btn">Simpan Perubahan</button>
        </form>
    </div>
</div>

<!-- PAGE CONTENT -->
<div class="page-content">
