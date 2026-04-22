# Sistem Informasi Sinta Cell

Sistem Informasi Penjualan dan Pengelolaan Stok berbasis PHP & MySQL.

---

## Cara Instalasi

### 1. Import Database
- Buka **phpMyAdmin** → `http://localhost/phpmyadmin`
- Klik **New** → buat database bernama `sintacell` → klik Create
- Klik database `sintacell` → tab **Import**
- Pilih file `database/sintacell.sql` → klik **Go**

### 2. Konfigurasi Database
Edit file `backend/config.php` jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // sesuaikan
define('DB_PASS', '');       // sesuaikan
define('DB_NAME', 'sintacell');
```

### 3. Letakkan di Web Server
- XAMPP : `C:/xampp/htdocs/sintacell/`
- Laragon: `C:/laragon/www/sintacell/`

### 4. Akses
```
http://localhost/sintacell/
```

---

## Akun Demo

| Username  | Password   | Role            |
|-----------|------------|-----------------|
| admin     | password   | Pemilik         |
| kasir01   | password   | Kasir           |
| stok01    | password   | Pengelola Stok  |

---

## Fitur Per Role

### Pemilik (admin)
- Dashboard statistik & grafik penjualan
- Kelola data barang (tambah, edit, hapus)
- Kelola stok & restock
- Proses transaksi penjualan
- Pembelian barang dari supplier
- Laporan penjualan, stok, keuangan
- Riwayat transaksi
- Presensi
- Edit profil

### Kasir (kasir01)
- Proses transaksi penjualan (POS)
- Cetak struk
- Riwayat order hari ini
- Presensi
- Edit profil

### Pengelola Stok (stok01)
- Kelola & monitor stok
- Lihat data barang
- Restock barang
- Presensi
- Edit profil

---

## Struktur Folder

```
sintacell/
├── index.php                  ← redirect ke login
├── login.php                  ← halaman login
├── README.md
├── database/
│   └── sintacell.sql          ← import ini ke MySQL
├── backend/
│   ├── config.php             ← konfigurasi DB & fungsi
│   ├── nav.php                ← menu navigasi per role
│   └── api/
│       ├── transaksi.php
│       ├── barang.php
│       ├── stok.php
│       ├── pembelian.php
│       └── riwayat.php
└── frontend/
    ├── css/
    │   └── style.css
    └── pages/
        ├── header.php
        ├── footer.php
        ├── logout.php
        ├── dashboard.php
        ├── transaksi.php
        ├── barang.php
        ├── stok.php
        ├── pembelian.php
        ├── laporan.php
        ├── riwayat.php
        ├── presensi.php
        └── profil.php
```
