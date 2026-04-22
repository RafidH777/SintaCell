<?php
// ============================================
// Konfigurasi Database - sesuaikan jika perlu
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sintacell');

// Koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die('<h3 style="color:red;font-family:Arial;">Koneksi database gagal: ' . $conn->connect_error . '<br>Pastikan MySQL sudah jalan dan database <b>sintacell</b> sudah diimport.</h3>');
}

// Session (cegah double start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Helper Functions
// ============================================
function sanitize($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

function generateKode($prefix, $table, $kolom, $conn) {
    $today  = date('Ymd');
    $like   = $prefix . '-' . $today . '-%';
    $result = $conn->query("SELECT COUNT(*) as cnt FROM `$table` WHERE `$kolom` LIKE '$like'");
    $row    = $result->fetch_assoc();
    $no     = str_pad($row['cnt'] + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . '-' . $today . '-' . $no;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Deteksi URL login secara otomatis berdasarkan letak file yang dipanggil
function getLoginUrl() {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // Cari posisi /sintacell/ di URL
    if (preg_match('#(/sintacell/)#i', $script, $m, PREG_OFFSET_CAPTURE)) {
        $base = substr($script, 0, $m[1][1] + strlen($m[1][0]));
        return $base . 'login.php';
    }
    // Fallback: naik sesuai kedalaman path
    $depth  = substr_count(trim($script, '/'), '/');
    $prefix = str_repeat('../', $depth);
    return $prefix . 'login.php';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit;
    }
}

function requireRole($roles) {
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['jabatan'], $roles)) {
        $loginUrl = getLoginUrl();
        http_response_code(403);
        die('<div style="font-family:Arial;padding:40px;"><h2>⛔ Akses Ditolak</h2><p>Role Anda tidak memiliki izin untuk halaman ini.</p><a href="' . $loginUrl . '">← Kembali ke Login</a></div>');
    }
}

function currentUser() {
    return [
        'id'       => $_SESSION['user_id']  ?? null,
        'nama'     => $_SESSION['nama']      ?? '',
        'jabatan'  => $_SESSION['jabatan']   ?? '',
        'username' => $_SESSION['username']  ?? '',
    ];
}
?>
