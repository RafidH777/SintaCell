</div><!-- /page-content -->

<script>
function toggleProfilePanel() {
    const panel   = document.getElementById('profilePanel');
    const overlay = document.getElementById('profileOverlay');
    panel.classList.toggle('open');
    overlay.classList.toggle('open');
}
function closeProfilePanel() {
    document.getElementById('profilePanel').classList.remove('open');
    document.getElementById('profileOverlay').classList.remove('open');
}
function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;
        background:${type==='success'?'#1a0aff':'#dc3545'};
        color:white;padding:12px 20px;border-radius:10px;font-size:14px;
        box-shadow:0 4px 16px rgba(0,0,0,.2);`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
function openModal(id)  { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
});
</script>
<?= $extraScript ?? '' ?>
</body>
</html>
