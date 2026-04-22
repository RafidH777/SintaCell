<?php
require_once __DIR__ . '/../config.php';
requireLogin();
requireRole('pemilik');
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'tambah') {
    $nama    = sanitize($conn, $input['nama']    ?? '');
    $alamat  = sanitize($conn, $input['alamat']  ?? '');
    $telepon = sanitize($conn, $input['telepon'] ?? '');
    $email   = sanitize($conn, $input['email']   ?? '');

    if (!$nama) jsonResponse(false, 'Nama supplier wajib diisi');

    // Cek duplikat nama
    $cek = $conn->query("SELECT id FROM supplier WHERE nama='$nama'");
    if ($cek->num_rows > 0) jsonResponse(false, 'Nama supplier sudah terdaftar');

    $conn->query("INSERT INTO supplier (nama, alamat, telepon, email) VALUES ('$nama','$alamat','$telepon','$email')");

    if ($conn->affected_rows > 0) {
        jsonResponse(true, 'Supplier berhasil ditambahkan', ['id' => $conn->insert_id, 'nama' => $nama]);
    } else {
        jsonResponse(false, 'Gagal menambahkan supplier');
    }

} elseif ($action === 'hapus') {
    $id = intval($input['id']);

    // Cek apakah supplier sudah dipakai di pembelian
    $cek = $conn->query("SELECT id FROM pembelian_barang WHERE supplier_id=$id LIMIT 1");
    if ($cek->num_rows > 0) {
        jsonResponse(false, 'Supplier tidak dapat dihapus karena sudah digunakan dalam transaksi pembelian');
    }

    $conn->query("DELETE FROM supplier WHERE id=$id");
    if ($conn->affected_rows > 0) jsonResponse(true, 'Supplier berhasil dihapus');
    else jsonResponse(false, 'Supplier tidak ditemukan');

} elseif ($action === 'list') {
    $result = $conn->query("SELECT * FROM supplier ORDER BY nama");
    $data   = [];
    while ($row = $result->fetch_assoc()) $data[] = $row;
    jsonResponse(true, '', $data);

} else {
    jsonResponse(false, 'Aksi tidak dikenal');
}
?>
