<?php
function getNavByRole($jabatan) {
    $navs = [
        'pemilik' => [
            ['url' => 'dashboard.php',  'icon' => '📊', 'label' => 'Dashboard'],
            ['url' => 'barang.php',     'icon' => '📦', 'label' => 'Data Barang'],
            ['url' => 'stok.php',       'icon' => '🏷️', 'label' => 'Kelola Stok'],
            ['url' => 'transaksi.php',  'icon' => '💳', 'label' => 'Transaksi'],
            ['url' => 'pembelian.php',  'icon' => '🛒', 'label' => 'Pembelian'],
            ['url' => 'laporan.php',    'icon' => '📈', 'label' => 'Laporan'],
            ['url' => 'presensi.php',   'icon' => '📋', 'label' => 'Presensi'],
            ['url' => 'profil.php',     'icon' => '👤', 'label' => 'Profil'],
        ],
        'kasir' => [
            ['url' => 'transaksi.php',  'icon' => '💳', 'label' => 'Transaksi'],
            ['url' => 'riwayat.php',    'icon' => '📜', 'label' => 'Riwayat Order'],
            ['url' => 'presensi.php',   'icon' => '📋', 'label' => 'Presensi'],
            ['url' => 'profil.php',     'icon' => '👤', 'label' => 'Profil'],
        ],
        'pengelola_stok' => [
            ['url' => 'stok.php',       'icon' => '🏷️', 'label' => 'Kelola Stok'],
            ['url' => 'barang.php',     'icon' => '📦', 'label' => 'Data Barang'],
            ['url' => 'presensi.php',   'icon' => '📋', 'label' => 'Presensi'],
            ['url' => 'profil.php',     'icon' => '👤', 'label' => 'Profil'],
        ],
    ];
    return $navs[$jabatan] ?? [];
}
?>
