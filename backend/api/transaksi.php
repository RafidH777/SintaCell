<?php
require_once __DIR__ . '/../config.php';
requireLogin();
requireRole(['kasir','pemilik']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed');

$input = json_decode(file_get_contents('php://input'), true);
$items       = $input['items']       ?? [];
$namaPembeli = sanitize($conn, $input['nama_pembeli'] ?? 'Umum');
$diskon      = floatval($input['diskon']      ?? 0);
$bayar       = floatval($input['bayar']       ?? 0);
$metodeBayar = sanitize($conn, $input['metode_bayar'] ?? 'tunai');

if (empty($items)) jsonResponse(false, 'Tidak ada item');

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += floatval($item['harga']) * intval($item['qty']);
}
$total     = max(0, $subtotal - $diskon);
$kembalian = max(0, $bayar - $total);

$noTrx   = generateKode('TRX', 'transaksi_penjualan', 'no_transaksi', $conn);
$kasirId = (int)$_SESSION['user_id'];
$tanggal = date('Y-m-d H:i:s');

$conn->begin_transaction();
try {
    $conn->query("INSERT INTO transaksi_penjualan (no_transaksi,kasir_id,nama_pembeli,tanggal,subtotal,diskon,total,bayar,kembalian,metode_bayar,status)
                  VALUES ('$noTrx',$kasirId,'$namaPembeli','$tanggal',$subtotal,$diskon,$total,$bayar,$kembalian,'$metodeBayar','selesai')");
    $trxId = $conn->insert_id;

    $itemsResult = [];
    foreach ($items as $item) {
        $barangId = intval($item['id']);
        $harga    = floatval($item['harga']);
        $qty      = intval($item['qty']);
        $sub      = $harga * $qty;
        $nama     = sanitize($conn, $item['nama']);

        // Cek stok
        $stokRes = $conn->query("SELECT stok, stok_minimal FROM barang WHERE id=$barangId FOR UPDATE");
        if (!$stokRes || $stokRes->num_rows === 0) throw new Exception("Barang tidak ditemukan");
        $stokRow = $stokRes->fetch_assoc();
        if ($stokRow['stok'] < $qty) throw new Exception("Stok $nama tidak mencukupi (tersisa {$stokRow['stok']})");

        $stokBefore = (int)$stokRow['stok'];
        $stokAfter  = $stokBefore - $qty;

        $conn->query("INSERT INTO detail_transaksi (transaksi_id,barang_id,nama_barang,harga_satuan,jumlah,subtotal)
                      VALUES ($trxId,$barangId,'$nama',$harga,$qty,$sub)");
        $conn->query("UPDATE barang SET stok=$stokAfter WHERE id=$barangId");
        $conn->query("INSERT INTO log_stok (barang_id,user_id,tipe,jumlah,stok_sebelum,stok_sesudah,referensi,keterangan)
                      VALUES ($barangId,$kasirId,'keluar',$qty,$stokBefore,$stokAfter,'$noTrx','Penjualan')");

        // Notifikasi stok rendah
        $minStok = (int)$stokRow['stok_minimal'];
        if ($stokAfter < $minStok) {
            $judul = sanitize($conn, "Stok Rendah: $nama");
            $pesan = sanitize($conn, "Stok tersisa $stokAfter, di bawah minimum $minStok");
            $conn->query("INSERT INTO notifikasi (judul,pesan,tipe,barang_id) VALUES ('$judul','$pesan','stok_rendah',$barangId)");
        }

        $itemsResult[] = ['nama_barang' => $nama, 'jumlah' => $qty, 'subtotal' => $sub];
    }

    $conn->commit();
    jsonResponse(true, 'Transaksi berhasil', [
        'no_transaksi' => $noTrx,
        'tanggal'      => date('d/m/Y H:i', strtotime($tanggal)),
        'items'        => $itemsResult,
        'subtotal'     => $subtotal,
        'diskon'       => $diskon,
        'total'        => $total,
        'bayar'        => $bayar,
        'kembalian'    => $kembalian,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, $e->getMessage());
}
?>
