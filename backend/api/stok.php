<?php
require_once __DIR__ . '/../config.php';
requireLogin();
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

if ($action === 'update') {
    requireRole(['pengelola_stok','pemilik']);
    $barangId = intval($input['barang_id']);
    $qty      = intval($input['qty']);
    $catatan  = sanitize($conn, $input['catatan'] ?? '');
    $userId   = (int)$_SESSION['user_id'];

    if ($qty < 1) jsonResponse(false, 'Jumlah tidak valid');

    $res = $conn->query("SELECT * FROM barang WHERE id=$barangId");
    if (!$res || $res->num_rows === 0) jsonResponse(false, 'Barang tidak ditemukan');

    $barang     = $res->fetch_assoc();
    $stokBefore = (int)$barang['stok'];
    $stokAfter  = $stokBefore + $qty;

    $conn->query("UPDATE barang SET stok=$stokAfter WHERE id=$barangId");
    $conn->query("INSERT INTO log_stok (barang_id,user_id,tipe,jumlah,stok_sebelum,stok_sesudah,keterangan) VALUES ($barangId,$userId,'masuk',$qty,$stokBefore,$stokAfter,'$catatan')");

    if ($stokAfter >= $barang['stok_minimal']) {
        $conn->query("UPDATE notifikasi SET is_read=1 WHERE barang_id=$barangId AND tipe='stok_rendah'");
    }
    jsonResponse(true, 'Stok berhasil diupdate', ['stok_baru' => $stokAfter]);
} else {
    jsonResponse(false, 'Aksi tidak dikenal');
}
?>
