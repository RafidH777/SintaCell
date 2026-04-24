<?php
require_once __DIR__ . '/../config.php';
requireLogin();
requireRole(['pemilik','pengelola_stok']);
header('Content-Type: application/json');

// Handle file upload (multipart)
if (isset($_FILES['gambar'])) {
    $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if (!in_array($ext, $allowed)) jsonResponse(false, 'Format tidak didukung');
    $nama   = 'brg_' . uniqid() . '.' . $ext;
    $tujuan = __DIR__ . '/../../uploads/barang/' . $nama;
    if (move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan)) {
        jsonResponse(true, 'Upload berhasil', ['nama_file' => $nama]);
    } else {
        jsonResponse(false, 'Gagal upload');
    }
}

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
    $gambar      = sanitize($conn, $input['gambar'] ?? '');

    if (!$kode || !$nama) jsonResponse(false, 'Kode dan nama wajib diisi');

    $cek = $conn->query("SELECT id FROM barang WHERE kode='$kode'");
    if ($cek->num_rows > 0) jsonResponse(false, 'Kode barang sudah digunakan');

    $conn->query("INSERT INTO barang (kode,nama,kategori_id,harga_beli,harga_jual,stok,stok_minimal,gambar)
                  VALUES ('$kode','$nama',$kategoriId,$hargaBeli,$hargaJual,$stok,$stokMinimal,'$gambar')");

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
    $gambar      = sanitize($conn, $input['gambar'] ?? '');

    $conn->query("UPDATE barang SET kode='$kode',nama='$nama',kategori_id=$kategoriId,
                  harga_beli=$hargaBeli,harga_jual=$hargaJual,stok=$stok,stok_minimal=$stokMinimal,
                  gambar=IF('$gambar'='', gambar, '$gambar')
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
