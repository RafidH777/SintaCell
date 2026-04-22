<?php
$pageTitle = 'Transaksi - Sinta Cell';
$extraHead = '<style>.main-content{padding:14px;overflow:hidden;}</style>';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole(['kasir','pemilik']);

$barangList = $conn->query("SELECT b.*, k.nama kategori FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id WHERE b.stok > 0 ORDER BY b.nama");
$riwayat    = $conn->query("SELECT t.*, u.nama kasir FROM transaksi_penjualan t JOIN users u ON t.kasir_id=u.id WHERE DATE(t.tanggal)=CURDATE() ORDER BY t.created_at DESC LIMIT 15");

$barangArr = [];
while ($b = $barangList->fetch_assoc()) $barangArr[] = $b;

include 'header.php';
?>

<div class="transaksi-layout">

    <!-- KOLOM 1: Riwayat Hari Ini -->
    <div class="transaksi-col">
        <div class="transaksi-col-header">📜 Riwayat Hari Ini</div>
        <div class="transaksi-col-body">
            <?php while($trx=$riwayat->fetch_assoc()): ?>
            <div class="riwayat-item">
                <div class="no"><?= htmlspecialchars($trx['no_transaksi']) ?></div>
                <div class="buyer"><?= htmlspecialchars($trx['nama_pembeli']?:'Umum') ?></div>
                <div class="total">Rp <?= number_format($trx['total']) ?></div>
                <div class="no"><?= date('H:i',strtotime($trx['tanggal'])) ?> · <?= htmlspecialchars($trx['kasir']) ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- KOLOM 2: Pilih Barang -->
    <div class="transaksi-col">
        <div class="transaksi-col-header">
            <div class="search-wrapper" style="flex:1;margin:0;">
                <input type="text" id="searchBarang" placeholder="Cari barang..." oninput="filterBarang(this.value)" style="width:100%;padding:7px 12px 7px 34px;border:1px solid var(--gray-medium);border-radius:6px;font-size:13px;">
            </div>
        </div>
        <div class="transaksi-col-body">
            <div class="product-grid" id="productGrid">
                <?php foreach($barangArr as $b):
                    $warna = $b['stok'] <= $b['stok_minimal'] ? 'color:var(--danger)' : '';
                ?>
                <div class="product-card" data-nama="<?= strtolower(htmlspecialchars($b['nama'])) ?>">
                    <div style="font-size:26px;margin-bottom:4px;">📦</div>
                    <div class="product-name"><?= htmlspecialchars($b['nama']) ?></div>
                    <div class="product-stok" style="<?= $warna ?>">Stok: <?= $b['stok'] ?></div>
                    <div class="product-price">Rp <?= number_format($b['harga_jual']) ?></div>
                    <button class="add-btn" onclick="addToCart(<?= $b['id'] ?>,'<?= addslashes(htmlspecialchars($b['nama'])) ?>',<?= $b['harga_jual'] ?>,<?= $b['stok'] ?>)">+</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- KOLOM 3: Detail Pesanan -->
    <div class="transaksi-col">
        <div class="transaksi-col-header">
            🧾 Detail Pesanan
            <button class="btn btn-sm btn-secondary" onclick="clearCart()">Bersihkan</button>
        </div>
        <div class="transaksi-col-body" id="orderBody">
            <div style="text-align:center;color:var(--gray);padding:30px 0;">
                <div style="font-size:40px;">🛒</div><p>Belum ada item</p>
            </div>
        </div>
        <div style="padding:12px;border-top:1px solid var(--gray-light);">
            <input type="text" class="form-control mb-1" id="namaPembeli" placeholder="Nama pembeli (opsional)" style="margin-bottom:8px;">
            <div class="d-flex justify-between mb-1"><span>Subtotal</span><strong id="subtotalLabel">Rp 0</strong></div>
            <div class="d-flex justify-between align-center mb-1">
                <span>Diskon (Rp)</span>
                <input type="number" id="diskonInput" value="0" min="0" oninput="updateTotal()"
                       style="width:90px;padding:4px 8px;border:1px solid var(--gray-medium);border-radius:4px;font-size:12px;">
            </div>
            <div class="d-flex justify-between mb-2" style="font-size:16px;border-top:1px solid var(--gray-light);padding-top:8px;">
                <strong>Total</strong><strong id="totalLabel" class="text-primary">Rp 0</strong>
            </div>
            <select class="form-control mb-1" id="metodeBayar" style="margin-bottom:8px;">
                <option value="tunai">💵 Tunai</option>
                <option value="transfer">🏦 Transfer</option>
                <option value="qris">📱 QRIS</option>
            </select>
            <input type="number" class="form-control" id="bayarInput" placeholder="Jumlah bayar" oninput="updateKembalian()" style="margin-bottom:4px;">
            <div class="d-flex justify-between mb-2">
                <span class="small text-muted">Kembalian:</span>
                <span class="fw-bold text-success" id="kembalianLabel">Rp 0</span>
            </div>
            <button class="btn btn-primary btn-block btn-lg" onclick="prosesTransaksi()">
                ✅ Proses · <span id="totalBtn">Rp 0</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Struk -->
<div class="modal-overlay" id="modalStruk">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <div><div class="modal-title">🧾 Struk Transaksi</div><div class="modal-subtitle">Transaksi berhasil</div></div>
            <button class="modal-close" onclick="closeModal('modalStruk')">✕</button>
        </div>
        <div class="modal-body">
            <div class="struk-print" id="strukContent" style="margin:0 auto;border:1px dashed #ccc;padding:16px;border-radius:4px;"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalStruk')">Tutup</button>
            <button class="btn btn-outline" onclick="window.print()">🖨️ Cetak</button>
            <button class="btn btn-primary" onclick="transaksiBaruSetelahStruk()">Transaksi Baru</button>
        </div>
    </div>
</div>

<script>
let cart = {};

function numFmt(n){ return Math.round(n).toLocaleString('id-ID'); }

function addToCart(id, nama, harga, stok){
    if(cart[id]){
        if(cart[id].qty >= stok){ showToast('Stok tidak cukup!','error'); return; }
        cart[id].qty++;
    } else {
        cart[id] = {id, nama, harga, qty:1, stok};
    }
    renderCart();
}

function removeFromCart(id){
    if(cart[id]){ cart[id].qty--; if(cart[id].qty<=0) delete cart[id]; }
    renderCart();
}

function clearCart(){ cart={}; renderCart(); }

function renderCart(){
    const body = document.getElementById('orderBody');
    const keys = Object.keys(cart);
    if(!keys.length){
        body.innerHTML='<div style="text-align:center;color:var(--gray);padding:30px 0;"><div style="font-size:40px;">🛒</div><p>Belum ada item</p></div>';
        updateTotal(); return;
    }
    let html='';
    keys.forEach(id=>{
        const i=cart[id];
        html+=`<div class="order-item">
            <div class="item-thumb">📦</div>
            <div class="item-info">
                <div class="item-name">${i.nama}</div>
                <div class="item-price">Rp ${numFmt(i.harga)} × ${i.qty} = Rp ${numFmt(i.harga*i.qty)}</div>
            </div>
            <div class="qty-control">
                <button class="qty-btn" onclick="removeFromCart(${id})">−</button>
                <span class="qty-num">${i.qty}</span>
                <button class="qty-btn" onclick="addToCart(${id},'${i.nama.replace(/'/g,"\\'")}',${i.harga},${i.stok})">+</button>
            </div>
        </div>`;
    });
    body.innerHTML=html;
    updateTotal();
}

function updateTotal(){
    let sub=0; Object.values(cart).forEach(i=>sub+=i.harga*i.qty);
    const diskon=parseFloat(document.getElementById('diskonInput').value)||0;
    const total=Math.max(0,sub-diskon);
    document.getElementById('subtotalLabel').textContent='Rp '+numFmt(sub);
    document.getElementById('totalLabel').textContent='Rp '+numFmt(total);
    document.getElementById('totalBtn').textContent='Rp '+numFmt(total);
    updateKembalian();
}

function updateKembalian(){
    const total=parseFloat(document.getElementById('totalLabel').textContent.replace(/[^\d]/g,''))||0;
    const bayar=parseFloat(document.getElementById('bayarInput').value)||0;
    document.getElementById('kembalianLabel').textContent='Rp '+numFmt(Math.max(0,bayar-total));
}

function filterBarang(q){
    document.querySelectorAll('.product-card').forEach(c=>{
        c.style.display=c.dataset.nama.includes(q.toLowerCase())?'':'none';
    });
}

async function prosesTransaksi(){
    if(!Object.keys(cart).length){ showToast('Keranjang kosong!','error'); return; }
    const total=parseFloat(document.getElementById('totalLabel').textContent.replace(/[^\d]/g,''))||0;
    const bayar=parseFloat(document.getElementById('bayarInput').value)||total;
    const metode=document.getElementById('metodeBayar').value;
    if(metode==='tunai'&&bayar<total){ showToast('Jumlah bayar kurang!','error'); return; }

    const data={
        items:Object.values(cart),
        nama_pembeli:document.getElementById('namaPembeli').value,
        diskon:parseFloat(document.getElementById('diskonInput').value)||0,
        bayar, metode_bayar:metode
    };
    const res=await fetch('../../backend/api/transaksi.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const result=await res.json();
    if(result.success){ tampilStruk(result.data); showToast('Transaksi berhasil!'); }
    else showToast(result.message||'Gagal','error');
}

function tampilStruk(d){
    let items=d.items.map(i=>`<div class="struk-row"><span>${i.nama_barang} ×${i.jumlah}</span><span>Rp ${numFmt(i.subtotal)}</span></div>`).join('');
    document.getElementById('strukContent').innerHTML=`
        <div class="struk-header"><div style="font-size:16px;font-weight:bold;">SINTA CELL</div>
        <div style="font-size:11px;">Jl. Yogyakarta</div>
        <div style="font-size:10px;">${d.no_transaksi}</div><div style="font-size:10px;">${d.tanggal}</div></div>
        <div style="border-bottom:1px dashed #333;margin-bottom:6px;">${items}</div>
        <div class="struk-row"><span>Subtotal</span><span>Rp ${numFmt(d.subtotal)}</span></div>
        <div class="struk-row"><span>Diskon</span><span>Rp ${numFmt(d.diskon)}</span></div>
        <div class="struk-row fw-bold"><span>Total</span><span>Rp ${numFmt(d.total)}</span></div>
        <div class="struk-row"><span>Bayar</span><span>Rp ${numFmt(d.bayar)}</span></div>
        <div class="struk-row"><span>Kembalian</span><span>Rp ${numFmt(d.kembalian)}</span></div>
        <div class="struk-footer">TERIMA KASIH ATAS KUNJUNGAN ANDA</div>`;
    openModal('modalStruk');
}

function transaksiBaruSetelahStruk(){
    closeModal('modalStruk');
    clearCart();
    document.getElementById('namaPembeli').value='';
    document.getElementById('diskonInput').value=0;
    document.getElementById('bayarInput').value='';
    location.reload();
}
</script>
<?php include 'footer.php'; ?>
