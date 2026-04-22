<?php
$pageTitle = 'Pembelian Barang - Sinta Cell';
require_once __DIR__ . '/../../backend/config.php';
requireLogin();
requireRole('pemilik');

$supplierList = $conn->query("SELECT * FROM supplier ORDER BY nama");
$barangList   = $conn->query("SELECT b.*, k.nama kat FROM barang b LEFT JOIN kategori k ON b.kategori_id=k.id ORDER BY b.nama");
$riwayat      = $conn->query("SELECT p.*, s.nama suplier_nama, u.nama pemilik FROM pembelian_barang p LEFT JOIN supplier s ON p.supplier_id=s.id JOIN users u ON p.pemilik_id=u.id ORDER BY p.created_at DESC LIMIT 30");
$suppAll      = $conn->query("SELECT * FROM supplier ORDER BY nama");

$supplierArr = []; while($s=$supplierList->fetch_assoc()) $supplierArr[]=$s;
$barangArr   = []; while($b=$barangList->fetch_assoc()) $barangArr[]=$b;

include 'pemilik_header.php';
?>

<div class="pm-page">
    <div class="pm-page-title">
        <div><h1>🛒 Kelola Pembelian Stok</h1><p>Input pembelian barang dari supplier</p></div>
        <button class="pm-add-btn" onclick="openModal('modalDaftarSupplier')">🏭 Kelola Supplier</button>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-bottom:24px;">
        <div class="pm-table-card" style="padding:20px;">
            <div style="font-weight:800;font-size:15px;margin-bottom:16px;">📝 Input Pembelian Stok Baru</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                <div class="pm-form-group"><label class="pm-form-lbl req">Tanggal</label><input type="date" class="pm-form-ctrl" id="tglBeli" value="<?= date('Y-m-d') ?>"></div>
                <div class="pm-form-group"><label class="pm-form-lbl">Supplier</label>
                    <div style="display:flex;gap:6px;">
                        <select class="pm-form-ctrl" id="supplierId" style="flex:1;">
                            <option value="">-- Pilih Supplier --</option>
                            <?php foreach($supplierArr as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option><?php endforeach; ?>
                        </select>
                        <button type="button" class="pm-add-btn" style="padding:8px 12px;font-size:12px;" onclick="openModal('modalTambahSupplier')">+ Baru</button>
                    </div>
                </div>
            </div>
            <div style="border:1.5px solid #e5e7ff;border-radius:10px;padding:14px;margin-bottom:14px;">
                <div style="font-weight:700;font-size:13px;margin-bottom:10px;">+ Tambah Item Barang</div>
                <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end;">
                    <div class="pm-form-group" style="margin:0;"><label class="pm-form-lbl">Barang</label>
                        <select class="pm-form-ctrl" id="pilihBarang" onchange="isiHarga()" style="font-size:12px;">
                            <option value="">-- Pilih --</option>
                            <?php foreach($barangArr as $b): ?><option value="<?= $b['id'] ?>" data-harga="<?= $b['harga_beli'] ?>" data-nama="<?= htmlspecialchars($b['nama']) ?>"><?= htmlspecialchars($b['nama']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="pm-form-group" style="margin:0;"><label class="pm-form-lbl">Jumlah</label><input type="number" class="pm-form-ctrl" id="qtyBeli" value="1" min="1"></div>
                    <div class="pm-form-group" style="margin:0;"><label class="pm-form-lbl">Harga Beli</label><input type="number" class="pm-form-ctrl" id="hargaBeliInput" min="0"></div>
                    <button class="pm-add-btn" onclick="tambahItem()" style="height:38px;align-self:end;">+</button>
                </div>
            </div>
            <div id="itemList"></div>
        </div>

        <div class="pm-table-card" style="display:flex;flex-direction:column;">
            <div style="padding:16px 18px;border-bottom:1px solid #f0f2ff;font-weight:800;font-size:14px;">📋 Ringkasan</div>
            <div id="ringkasan" style="flex:1;padding:16px;text-align:center;color:#888;"><div style="font-size:36px;margin-bottom:8px;">📦</div><p>Belum ada item</p></div>
            <div style="padding:14px 18px;border-top:1px solid #f0f2ff;">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;font-size:13px;"><span>Total Item:</span><strong id="totalItemLbl">0</strong></div>
                <div style="display:flex;justify-content:space-between;font-size:14px;"><span>Total Biaya:</span><strong style="color:#1a0aff;" id="totalBiayaLbl">Rp 0</strong></div>
            </div>
            <div style="padding:14px 18px;">
                <button class="pm-add-btn" style="width:100%;justify-content:center;" id="btnSimpan" onclick="simpanPembelian()" disabled>💾 Simpan Stok Masuk</button>
            </div>
        </div>
    </div>

    <!-- Riwayat -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-weight:800;font-size:15px;">📊 Riwayat Pembelian</div>
        <button class="pm-print-btn" onclick="window.print()">🖨️ Print</button>
    </div>
    <div class="pm-table-card"><table class="pm-table">
        <thead><tr><th>No. Pembelian</th><th>Supplier</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
        <?php while($p=$riwayat->fetch_assoc()): ?>
        <tr>
            <td class="fw-bold" style="color:#1a0aff"><?= htmlspecialchars($p['no_pembelian']) ?></td>
            <td><?= htmlspecialchars($p['suplier_nama']??'Langsung') ?></td>
            <td class="fw-bold">Rp <?= number_format($p['total']) ?></td>
            <td><span class="badge-ok"><?= ucfirst($p['status']) ?></span></td>
            <td><?= date('d/m/Y',strtotime($p['tanggal'])) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table></div>
</div>

<!-- Modal Tambah Supplier -->
<div class="modal-overlay" id="modalTambahSupplier">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-hd"><div><div class="modal-hd-title">🏭 Tambah Supplier Baru</div></div><button class="modal-hd-close" onclick="closeModal('modalTambahSupplier')">✕</button></div>
        <div class="modal-bd">
            <div class="pm-form-group"><label class="pm-form-lbl req">Nama Supplier</label><input type="text" class="pm-form-ctrl" id="newSuppNama" placeholder="CV. Sumber Makmur"></div>
            <div class="pm-form-group"><label class="pm-form-lbl">Alamat</label><textarea class="pm-form-ctrl" id="newSuppAlamat" rows="2"></textarea></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="pm-form-group"><label class="pm-form-lbl">Telepon</label><input type="text" class="pm-form-ctrl" id="newSuppTlp"></div>
                <div class="pm-form-group"><label class="pm-form-lbl">Email</label><input type="email" class="pm-form-ctrl" id="newSuppEmail"></div>
            </div>
        </div>
        <div class="modal-ft"><button class="modal-btn-sec" onclick="closeModal('modalTambahSupplier')">Batal</button><button class="modal-btn-prim" onclick="simpanSupplierBaru()">💾 Simpan</button></div>
    </div>
</div>

<!-- Modal Daftar Supplier -->
<div class="modal-overlay" id="modalDaftarSupplier">
    <div class="modal-box" style="max-width:620px;">
        <div class="modal-hd"><div><div class="modal-hd-title">🏭 Kelola Supplier</div></div><button class="modal-hd-close" onclick="closeModal('modalDaftarSupplier')">✕</button></div>
        <div class="modal-bd" style="padding:0;">
            <div style="padding:12px 18px;border-bottom:1px solid #f0f2ff;display:flex;justify-content:flex-end;">
                <button class="pm-add-btn" style="font-size:12px;padding:8px 14px;" onclick="closeModal('modalDaftarSupplier');openModal('modalTambahSupplier')">+ Tambah Supplier</button>
            </div>
            <table class="pm-table" id="tabelSupplier">
                <thead><tr><th>Nama</th><th>Telepon</th><th>Alamat</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php while($s=$suppAll->fetch_assoc()): ?>
                <tr id="rowSupp<?= $s['id'] ?>">
                    <td class="fw-bold"><?= htmlspecialchars($s['nama']) ?></td>
                    <td><?= htmlspecialchars($s['telepon']??'-') ?></td>
                    <td><?= htmlspecialchars($s['alamat']??'-') ?></td>
                    <td><button class="act-btn-del" onclick="hapusSupplier(<?= $s['id'] ?>,'<?= addslashes($s['nama']) ?>')">🗑️</button></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-ft"><button class="modal-btn-sec" onclick="closeModal('modalDaftarSupplier')">Tutup</button></div>
    </div>
</div>

<script>
let items=[];
function isiHarga(){const o=document.getElementById('pilihBarang').options[document.getElementById('pilihBarang').selectedIndex];if(o.dataset.harga)document.getElementById('hargaBeliInput').value=o.dataset.harga;}
function tambahItem(){
    const sel=document.getElementById('pilihBarang');const id=sel.value;const nama=sel.options[sel.selectedIndex]?.text;
    const qty=parseInt(document.getElementById('qtyBeli').value);const harga=parseFloat(document.getElementById('hargaBeliInput').value);
    if(!id||!qty||!harga){showToast('Isi semua data!','error');return;}
    const ex=items.find(i=>i.barang_id==id);
    if(ex){ex.qty+=qty;ex.subtotal=ex.qty*ex.harga;}else items.push({barang_id:id,nama,qty,harga,subtotal:qty*harga});
    renderItems();
}
function hapusItem(i){items.splice(i,1);renderItems();}
function renderItems(){
    const list=document.getElementById('itemList');const ring=document.getElementById('ringkasan');
    if(!items.length){list.innerHTML='';ring.innerHTML='<div style="text-align:center;color:#888;padding:20px;"><div style="font-size:36px;">📦</div><p>Belum ada item</p></div>';document.getElementById('totalItemLbl').textContent='0';document.getElementById('totalBiayaLbl').textContent='Rp 0';document.getElementById('btnSimpan').disabled=true;return;}
    let html='<table class="pm-table" style="font-size:12px;"><thead><tr><th>Barang</th><th>Qty</th><th>Harga</th><th>Sub</th><th></th></tr></thead><tbody>';let total=0;
    items.forEach((i,idx)=>{html+=`<tr><td>${i.nama}</td><td>${i.qty}</td><td>Rp ${i.harga.toLocaleString('id-ID')}</td><td>Rp ${i.subtotal.toLocaleString('id-ID')}</td><td><button class="act-btn-del" onclick="hapusItem(${idx})" style="padding:4px 8px;">×</button></td></tr>`;total+=i.subtotal;});
    html+='</tbody></table>';
    list.innerHTML=html;ring.innerHTML=html;
    document.getElementById('totalItemLbl').textContent=items.length+' item';document.getElementById('totalBiayaLbl').textContent='Rp '+total.toLocaleString('id-ID');document.getElementById('btnSimpan').disabled=false;
}
async function simpanPembelian(){
    if(!items.length)return;
    const res=await fetch('../../backend/api/pembelian.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tanggal:document.getElementById('tglBeli').value,supplier_id:document.getElementById('supplierId').value,items})});
    const r=await res.json();
    if(r.success){showToast('Pembelian disimpan!');items=[];renderItems();setTimeout(()=>location.reload(),1000);}else showToast(r.message||'Gagal','error');
}
async function simpanSupplierBaru(){
    const nama=document.getElementById('newSuppNama').value.trim();
    if(!nama){showToast('Nama supplier wajib!','error');return;}
    const res=await fetch('../../backend/api/supplier.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'tambah',nama,alamat:document.getElementById('newSuppAlamat').value,telepon:document.getElementById('newSuppTlp').value,email:document.getElementById('newSuppEmail').value})});
    const r=await res.json();
    if(r.success){showToast('Supplier ditambahkan!');const sel=document.getElementById('supplierId');const opt=document.createElement('option');opt.value=r.data.id;opt.textContent=nama;opt.selected=true;sel.appendChild(opt);closeModal('modalTambahSupplier');}
    else showToast(r.message||'Gagal','error');
}
async function hapusSupplier(id,nama){
    if(!confirm(`Hapus supplier "${nama}"?`))return;
    const res=await fetch('../../backend/api/supplier.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'hapus',id})});
    const r=await res.json();
    if(r.success){showToast('Supplier dihapus');document.getElementById('rowSupp'+id)?.remove();document.querySelector(`#supplierId option[value="${id}"]`)?.remove();}
    else showToast(r.message||'Gagal','error');
}
</script>
<?php include 'pemilik_footer.php'; ?>
