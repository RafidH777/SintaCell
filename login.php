<?php
require_once __DIR__ . '/backend/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'kasir';
    if ($username && $password) {
        $result = $conn->query("SELECT * FROM users WHERE username='$username' AND jabatan='$role' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama']     = $user['nama'];
                $_SESSION['jabatan']  = $user['jabatan'];
                $_SESSION['email']    = $user['email'];
                if ($user['jabatan'] === 'kasir') header('Location: frontend/pages/kasir.php');
                elseif ($user['jabatan'] === 'pengelola_stok') header('Location: frontend/pages/stok_manager.php');
                else header('Location: frontend/pages/pemilik_barang.php');
                exit;
            } else { $error = 'Password salah.'; }
        } else { $error = 'Username tidak ditemukan atau role tidak sesuai.'; }
    } else { $error = 'Username dan password wajib diisi.'; }
}
$selectedRole = $_POST['role'] ?? 'kasir';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sinta Cell</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; min-height:100vh; display:flex; background:#f0f2ff; }

        .left {
            width: 49%;
            background: linear-gradient(135deg, #1a0aff 0%, #3320ff 60%, #0d00cc 100%);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px; position: relative; overflow: hidden;
        }
        .left::before { content:''; position:absolute; width:340px; height:340px; border-radius:50%; background:rgba(255,255,255,.06); bottom:-100px; left:-70px; }
        .left::after  { content:''; position:absolute; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.04); top:-50px; right:-30px; }
        .left-inner { z-index:1; text-align:center; color:white; }

        .avatar-box {
            width:110px; height:110px; background:rgba(255,255,255,.12);
            border-radius:20px; display:flex; align-items:center; justify-content:center;
            margin:0 auto 22px;
        }
        .avatar-box svg { width:60px; height:60px; fill:rgba(255,255,255,.7); }

        .brand-name { font-size:32px; font-weight:800; margin-bottom:6px; }
        .brand-sub  { font-size:15px; font-weight:600; margin-bottom:4px; opacity:.95; }
        .brand-desc { font-size:12px; opacity:.65; }

        .right { flex:1; display:flex; align-items:center; justify-content:center; padding:30px; background:#eef0ff; }
        .login-card { background:#fff; border-radius:20px; box-shadow:0 8px 40px rgba(26,10,255,.13), 0 2px 12px rgba(0,0,0,.08); padding:36px 36px 28px; width:100%; max-width:460px; }
        .login-box { width:100%; }

        .login-title { font-size:24px; font-weight:800; color:#1a0aff; text-align:center; margin-bottom:4px; }
        .login-sub   { font-size:12px; color:#888; text-align:center; margin-bottom:22px; }

        .role-label { font-size:12px; font-weight:700; color:#333; margin-bottom:8px; }
        .role-cards { display:flex; gap:8px; margin-bottom:20px; }
        .role-card  {
            flex:1; padding:12px 6px; border-radius:12px;
            background:#f5f5ff; border:2px solid transparent;
            cursor:pointer; text-align:center; transition:all .2s;
        }
        .role-card:hover { border-color:#c0c8ff; }
        .role-card.active { background:#1a0aff; color:white; border-color:#1a0aff; }
        .role-card .ricon { font-size:20px; margin-bottom:3px; }
        .role-card .rname { font-size:11px; font-weight:700; }

        .form-group { margin-bottom:14px; }
        .form-lbl   { font-size:12px; font-weight:600; color:#444; margin-bottom:5px; display:block; }
        .input-wrap { position:relative; }
        .input-wrap .ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); opacity:.35; width:16px; height:16px; }
        .form-input {
            width:100%; padding:11px 12px 11px 38px;
            border:1.5px solid #e5e7ff; border-radius:10px;
            font-size:13px; background:#f9f9ff; transition:all .2s; font-family:Arial,sans-serif;
        }
        .form-input:focus { outline:none; border-color:#1a0aff; background:#fff; box-shadow:0 0 0 3px rgba(26,10,255,.07); }
        .eye-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; opacity:.35; }

        .btn-login {
            width:100%; padding:12px; border-radius:12px; border:none;
            background:#1a0aff; color:white; font-size:14px; font-weight:700;
            cursor:pointer; transition:all .2s; margin-top:4px;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-login:hover { background:#1500e0; box-shadow:0 4px 16px rgba(26,10,255,.25); }

        .forgot { text-align:center; margin-top:12px; font-size:12px; }
        .forgot a { color:#1a0aff; text-decoration:none; font-weight:600; }
        .copyright { text-align:center; margin-top:16px; font-size:10px; color:#bbb; }

        .alert-error { background:#fff0f0; border:1px solid #ffcccc; color:#cc0000; padding:9px 12px; border-radius:8px; font-size:12px; margin-bottom:14px; }
        .demo-box { margin-top:14px; padding:10px 12px; background:#f0f4ff; border-radius:8px; font-size:11px; color:#555; line-height:1.8; }
        .demo-box strong { color:#1a0aff; }
    </style>
</head>
<body>
<div class="left">
    <div class="left-inner">
        <div class="avatar-box">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        <div class="brand-name">Sinta Cell</div>
        <div class="brand-sub">Sistem Manajemen Toko Klontong</div>
        <div class="brand-desc">Kelola transaksi, stok, dan laporan dengan mudah</div>
    </div>
</div>

<div class="right">
    <div class="login-card">
    <div class="login-box">
        <div class="login-title">Selamat Datang</div>
        <div class="login-sub">Pilih role dan masukkan kredensial Anda</div>

        <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="role-label">Pilih Role Akses</div>
            <div class="role-cards">
                <div class="role-card <?= $selectedRole==='kasir'?'active':'' ?>" onclick="selectRole('kasir',this)">
                    <div class="ricon">💰</div><div class="rname">Kasir</div>
                </div>
                <div class="role-card <?= $selectedRole==='pengelola_stok'?'active':'' ?>" onclick="selectRole('pengelola_stok',this)">
                    <div class="ricon">📦</div><div class="rname">Pengelola Stok</div>
                </div>
                <div class="role-card <?= $selectedRole==='pemilik'?'active':'' ?>" onclick="selectRole('pemilik',this)">
                    <div class="ricon">👑</div><div class="rname">Pemilik</div>
                </div>
            </div>
            <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selectedRole) ?>">

            <div class="form-group">
                <label class="form-lbl">Username / ID Pengguna</label>
                <div class="input-wrap">
                    <svg class="ico" viewBox="0 0 24 24" fill="#333"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                    <input type="text" name="username" class="form-input" id="userInput"
                           placeholder="Masukkan ID Kasir"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label class="form-lbl">Password</label>
                <div class="input-wrap">
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" name="password" class="form-input" id="passInput" placeholder="Masukkan password" required>
                    <button type="button" class="eye-btn" onclick="togglePass()">
                        <svg width="16" height="16" fill="none" stroke="#333" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <span id="btnIcon">💰</span> Masuk sebagai <span id="roleLabel">Kasir</span>
            </button>
            <div class="forgot"><a href="#">Lupa Password? Hubungi Admin</a></div>
        </form>

        <div class="demo-box">
            <strong>Akun Demo:</strong><br>
            Pemilik: <code>admin</code> &nbsp;/&nbsp; Kasir: <code>kasir01</code> &nbsp;/&nbsp; Stok: <code>stok01</code><br>
            Password: <code>password</code>
        </div>
        <div class="copyright">© 2024 Sinta Cell. All rights reserved.</div>
    </div>
    </div>
</div>
<script>
const roles={kasir:{label:'Kasir',icon:'💰',ph:'Masukkan ID Kasir'},pengelola_stok:{label:'Pengelola Stok',icon:'📦',ph:'Masukkan ID Pengelola Stok'},pemilik:{label:'Pemilik',icon:'👑',ph:'Masukkan ID Pemilik'}};
function selectRole(r,el){
    document.querySelectorAll('.role-card').forEach(c=>c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('roleInput').value=r;
    document.getElementById('roleLabel').textContent=roles[r].label;
    document.getElementById('btnIcon').textContent=roles[r].icon;
    document.getElementById('userInput').placeholder=roles[r].ph;
}
function togglePass(){const i=document.getElementById('passInput');i.type=i.type==='password'?'text':'password';}
const cur=document.getElementById('roleInput').value;
if(roles[cur]){document.getElementById('roleLabel').textContent=roles[cur].label;document.getElementById('btnIcon').textContent=roles[cur].icon;}
</script>
</body>
</html>
