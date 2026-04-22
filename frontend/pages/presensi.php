<?php
$pageTitle = 'Presensi - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$user   = currentUser();
$sudah  = $conn->query("SELECT * FROM presensi WHERE user_id=$userId AND tanggal=CURDATE() LIMIT 1")->fetch_assoc();
$riwayat= $conn->query("SELECT * FROM presensi WHERE user_id=$userId ORDER BY tanggal DESC LIMIT 20");

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $shift  = sanitize($conn, $_POST['jenis_shift']);
    $jenis  = sanitize($conn, $_POST['jenis_presensi']);
    $waktu  = date('H:i:s');
    if ($sudah && !$sudah['waktu_keluar']) {
        $conn->query("UPDATE presensi SET waktu_keluar='$waktu' WHERE id={$sudah['id']}");
        $msg='Presensi keluar berhasil!';
    } elseif (!$sudah) {
        $conn->query("INSERT INTO presensi (user_id,tanggal,waktu_masuk,jenis_shift,jenis_presensi) VALUES ($userId,CURDATE(),'$waktu','$shift','$jenis')");
        $msg='Presensi masuk berhasil!';
    } else { $msg='Anda sudah presensi masuk dan keluar hari ini.'; }
    header('Location: presensi.php?msg='.urlencode($msg)); exit;
}

include 'header.php';
?>
<div class="page-title"><h1>📋 Presensi</h1><p>Catat kehadiran kerja harian</p></div>
<?php if(isset($_GET['msg'])): ?><div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:400px 1fr;gap:20px;">
    <div class="card">
        <div class="card-header"><strong>Input Kehadiran</strong><span id="jamNow" style="font-size:16px;font-weight:bold;"></span></div>
        <div class="card-body">
            <?php if($sudah): ?>
            <div class="alert alert-success">✅ Masuk: <?= $sudah['waktu_masuk'] ?><?= $sudah['waktu_keluar']?' | ✅ Keluar: '.$sudah['waktu_keluar']:' (belum keluar)' ?></div>
            <?php endif; ?>
            <div style="text-align:center;margin-bottom:20px;">
                <div style="width:80px;height:80px;border-radius:50%;background:var(--purple);color:white;display:inline-flex;align-items:center;justify-content:center;font-size:32px;font-weight:bold;"><?= strtoupper(substr($user['nama'],0,1)) ?></div>
                <div class="fw-bold mt-1" style="font-size:16px;"><?= htmlspecialchars($user['nama']) ?></div>
                <div class="text-muted small"><?= $user['jabatan'] ?></div>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label class="form-label required">Jenis Shift</label>
                        <select class="form-control" name="jenis_shift" required>
                            <option value="pagi">Shift Pagi (07.00-15.00)</option>
                            <option value="siang">Shift Siang (14.00-22.00)</option>
                            <option value="full">Full Day</option>
                        </select></div>
                    <div class="form-group"><label class="form-label required">Keterangan</label>
                        <select class="form-control" name="jenis_presensi" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                        </select></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <?= $sudah&&!$sudah['waktu_keluar']?'🚪 Presensi Keluar':'📝 Presensi Masuk' ?>
                </button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><strong>📊 Riwayat Presensi</strong></div>
        <div class="table-responsive"><table>
            <thead><tr><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Shift</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($p=$riwayat->fetch_assoc()): $bc=['hadir'=>'badge-success','izin'=>'badge-warning','sakit'=>'badge-secondary','alpha'=>'badge-danger'][$p['jenis_presensi']]??'badge-secondary'; ?>
            <tr><td><?= date('d/m/Y',strtotime($p['tanggal'])) ?></td><td><?= $p['waktu_masuk']??'-' ?></td><td><?= $p['waktu_keluar']??'-' ?></td><td><span class="badge badge-primary"><?= ucfirst($p['jenis_shift']) ?></span></td><td><span class="badge <?= $bc ?>"><?= ucfirst($p['jenis_presensi']) ?></span></td></tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
    </div>
</div>
<script>function tick(){ document.getElementById('jamNow').textContent=new Date().toLocaleTimeString('id-ID'); } setInterval(tick,1000); tick();</script>
<?php include 'footer.php'; ?>
