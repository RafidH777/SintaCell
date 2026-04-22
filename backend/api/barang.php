<?php
require_once __DIR__ . '/../config.php';
requireLogin();
requireRole(['pemilik','pengelola_stok']);
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'tambah') {
    requireRole('pemilik');
    $kode        = sanitize($conn, $input['kode']);
    $nama        = sanitize($conn, $input['nama']);
    $kategoriId  = intval($input['kategori_id']);
    $hargaBeli   = floatval($input['harga_beli']);
    $hargaJual   = floatval($input['harga_jual']);
    $stok        = intval($input['stok']);
    $stokMinimal = intval($input['stok_minimal']);

    if (!$kode || !$nama) jsonResponse(false, 'Kode dan nama wajib diisi');

    $cek = $conn->query("SELECT id FROM barang WHERE kode='$kode'");
    if ($cek->num_rows > 0) jsonResponse(false, 'Kode barang sudah digunakan');

    $conn->query("INSERT INTO barang (kode,nama,kategori_id,harga_beli,harga_jual,stok,stok_minimal)
                  VALUES ('$kode','$nama',$kategoriId,$hargaBeli,$hargaJual,$stok,$stokMinimal)");

    if ($conn->affected_rows > 0) jsonResponse(true, 'Barang berhasil ditambahkan');
    else jsonResponse(false, 'Gagal menambahkan barang');

} elseif ($action === 'edit') {
    requireRole('pemilik');
    $id          = intval($input['id']);
    $kode        = sanitize($conn, $input['kode']);
    $nama        = sanitize($conn, $input['nama']);
    $kategoriId  = intval($input['kategori_id']);
    $hargaBeli   = floatval($input['harga_beli']);
    $hargaJual   = floatval($input['harga_jual']);
    $stok        = intval($input['stok']);
    $stokMinimal = intval($input['stok_minimal']);

    $conn->query("UPDATE barang SET kode='$kode',nama='$nama',kategori_id=$kategoriId,
                  harga_beli=$hargaBeli,harga_jual=$hargaJual,stok=$stok,stok_minimal=$stokMinimal
                  WHERE id=$id");

    jsonResponse(true, 'Barang berhasil diupdate');

} elseif ($action === 'hapus') {
    requireRole('pemilik');
    $id = intval($input['id']);

    $cek = $conn->query("SELECT id FROM detail_transaksi WHERE barang_id=$id LIMIT 1");
    if ($cek->num_rows > 0) jsonResponse(false, 'Barang tidak dapat dihapus karena sudah ada transaksi');

    $conn->query("DELETE FROM notifikasi WHERE barang_id=$id");
    $conn->query("DELETE FROM log_stok WHERE barang_id=$id");
    $conn->query("DELETE FROM barang WHERE id=$id");

    if ($conn->affected_rows > 0) jsonResponse(true, 'Barang berhasil dihapus');
    else jsonResponse(false, 'Barang tidak ditemukan');

} else {
    jsonResponse(false, 'Aksi tidak dikenal');
}
?>
