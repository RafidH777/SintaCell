<?php
require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/nav.php';
requireLogin();

$user        = currentUser();
$jabatan     = $user['jabatan'];
$navs        = getNavByRole($jabatan);
$currentPage = basename($_SERVER['PHP_SELF']);

$notifCount  = 0;
$notifResult = $conn->query("SELECT COUNT(*) as cnt FROM notifikasi WHERE is_read = 0");
if ($notifResult) $notifCount = (int)$notifResult->fetch_assoc()['cnt'];

$jabatanLabel = ['pemilik'=>'Pemilik','kasir'=>'Kasir','pengelola_stok'=>'Pengelola Stok'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Sinta Cell') ?></title>
    <link rel="stylesheet" href="../../frontend/css/style.css">
    <?= $extraHead ?? '' ?>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-logo">📱 Sinta Cell</div>

    <div class="header-nav">
        <?php foreach ($navs as $nav): ?>
        <a href="<?= $nav['url'] ?>" class="<?= $currentPage === $nav['url'] ? 'active' : '' ?>">
            <?= $nav['icon'] ?> <?= $nav['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div style="display:flex;align-items:center;gap:14px;">
        <?php if ($notifCount > 0): ?>
        <div class="notif-bell" title="<?= $notifCount ?> notifikasi stok rendah">
            🔔<span class="notif-badge"><?= $notifCount ?></span>
        </div>
        <?php endif; ?>

        <div class="header-user">
            <div>
                <div class="user-name"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="user-role"><?= $jabatanLabel[$jabatan] ?? $jabatan ?></div>
            </div>
            <div class="avatar"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
        </div>
        <a href="logout.php" class="btn btn-sm"
           style="background:rgba(255,255,255,.2);color:white;border:1px solid rgba(255,255,255,.4);">
            Keluar
        </a>
    </div>
</div>

<!-- LAYOUT -->
<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <?php foreach ($navs as $nav): ?>
        <a href="<?= $nav['url'] ?>" class="<?= $currentPage === $nav['url'] ? 'active' : '' ?>">
            <span class="icon"><?= $nav['icon'] ?></span>
            <?= $nav['label'] ?>
        </a>
        <?php endforeach; ?>

        <div style="margin-top:30px;border-top:1px solid var(--gray-medium);padding-top:10px;">
            <a href="logout.php" style="color:var(--danger)!important;">
                <span class="icon">🚪</span> Logout
            </a>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
