<?php
$pageTitle = 'Presensi - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik']);

$userId = (int)$_SESSION['user_id'];
$user   = currentUser();
$sudah  = $conn->query("SELECT * FROM presensi WHERE user_id=$userId AND tanggal=CURDATE() LIMIT 1")->fetch_assoc();
$riwayat= $conn->query("SELECT * FROM presensi WHERE user_id=$userId ORDER BY tanggal DESC LIMIT 15");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $shift = sanitize($conn, $_POST['jenis_shift'] ?? 'pagi');
    $jenis = sanitize($conn, $_POST['jenis_presensi'] ?? 'hadir');
    $waktu = date('H:i:s');
    if ($sudah && !$sudah['waktu_keluar']) {
        $conn->query("UPDATE presensi SET waktu_keluar='$waktu' WHERE id={$sudah['id']}");
        $msg='Presensi keluar berhasil! Pukul '.$waktu;
    } elseif (!$sudah) {
        $conn->query("INSERT INTO presensi (user_id,tanggal,waktu_masuk,jenis_shift,jenis_presensi) VALUES ($userId,CURDATE(),'$waktu','$shift','$jenis')");
        $msg='Presensi masuk berhasil! Pukul '.$waktu;
    } else { $msg='Anda sudah presensi masuk dan keluar hari ini.'; }
    header('Location: presensi_kasir.php?msg='.urlencode($msg)); exit;
}

include 'kasir_header.php';
?>

<div class="lap-page" style="max-width:900px;">
    <h2 style="font-size:22px;font-weight:800;margin-bottom:20px;">📋 Presensi</h2>

    <?php if(isset($_GET['msg'])): ?>
    <div style="background:#e8f5e9;border:1px solid #c3e6cb;color:#28a745;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-weight:600;">
        ✅ <?= htmlspecialchars($_GET['msg']) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:360px 1fr;gap:20px;">
        <div class="presensi-card">
            <div style="text-align:center;margin-bottom:24px;">
                <div style="width:90px;height:90px;border-radius:50%;background:#ff8c00;color:white;display:inline-flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;border:4px solid var(--blue);">
                    <?= strtoupper(substr($user['nama'],0,1)) ?>
                </div>
                <div style="font-size:16px;font-weight:800;margin-top:10px;"><?= htmlspecialchars($user['nama']) ?></div>
                <div style="font-size:12px;color:var(--gray);">@<?= htmlspecialchars($user['username']) ?></div>
                <div style="font-size:20px;font-weight:800;color:var(--blue);margin-top:10px;" id="jamSekarang"></div>
                <div style="font-size:13px;color:var(--gray);"><?= date('l, d F Y') ?></div>
            </div>

            <?php if($sudah): ?>
            <div style="background:#e8f5e9;border-radius:12px;padding:12px;margin-bottom:16px;font-size:13px;">
                ✅ Masuk: <strong><?= $sudah['waktu_masuk'] ?></strong>
                <?php if($sudah['waktu_keluar']): ?>
                <br>✅ Keluar: <strong><?= $sudah['waktu_keluar'] ?></strong>
                <?php else: ?>
                <br>⏳ Belum presensi keluar
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-lbl">Jenis Shift</label>
                    <select class="form-ctrl" name="jenis_shift">
                        <option value="pagi">Shift Pagi (07.00-15.00)</option>
                        <option value="siang">Shift Siang (14.00-22.00)</option>
                        <option value="full">Full Day</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-lbl">Keterangan</label>
                    <select class="form-ctrl" name="jenis_presensi">
                        <option value="hadir">Hadir</option>
                        <option value="izin">Izin</option>
                        <option value="sakit">Sakit</option>
                    </select>
                </div>
                <button type="submit" style="width:100%;padding:14px;border-radius:12px;border:none;background:var(--blue);color:white;font-size:15px;font-weight:800;cursor:pointer;">
                    <?= $sudah&&!$sudah['waktu_keluar']?'🚪 Presensi Keluar':'📝 Presensi Masuk' ?>
                </button>
            </form>
        </div>

        <!-- Riwayat -->
        <div class="order-table-wrap">
            <table class="order-table">
                <thead><tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Shift</th><th>Status</th></tr></thead>
                <tbody>
                <?php while($p=$riwayat->fetch_assoc()):
                    $bc=['hadir'=>'#28a745','izin'=>'#ff8c00','sakit'=>'#6c757d','alpha'=>'#dc3545'][$p['jenis_presensi']]??'#6c757d';
                ?>
                <tr>
                    <td><?= date('d/m/Y',strtotime($p['tanggal'])) ?></td>
                    <td><?= $p['waktu_masuk']??'-' ?></td>
                    <td><?= $p['waktu_keluar']??'-' ?></td>
                    <td><?= ucfirst($p['jenis_shift']) ?></td>
                    <td><span style="background:<?= $bc ?>;color:white;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;"><?= ucfirst($p['jenis_presensi']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>function tick(){ document.getElementById('jamSekarang').textContent=new Date().toLocaleTimeString('id-ID'); } setInterval(tick,1000); tick();</script>
<?php include 'kasir_footer.php'; ?>
