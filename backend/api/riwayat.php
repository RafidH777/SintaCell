<?php
require_once __DIR__ . '/../config.php';
requireLogin();
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) jsonResponse(false, 'ID tidak valid');

$trx = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE t.id=$id")->fetch_assoc();
if (!$trx) jsonResponse(false, 'Transaksi tidak ditemukan');

$items = [];
$res   = $conn->query("SELECT * FROM detail_transaksi WHERE transaksi_id=$id");
while ($r = $res->fetch_assoc()) $items[] = $r;

$trx['tanggal'] = date('d/m/Y H:i', strtotime($trx['tanggal']));

jsonResponse(true, '', ['transaksi' => $trx, 'items' => $items]);
?>
