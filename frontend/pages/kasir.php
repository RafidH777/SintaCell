<?php
$pageTitle = 'Kasir - Sinta Cell';
$extraHead = '<style>.page-content{overflow:hidden;}</style>';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik']);

$barangList = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE b.stok > 0 ORDER BY b.nama");
$riwayat    = $conn->query("SELECT t.*, u.nama kasir_nama,
    (SELECT GROUP_CONCAT(CONCAT(dt.nama_barang,' Rp',FORMAT(dt.harga_satuan,0)) SEPARATOR ', ') FROM detail_transaksi dt WHERE dt.transaksi_id=t.id) as detail_barang,
    (SELECT COUNT(*) FROM detail_transaksi WHERE transaksi_id=t.id) as jml_item
    FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id
    WHERE DATE(t.tanggal)=CURDATE() ORDER BY t.created_at DESC LIMIT 10");

$barangArr = [];
while ($b = $barangList->fetch_assoc()) $barangArr[] = $b;

$terjualHariIni = [];
$tRes = $conn->query("SELECT dt.barang_id, SUM(dt.jumlah) total FROM detail_transaksi dt JOIN transaksi_penjualan t ON dt.transaksi_id=t.id WHERE DATE(t.tanggal)=CURDATE() AND t.status='selesai' GROUP BY dt.barang_id");
while($tr = $tRes->fetch_assoc()) $terjualHariIni[$tr['barang_id']] = $tr['total'];

include 'kasir_header.php';
?>

<div class="kasir-layout">
    <!-- LEFT -->
    <div class="kasir-left">
        <!-- Riwayat Strip -->
        <div class="riwayat-label">Riwayat Order</div>
        <div class="riwayat-strip" id="riwayatStrip">
            <?php
            $rwArr = [];
            while ($rw = $riwayat->fetch_assoc()) $rwArr[] = $rw;
            if (empty($rwArr)):
            ?>
            <div style="color:var(--gray);font-size:13px;padding:10px 0;">Belum ada order hari ini</div>
            <?php else: foreach($rwArr as $rw): ?>
            <div class="riwayat-card">
                <div class="riwayat-card-img">🛒</div>
                <div class="riwayat-card-info">
                    <div class="riwayat-card-no"><?= htmlspecialchars($rw['no_transaksi']) ?></div>
                    <div class="riwayat-card-name fw-bold"><?= htmlspecialchars($rw['nama_pembeli'] ?: 'Umum') ?></div>
                    <div class="riwayat-card-total">Rp <?= number_format($rw['total']) ?>,-</div>
                    <div class="riwayat-card-desc"><?= htmlspecialchars(mb_substr($rw['detail_barang'] ?? '-', 0, 35)) ?></div>
                    <div class="riwayat-card-kasir">Kasir : <?= htmlspecialchars($rw['kasir_nama']) ?></div>
                </div>
                <div class="riwayat-arrow">›</div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Search only (no extra buttons) -->
        <div style="margin-bottom:14px;">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Cari Barang...." oninput="filterBarang(this.value)">
            </div>
        </div>

        <!-- Product Grid -->
        <div class="product-grid" id="productGrid">
            <?php foreach($barangArr as $b):
                $terjual   = $terjualHariIni[$b['id']] ?? 0;
                $stokWarna = $b['stok'] <= $b['stok_minimal'] ? 'color:var(--danger)' : '';
            ?>
            <div class="product-card" data-nama="<?= strtolower(htmlspecialchars($b['nama'])) ?>">
                <div class="product-card-img">
                    📦
                    <button class="product-card-info-btn" onclick="event.stopPropagation();" title="<?= htmlspecialchars($b['nama']) ?> | Rp <?= number_format($b['harga_jual']) ?>">i</button>
                </div>
                <div class="product-card-body">
                    <div class="product-card-name"><?= htmlspecialchars($b['nama']) ?></div>
                    <div class="product-card-stok" style="<?= $stokWarna ?>">Sisa Stok : <?= $b['stok'] ?></div>
                    <div class="product-card-footer">
                        <div class="product-card-price">Rp <?= number_format($b['harga_jual'], 0, ',', '.') ?></div>
                        <button class="product-card-add"
                            onclick="addToCart(<?= $b['id'] ?>,'<?= addslashes(htmlspecialchars($b['nama'])) ?>',<?= $b['harga_jual'] ?>,<?= $b['stok'] ?>)">+</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RIGHT: Detail Pesanan -->
    <div class="kasir-right">
        <div class="detail-header">
            <div class="detail-title">Detail <span>Pesanan</span></div>
            <div class="detail-meta">
                <span>Id Pesanan : <strong id="idPesananLabel">#<?= date('Hi') ?>P<?= rand(1,9) ?></strong></span>
                <span>Kasir : <?= htmlspecialchars($user['nama']) ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;margin-top:8px;">
                <span style="font-size:12px;font-weight:700;color:#555;white-space:nowrap;">Pembeli :</span>
                <input type="text" class="form-ctrl" id="namaPembeli"
                       placeholder="Ketik nama pembeli (opsional)"
                       style="font-size:12px;flex:1;margin:0;padding:5px 10px;"
                       oninput="document.getElementById('namaPembeliModal').value=this.value">
                <button class="detail-clear-btn" onclick="clearCart()">Bersihkan Pesanan</button>
            </div>
        </div>

        <div class="detail-items" id="orderBody">
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <p>Belum ada item dipilih</p>
            </div>
        </div>

        <!-- Diskon row - simple, no cek diskon button -->
        <div class="diskon-row">
            <span class="diskon-icon">🏷️</span>
            <input type="number" class="diskon-input" id="diskonInput" placeholder="Diskon (Rp)" value="0" min="0" oninput="updateTotal()">
        </div>

        <div class="detail-checkout">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray);margin-bottom:3px;">
                <span>Subtotal</span><span id="subtotalLabel">Rp 0</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray);margin-bottom:8px;">
                <span>Diskon</span><span id="diskonLabel">Rp 0</span>
            </div>
            <button class="checkout-btn" onclick="openCheckout()">
                <div class="cb-left">
                    <span class="cb-count" id="cbCount">0 Barang</span>
                    <span class="cb-total" id="cbTotal">Rp 0,-</span>
                </div>
                <div class="cb-right">Proses Transaksi 🛒</div>
            </button>
        </div>
    </div>
</div>

<!-- Modal Checkout -->
<div class="modal-overlay" id="modalCheckout">
    <div class="modal-box">
        <div class="modal-hd">
            <div><div class="modal-hd-title">💳 Proses Transaksi</div><div class="modal-hd-sub">Konfirmasi pembayaran</div></div>
            <button class="modal-hd-close" onclick="closeModal('modalCheckout')">✕</button>
        </div>
        <div class="modal-bd">
            <div class="form-group">
                <label class="form-lbl">Nama Pembeli</label>
                <input type="text" class="form-ctrl" id="namaPembeliModal" placeholder="Nama pembeli (opsional)">
            </div>
            <div class="form-group">
                <label class="form-lbl">Metode Pembayaran</label>
                <select class="form-ctrl" id="metodeBayar">
                    <option value="tunai">💵 Tunai</option>
                    <option value="transfer">🏦 Transfer</option>
                    <option value="qris">📱 QRIS</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-lbl">Jumlah Bayar</label>
                <input type="number" class="form-ctrl" id="bayarInput" placeholder="Masukkan jumlah bayar" oninput="updateKembalian()">
                <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:13px;">
                    <span>Kembalian:</span>
                    <strong id="kembalianLabel" style="color:var(--success)">Rp 0</strong>
                </div>
            </div>
            <div style="background:#f0f2ff;border-radius:10px;padding:12px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;"><span>Subtotal</span><span id="co-subtotal">Rp 0</span></div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;"><span>Diskon</span><span id="co-diskon">Rp 0</span></div>
                <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;"><span>Total</span><span id="co-total" style="color:var(--blue)">Rp 0</span></div>
            </div>
        </div>
        <div class="modal-ft">
            <button class="modal-btn-sec" onclick="closeModal('modalCheckout')">Batal</button>
            <button class="modal-btn-prim" onclick="prosesTransaksi()">✅ Konfirmasi</button>
        </div>
    </div>
</div>

<!-- Modal Struk -->
<div class="modal-overlay" id="modalStruk">
    <div class="modal-box" style="max-width:380px;">
        <div class="modal-hd">
            <div><div class="modal-hd-title">🧾 Struk Transaksi</div></div>
            <button class="modal-hd-close" onclick="closeModal('modalStruk')">✕</button>
        </div>
        <div class="modal-bd"><div class="struk-wrap" id="strukContent"></div></div>
        <div class="modal-ft">
            <button class="modal-btn-sec" onclick="closeModal('modalStruk')">Tutup</button>
            <button class="modal-btn-sec" onclick="window.print()">🖨️ Cetak</button>
            <button class="modal-btn-prim" onclick="transaksiBaruSetelahStruk()">Transaksi Baru</button>
        </div>
    </div>
</div>

<script>
let cart={};
function numFmt(n){return Math.round(n).toLocaleString('id-ID');}
function addToCart(id,nama,harga,stok){
    if(cart[id]){if(cart[id].qty>=stok){showToast('Stok tidak cukup!','error');return;}cart[id].qty++;}
    else cart[id]={id,nama,harga,qty:1,stok};
    renderCart();
}
function removeFromCart(id){if(cart[id]){cart[id].qty--;if(cart[id].qty<=0)delete cart[id];}renderCart();}
function clearCart(){cart={};renderCart();}
function renderCart(){
    const body=document.getElementById('orderBody');const keys=Object.keys(cart);
    if(!keys.length){body.innerHTML='<div class="empty-state"><div class="empty-state-icon">🛒</div><p>Belum ada item dipilih</p></div>';updateTotal();return;}
    let html='';
    keys.forEach(id=>{const i=cart[id];html+=`<div class="order-item-card">
        <div class="order-item-thumb">📦</div>
        <div class="order-item-info">
            <div class="order-item-name">${i.nama}</div>
            <div class="order-item-stok">Sisa Stok : ${i.stok-i.qty}</div>
            <div class="order-item-price">Rp ${numFmt(i.harga)},-</div>
        </div>
        <div class="qty-ctrl">
            <button class="qty-btn minus" onclick="removeFromCart(${id})">−</button>
            <span class="qty-num">${i.qty}</span>
            <button class="qty-btn" onclick="addToCart(${id},'${i.nama.replace(/'/g,"\\'")}',${i.harga},${i.stok})">+</button>
        </div>
    </div>`;});
    body.innerHTML=html;updateTotal();
}
function updateTotal(){
    let sub=0;Object.values(cart).forEach(i=>sub+=i.harga*i.qty);
    const diskon=parseFloat(document.getElementById('diskonInput').value)||0;
    const total=Math.max(0,sub-diskon);
    const count=Object.values(cart).reduce((a,i)=>a+i.qty,0);
    document.getElementById('subtotalLabel').textContent='Rp '+numFmt(sub);
    document.getElementById('diskonLabel').textContent='Rp '+numFmt(diskon);
    document.getElementById('cbCount').textContent=count+' Barang';
    document.getElementById('cbTotal').textContent='Rp '+numFmt(total)+',-';
    document.getElementById('co-subtotal').textContent='Rp '+numFmt(sub);
    document.getElementById('co-diskon').textContent='Rp '+numFmt(diskon);
    document.getElementById('co-total').textContent='Rp '+numFmt(total);
}
function updateKembalian(){
    const total=parseFloat(document.getElementById('co-total').textContent.replace(/[^\d]/g,''))||0;
    const bayar=parseFloat(document.getElementById('bayarInput').value)||0;
    document.getElementById('kembalianLabel').textContent='Rp '+numFmt(Math.max(0,bayar-total));
}
function openCheckout(){
    if(!Object.keys(cart).length){showToast('Keranjang kosong!','error');return;}
    // Auto-fill nama pembeli from inline input
    var nama = document.getElementById('namaPembeli').value;
    document.getElementById('namaPembeliModal').value = nama;
    updateTotal();openModal('modalCheckout');
}
function filterBarang(q){document.querySelectorAll('.product-card').forEach(c=>{c.style.display=c.dataset.nama.includes(q.toLowerCase())?'':'none';});}

async function prosesTransaksi(){
    if(!Object.keys(cart).length)return;
    const total=parseFloat(document.getElementById('co-total').textContent.replace(/[^\d]/g,''))||0;
    const bayar=parseFloat(document.getElementById('bayarInput').value)||total;
    const metode=document.getElementById('metodeBayar').value;
    if(metode==='tunai'&&bayar<total){showToast('Jumlah bayar kurang!','error');return;}
    const data={items:Object.values(cart),nama_pembeli:document.getElementById('namaPembeliModal').value,diskon:parseFloat(document.getElementById('diskonInput').value)||0,bayar,metode_bayar:metode};
    const res=await fetch('../../backend/api/transaksi.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const result=await res.json();
    if(result.success){closeModal('modalCheckout');tampilStruk(result.data);showToast('Transaksi berhasil!');}
    else showToast(result.message||'Gagal','error');
}
function tampilStruk(d){
    let items=d.items.map(i=>`<div class="struk-row"><span>${i.nama_barang} ×${i.jumlah}</span><span>Rp ${numFmt(i.subtotal)}</span></div>`).join('');
    document.getElementById('strukContent').innerHTML=`<div class="struk-hd"><div style="font-size:15px;font-weight:bold;">SINTA CELL</div><div style="font-size:10px;">Toko Handphone</div><div style="font-size:10px;">${d.no_transaksi}</div><div style="font-size:10px;">${d.tanggal}</div></div><div style="border-bottom:1px dashed #333;margin-bottom:6px;">${items}</div><div class="struk-row"><span>Subtotal</span><span>Rp ${numFmt(d.subtotal)}</span></div><div class="struk-row"><span>Diskon</span><span>Rp ${numFmt(d.diskon)}</span></div><div class="struk-row fw-bold"><span>Total</span><span>Rp ${numFmt(d.total)}</span></div><div class="struk-row"><span>Bayar</span><span>Rp ${numFmt(d.bayar)}</span></div><div class="struk-row"><span>Kembalian</span><span>Rp ${numFmt(d.kembalian)}</span></div><div class="struk-ft">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>`;
    openModal('modalStruk');
}
function transaksiBaruSetelahStruk(){closeModal('modalStruk');clearCart();document.getElementById('diskonInput').value=0;document.getElementById('bayarInput').value='';location.reload();}
</script>
<?php include 'kasir_footer.php'; ?>
