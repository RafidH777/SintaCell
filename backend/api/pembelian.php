<?php
require_once __DIR__ . '/../config.php';
requireLogin();
requireRole('pemilik');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed');

$input      = json_decode(file_get_contents('php://input'), true);
$tanggal    = sanitize($conn, $input['tanggal'] ?? date('Y-m-d'));
$supplierId = intval($input['supplier_id'] ?? 0);
$items      = $input['items'] ?? [];
$pemilikId  = (int)$_SESSION['user_id'];

if (empty($items)) jsonResponse(false, 'Tidak ada item pembelian');

$total = 0;
foreach ($items as $item) {
    $total += floatval($item['harga']) * intval($item['qty']);
}

$noPembelian  = generateKode('PBL', 'pembelian_barang', 'no_pembelian', $conn);
$supplierVal  = $supplierId ? $supplierId : 'NULL';

$conn->begin_transaction();
try {
    $conn->query("INSERT INTO pembelian_barang (no_pembelian,supplier_id,pemilik_id,tanggal,total,status)
                  VALUES ('$noPembelian',$supplierVal,$pemilikId,'$tanggal',$total,'selesai')");
    $pembelianId = $conn->insert_id;

    foreach ($items as $item) {
        $barangId = intval($item['barang_id']);
        $qty      = intval($item['qty']);
        $harga    = floatval($item['harga']);
        $sub      = $qty * $harga;
        $nama     = sanitize($conn, $item['nama']);

        $stokRes    = $conn->query("SELECT stok FROM barang WHERE id=$barangId");
        if (!$stokRes || $stokRes->num_rows === 0) throw new Exception("Barang ID $barangId tidak ditemukan");
        $stokBefore = (int)$stokRes->fetch_assoc()['stok'];
        $stokAfter  = $stokBefore + $qty;

        $conn->query("INSERT INTO detail_pembelian (pembelian_id,barang_id,nama_barang,harga_beli,jumlah,subtotal)
                      VALUES ($pembelianId,$barangId,'$nama',$harga,$qty,$sub)");
        $conn->query("UPDATE barang SET stok=$stokAfter, harga_beli=$harga WHERE id=$barangId");
        $conn->query("INSERT INTO log_stok (barang_id,user_id,tipe,jumlah,stok_sebelum,stok_sesudah,referensi,keterangan)
                      VALUES ($barangId,$pemilikId,'masuk',$qty,$stokBefore,$stokAfter,'$noPembelian','Pembelian dari supplier')");

        // Tandai notifikasi stok rendah sebagai sudah dibaca jika stok sudah aman
        $minRes  = $conn->query("SELECT stok_minimal FROM barang WHERE id=$barangId");
        $minStok = (int)$minRes->fetch_assoc()['stok_minimal'];
        if ($stokAfter >= $minStok) {
            $conn->query("UPDATE notifikasi SET is_read=1 WHERE barang_id=$barangId AND tipe='stok_rendah'");
        }
    }

    $conn->commit();
    jsonResponse(true, 'Pembelian berhasil disimpan', ['no_pembelian' => $noPembelian]);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, $e->getMessage());
}
?>
