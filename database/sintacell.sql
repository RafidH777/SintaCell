-- ============================================
-- DATABASE: sintacell
-- Sistem Informasi Sinta Cell
-- ============================================

CREATE DATABASE IF NOT EXISTS sintacell CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sintacell;

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(20),
    alamat TEXT,
    jabatan ENUM('pemilik','kasir','pengelola_stok') NOT NULL,
    id_pegawai VARCHAR(20),
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: presensi
-- ============================================
CREATE TABLE IF NOT EXISTS presensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu_masuk TIME,
    waktu_keluar TIME,
    jenis_shift VARCHAR(50),
    jenis_presensi ENUM('hadir','izin','sakit','alpha') DEFAULT 'hadir',
    foto_bukti VARCHAR(255),
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================
-- TABLE: kategori
-- ============================================
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: supplier
-- ============================================
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    alamat TEXT,
    telepon VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: barang
-- ============================================
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(200) NOT NULL,
    kategori_id INT,
    harga_beli DECIMAL(15,2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(15,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimal INT NOT NULL DEFAULT 10,
    satuan VARCHAR(20) DEFAULT 'pcs',
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
);

-- ============================================
-- TABLE: transaksi_penjualan
-- ============================================
CREATE TABLE IF NOT EXISTS transaksi_penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(30) NOT NULL UNIQUE,
    kasir_id INT NOT NULL,
    nama_pembeli VARCHAR(100),
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    bayar DECIMAL(15,2) DEFAULT 0,
    kembalian DECIMAL(15,2) DEFAULT 0,
    metode_bayar ENUM('tunai','transfer','qris') DEFAULT 'tunai',
    status ENUM('selesai','batal') DEFAULT 'selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kasir_id) REFERENCES users(id)
);

-- ============================================
-- TABLE: detail_transaksi
-- ============================================
CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    barang_id INT NOT NULL,
    nama_barang VARCHAR(200) NOT NULL,
    harga_satuan DECIMAL(15,2) NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi_penjualan(id),
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);

-- ============================================
-- TABLE: pembelian_barang
-- ============================================
CREATE TABLE IF NOT EXISTS pembelian_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pembelian VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT,
    pemilik_id INT NOT NULL,
    tanggal DATE NOT NULL,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','selesai','batal') DEFAULT 'selesai',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(id),
    FOREIGN KEY (pemilik_id) REFERENCES users(id)
);

-- ============================================
-- TABLE: detail_pembelian
-- ============================================
CREATE TABLE IF NOT EXISTS detail_pembelian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pembelian_id INT NOT NULL,
    barang_id INT NOT NULL,
    nama_barang VARCHAR(200) NOT NULL,
    harga_beli DECIMAL(15,2) NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (pembelian_id) REFERENCES pembelian_barang(id),
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);

-- ============================================
-- TABLE: log_stok
-- ============================================
CREATE TABLE IF NOT EXISTS log_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT NOT NULL,
    user_id INT,
    tipe ENUM('masuk','keluar','adjustment') NOT NULL,
    jumlah INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    referensi VARCHAR(50),
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================
-- TABLE: notifikasi
-- ============================================
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('stok_rendah','info','warning') DEFAULT 'info',
    barang_id INT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id)
);

-- ============================================
-- DATA: Users
-- Password semua: "password"
-- Hash: password_hash('password', PASSWORD_DEFAULT)
-- ============================================
INSERT INTO users (username, password, nama, email, telepon, jabatan, id_pegawai) VALUES
('admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sinta Pemilik',  'sinta@sintacell.com', '081234567890', 'pemilik',        'PGW001'),
('kasir01',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rama Kasir',     'rama@sintacell.com',  '081234567891', 'kasir',           'PGW002'),
('stok01',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Stok',      'budi@sintacell.com',  '081234567892', 'pengelola_stok',  'PGW003');

-- ============================================
-- DATA: Kategori
-- ============================================
INSERT INTO kategori (nama) VALUES
('Sembako'),
('Minuman'),
('Makanan Instan'),
('Protein'),
('Snack'),
('Lainnya');

-- ============================================
-- DATA: Supplier
-- ============================================
INSERT INTO supplier (nama, alamat, telepon) VALUES
('CV. Sumber Makmur',  'Jl. Raya Yogyakarta No. 10', '0274-123456'),
('PT. Mega Distribusi','Jl. Solo No. 25, Sleman',     '0274-654321'),
('UD. Berkah Jaya',    'Jl. Kaliurang Km 5',          '0274-111222');

-- ============================================
-- DATA: Barang
-- ============================================
INSERT INTO barang (kode, nama, kategori_id, harga_beli, harga_jual, stok, stok_minimal, satuan) VALUES
('BRG001', 'Beras Rose Brand 5 KG',       1, 50000, 60000,  8,  10, 'karung'),
('BRG002', 'Minyak Goreng Bimoli 2L',     1, 28000, 32000,  5,  10, 'botol'),
('BRG003', 'Telur Ayam 1 Kg',             4, 25000, 28000,  3,  15, 'kg'),
('BRG004', 'Gula Pasir 1 Kg',             1, 13000, 15000,  7,  10, 'kg'),
('BRG005', 'Tepung Terigu Segitiga 1 Kg', 1,  9000, 11000,  4,  10, 'kg'),
('BRG006', 'Indomie Goreng',              3,  3000,  3500, 50,  20, 'bungkus'),
('BRG007', 'Air Mineral Aqua 600ml',      2,  2500,  3000, 30,  20, 'botol'),
('BRG008', 'Sabun Mandi Lifebuoy',        6,  4000,  5000, 25,  10, 'pcs');

-- ============================================
-- DATA: Transaksi Sample
-- ============================================
INSERT INTO transaksi_penjualan (no_transaksi, kasir_id, nama_pembeli, tanggal, subtotal, diskon, total, bayar, kembalian, metode_bayar, status) VALUES
('TRX-20250101-001', 2, 'Bu Ani',  '2025-01-01 09:00:00', 75000, 0, 75000, 100000, 25000, 'tunai',    'selesai'),
('TRX-20250101-002', 2, 'Pak Budi','2025-01-01 10:30:00', 32000, 0, 32000,  32000,     0, 'transfer', 'selesai'),
('TRX-20250102-001', 2, 'Umum',    '2025-01-02 08:00:00', 60000, 0, 60000,  60000,     0, 'tunai',    'selesai');

INSERT INTO detail_transaksi (transaksi_id, barang_id, nama_barang, harga_satuan, jumlah, subtotal) VALUES
(1, 1, 'Beras Rose Brand 5 KG',   60000, 1, 60000),
(1, 7, 'Air Mineral Aqua 600ml',   3000, 5, 15000),
(2, 2, 'Minyak Goreng Bimoli 2L', 32000, 1, 32000),
(3, 1, 'Beras Rose Brand 5 KG',   60000, 1, 60000);

-- ============================================
-- DATA: Pembelian Sample
-- ============================================
INSERT INTO pembelian_barang (no_pembelian, supplier_id, pemilik_id, tanggal, total, status) VALUES
('PBL-20250101-001', 1, 1, '2025-01-01', 250000, 'selesai'),
('PBL-20250105-001', 2, 1, '2025-01-05', 180000, 'selesai');

INSERT INTO detail_pembelian (pembelian_id, barang_id, nama_barang, harga_beli, jumlah, subtotal) VALUES
(1, 1, 'Beras Rose Brand 5 KG',   50000, 5, 250000),
(2, 2, 'Minyak Goreng Bimoli 2L', 28000, 6, 168000),
(2, 4, 'Gula Pasir 1 Kg',         12000, 1,  12000);

-- ============================================
-- DATA: Notifikasi stok rendah awal
-- ============================================
INSERT INTO notifikasi (judul, pesan, tipe, barang_id) VALUES
('Stok Rendah: Beras Rose Brand 5 KG',       'Stok tersisa 8, di bawah minimum 10',  'stok_rendah', 1),
('Stok Rendah: Minyak Goreng Bimoli 2L',     'Stok tersisa 5, di bawah minimum 10',  'stok_rendah', 2),
('Stok Rendah: Telur Ayam 1 Kg',             'Stok tersisa 3, di bawah minimum 15',  'stok_rendah', 3),
('Stok Rendah: Gula Pasir 1 Kg',             'Stok tersisa 7, di bawah minimum 10',  'stok_rendah', 4),
('Stok Rendah: Tepung Terigu Segitiga 1 Kg', 'Stok tersisa 4, di bawah minimum 10',  'stok_rendah', 5);
