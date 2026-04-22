<?php
$pageTitle = 'Pembelian Barang - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole('pemilik');

$supplierList = $conn->query("SELECT * FROM supplier ORDER BY nama");
$barangList   = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY b.nama");
$riwayat      = $conn->query("SELECT p.*, s.nama supplier_nama, u.nama pemilik FROM pembelian_barang p LEFT JOIN supplier s ON p.supplier_id=s.id JOIN users u ON p.pemilik_id=u.id ORDER BY p.created_at DESC LIMIT 20");

$supplierArr = []; while($s=$supplierList->fetch_assoc()) $supplierArr[]=$s;
$barangArr   = []; while($b=$barangList->fetch_assoc()) $barangArr[]=$b;

include 'header.php';
?>

<div class="page-title d-flex justify-between align-center">
    <div><h1>🛒 Kelola Pembelian Stok</h1><p>Input pembelian barang dari supplier</p></div>
    <button class="btn btn-outline" onclick="openModal('modalDaftarSupplier')">🏭 Kelola Supplier</button>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:20px;">
    <div class="card">
        <div class="card-header"><strong>📝 Input Pembelian Stok Baru</strong></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label required">Tanggal Pembelian</label>
                    <input type="date" class="form-control" id="tglBeli" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier</label>
                    <div style="display:flex;gap:6px;">
                        <select class="form-control" id="supplierId">
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach($supplierArr as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-primary" style="white-space:nowrap;" 
                                onclick="openModal('modalTambahSupplier')" title="Tambah supplier baru">
                            + Baru
                        </button>
                    </div>
                </div>
            </div>

            <div style="border:1px solid var(--gray-medium);border-radius:8px;padding:16px;margin-bottom:12px;">
                <div class="fw-bold mb-2">+ Tambah Item Barang</div>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Barang</label>
                        <select class="form-control" id="pilihBarang" onchange="isiHarga()">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach($barangArr as $b): ?>
                            <option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_beli'] ?>" data-nama="<?= htmlspecialchars($b['nama']) ?>">
                                <?= htmlspecialchars($b['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;"><label class="form-label">Jumlah</label><input type="number" class="form-control" id="qtyBeli" value="1" min="1"></div>
                    <div class="form-group" style="margin:0;"><label class="form-label">Harga Beli</label><input type="number" class="form-control" id="hargaBeliInput" min="0"></div>
                    <button class="btn btn-primary" onclick="tambahItem()" style="height:38px;align-self:end;">+</button>
                </div>
            </div>
            <div id="itemList"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>📋 Ringkasan Stok Masuk</strong></div>
        <div class="card-body">
            <div id="ringkasan" style="text-align:center;color:var(--gray);padding:20px;">
                <div style="font-size:36px;">📦</div><p>Belum ada item</p>
            </div>
            <div style="border-top:1px solid var(--gray-light);padding-top:10px;margin-top:10px;">
                <div class="d-flex justify-between mb-1"><span>Total Item:</span><strong id="totalItemLbl">0</strong></div>
                <div class="d-flex justify-between"><span>Total Biaya:</span><strong class="text-primary" id="totalBiayaLbl" style="font-size:16px;">Rp 0</strong></div>
            </div>
        </div>
        <div style="padding:16px;">
            <button class="btn btn-primary btn-block btn-lg" id="btnSimpan" onclick="simpanPembelian()" disabled>
                💾 Simpan Stok Masuk
            </button>
        </div>
    </div>
</div>

<!-- Riwayat Pembelian -->
<div class="card">
    <div class="card-header"><strong>📊 Riwayat Pembelian</strong></div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>No. Pembelian</th><th>Supplier</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
            <tbody>
            <?php while($p=$riwayat->fetch_assoc()): ?>
            <tr>
                <td class="fw-bold text-primary"><?= htmlspecialchars($p['no_pembelian']) ?></td>
                <td><?= htmlspecialchars($p['supplier_nama'] ?? 'Langsung') ?></td>
                <td>Rp <?= number_format($p['total']) ?></td>
                <td><span class="badge badge-success"><?= ucfirst($p['status']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== MODAL TAMBAH SUPPLIER ===== -->
<div class="modal-overlay" id="modalTambahSupplier">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <div><div class="modal-title">🏭 Tambah Supplier Baru</div><div class="modal-subtitle">Isi data supplier</div></div>
            <button class="modal-close" onclick="closeModal('modalTambahSupplier')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label required">Nama Supplier</label>
                <input type="text" class="form-control" id="newSupplierNama" placeholder="Contoh: CV. Sumber Makmur">
            </div>
            <div class="form-group">
                <label class="form-label">Alamat</label>
                <textarea class="form-control" id="newSupplierAlamat" rows="2" placeholder="Alamat lengkap supplier"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Telepon</label>
                    <input type="text" class="form-control" id="newSupplierTelepon" placeholder="0274-xxxxx">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="newSupplierEmail" placeholder="email@supplier.com">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalTambahSupplier')">Batal</button>
            <button class="btn btn-primary" onclick="simpanSupplierBaru()">💾 Simpan Supplier</button>
        </div>
    </div>
</div>

<!-- ===== MODAL DAFTAR & KELOLA SUPPLIER ===== -->
<div class="modal-overlay" id="modalDaftarSupplier">
    <div class="modal" style="max-width:650px;">
        <div class="modal-header">
            <div><div class="modal-title">🏭 Kelola Supplier</div><div class="modal-subtitle">Daftar semua supplier</div></div>
            <button class="modal-close" onclick="closeModal('modalDaftarSupplier')">✕</button>
        </div>
        <div class="modal-body" style="padding:0;">
            <div style="padding:14px 20px;border-bottom:1px solid var(--gray-light);display:flex;justify-content:flex-end;">
                <button class="btn btn-primary btn-sm" onclick="closeModal('modalDaftarSupplier');openModal('modalTambahSupplier')">+ Tambah Supplier Baru</button>
            </div>
            <div id="tabelSupplier">
                <table>
                    <thead><tr><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php
                    $suppAll = $conn->query("SELECT * FROM supplier ORDER BY nama");
                    while($s=$suppAll->fetch_assoc()):
                    ?>
                    <tr id="rowSupplier<?= $s['id'] ?>">
                        <td class="fw-bold"><?= htmlspecialchars($s['nama']) ?></td>
                        <td><?= htmlspecialchars($s['telepon'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['alamat'] ?? '-') ?></td>
                        <td>
                            <button class="btn btn-sm btn-danger" onclick="hapusSupplier(<?= $s['id'] ?>,'<?= addslashes($s['nama']) ?>')">🗑️</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalDaftarSupplier')">Tutup</button>
        </div>
    </div>
</div>

<script>
let items = [];

function isiHarga() {
    const o = document.getElementById('pilihBarang').options[document.getElementById('pilihBarang').selectedIndex];
    if (o.dataset.harga) document.getElementById('hargaBeliInput').value = o.dataset.harga;
}

function tambahItem() {
    const sel  = document.getElementById('pilihBarang');
    const id   = sel.value;
    const nama = sel.options[sel.selectedIndex]?.text;
    const qty  = parseInt(document.getElementById('qtyBeli').value);
    const harga= parseFloat(document.getElementById('hargaBeliInput').value);
    if (!id || !qty || !harga) { showToast('Isi semua data item!', 'error'); return; }
    const ex = items.find(i => i.barang_id == id);
    if (ex) { ex.qty += qty; ex.subtotal = ex.qty * ex.harga; }
    else items.push({ barang_id: id, nama, qty, harga, subtotal: qty * harga });
    renderItems();
}

function hapusItem(i) { items.splice(i, 1); renderItems(); }

function renderItems() {
    const list = document.getElementById('itemList');
    const ring = document.getElementById('ringkasan');
    if (!items.length) {
        list.innerHTML = '';
        ring.innerHTML = '<div style="text-align:center;color:var(--gray);padding:20px;"><div style="font-size:36px;">📦</div><p>Belum ada item</p></div>';
        document.getElementById('totalItemLbl').textContent = '0';
        document.getElementById('totalBiayaLbl').textContent = 'Rp 0';
        document.getElementById('btnSimpan').disabled = true;
        return;
    }
    let html = '<table><thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead><tbody>';
    let total = 0;
    items.forEach((i, idx) => {
        html += `<tr><td>${i.nama}</td><td>${i.qty}</td><td>Rp ${i.harga.toLocaleString('id-ID')}</td><td>Rp ${i.subtotal.toLocaleString('id-ID')}</td><td><button class="btn btn-sm btn-danger" onclick="hapusItem(${idx})">×</button></td></tr>`;
        total += i.subtotal;
    });
    html += '</tbody></table>';
    list.innerHTML = html;
    ring.innerHTML = html;
    document.getElementById('totalItemLbl').textContent = items.length + ' item';
    document.getElementById('totalBiayaLbl').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('btnSimpan').disabled = false;
}

async function simpanPembelian() {
    if (!items.length) return;
    const res = await fetch('../../backend/api/pembelian.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tanggal: document.getElementById('tglBeli').value,
            supplier_id: document.getElementById('supplierId').value,
            items
        })
    });
    const r = await res.json();
    if (r.success) { showToast('Pembelian disimpan!'); items = []; renderItems(); setTimeout(() => location.reload(), 1000); }
    else showToast(r.message || 'Gagal', 'error');
}

// ===== SUPPLIER FUNCTIONS =====
async function simpanSupplierBaru() {
    const nama    = document.getElementById('newSupplierNama').value.trim();
    const alamat  = document.getElementById('newSupplierAlamat').value.trim();
    const telepon = document.getElementById('newSupplierTelepon').value.trim();
    const email   = document.getElementById('newSupplierEmail').value.trim();

    if (!nama) { showToast('Nama supplier wajib diisi!', 'error'); return; }

    const res = await fetch('../../backend/api/supplier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'tambah', nama, alamat, telepon, email })
    });
    const r = await res.json();
    if (r.success) {
        showToast('Supplier berhasil ditambahkan!');
        // Tambah ke dropdown tanpa reload halaman
        const select = document.getElementById('supplierId');
        const option = document.createElement('option');
        option.value = r.data.id;
        option.textContent = nama;
        option.selected = true;
        select.appendChild(option);
        // Reset form & tutup modal
        document.getElementById('newSupplierNama').value = '';
        document.getElementById('newSupplierAlamat').value = '';
        document.getElementById('newSupplierTelepon').value = '';
        document.getElementById('newSupplierEmail').value = '';
        closeModal('modalTambahSupplier');
    } else {
        showToast(r.message || 'Gagal menyimpan supplier', 'error');
    }
}

async function hapusSupplier(id, nama) {
    if (!confirm(`Hapus supplier "${nama}"?`)) return;
    const res = await fetch('../../backend/api/supplier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'hapus', id })
    });
    const r = await res.json();
    if (r.success) {
        showToast('Supplier dihapus');
        document.getElementById('rowSupplier' + id)?.remove();
        // Hapus juga dari dropdown
        const opt = document.querySelector(`#supplierId option[value="${id}"]`);
        if (opt) opt.remove();
    } else {
        showToast(r.message || 'Gagal hapus', 'error');
    }
}
</script>
<?php include 'footer.php'; ?>
