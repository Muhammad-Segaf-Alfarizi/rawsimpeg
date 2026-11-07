<!-- Modal konfirmasi bertema (pengganti SweetAlert yang tidak dimuat di layout) -->
<div class="modal fade" id="pgConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="pgConfirmTitle"><i class="fas fa-question-circle" style="color:var(--accent);margin-right:8px;"></i>Konfirmasi</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="pgConfirmBody" style="font-size:13.5px;color:var(--text-secondary) !important;"></div>
            <div class="modal-footer">
                <button type="button" class="btn-info-dark" data-dismiss="modal" style="background:rgba(255,255,255,0.05);border-color:var(--border);color:var(--text-secondary);">Batal</button>
                <button type="button" class="btn-primary-dark" id="pgConfirmOk">Ya, lanjutkan</button>
            </div>
        </div>
    </div>
</div>
<div id="pgToastWrap"></div>
<div id="pgTip"></div>

<script>
/** Escape HTML untuk data dari server sebelum disisipkan ke template string. */
function h(v) {
    return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/** Toast bertema memakai class alert-dark dari layout. type: success | danger | warning */
function pgToast(message, type = 'success') {
    const icon = { success: 'fa-check-circle', danger: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' }[type] || 'fa-info-circle';
    const el = document.createElement('div');
    el.className = `alert-dark alert-${type}-dark`;
    el.innerHTML = `<i class="fas ${icon}"></i> <span>${h(message)}</span>`;
    document.getElementById('pgToastWrap').appendChild(el);
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3200);
}

/** Konfirmasi bertema. Mengembalikan Promise<boolean>. */
function pgConfirm(message, { title = 'Konfirmasi', okText = 'Ya, lanjutkan', danger = false } = {}) {
    return new Promise(resolve => {
        const $m = $('#pgConfirmModal');
        const ok = document.getElementById('pgConfirmOk');
        document.getElementById('pgConfirmTitle').innerHTML =
            `<i class="fas ${danger ? 'fa-exclamation-triangle' : 'fa-question-circle'}" style="color:${danger ? 'var(--danger)' : 'var(--accent)'};margin-right:8px;"></i>${h(title)}`;
        document.getElementById('pgConfirmBody').textContent = message;
        ok.textContent = okText;
        ok.className = danger ? 'btn-danger-dark' : 'btn-primary-dark';
        let answered = false;
        ok.onclick = () => { answered = true; $m.modal('hide'); resolve(true); };
        $m.off('hidden.bs.modal.pg').on('hidden.bs.modal.pg', () => { if (!answered) resolve(false); });
        $m.modal('show');
    });
}

/** POST AJAX ke endpoint CI4, mengembalikan JSON. */
function pgPost(url, data) {
    const body = data instanceof FormData ? data : new URLSearchParams(data || {});
    return fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body })
        .then(r => r.json())
        .catch(() => ({ status: 'error', message: 'Gagal menghubungi server.' }));
}

/** Badge peran (warna tema) */
function pgRoleBadge(peran) {
    const map = {
        'Dinas Luar': 'badge-info', 'Panitia Hotel': 'badge-primary', 'Panitia UPT': 'badge-primary',
        'Fasilitator': 'badge-success', 'Peserta Hotel': 'badge-warning', 'Peserta UPT': 'badge-warning'
    };
    return `<span class="badge-custom ${map[peran] || 'badge-secondary'}">${h(peran)}</span>`;
}

/** Hitung jumlah hari inklusif */
function pgHitungHari(m, s) {
    if (!m || !s) return 1;
    return Math.max(1, Math.round((new Date(s) - new Date(m)) / 86400000) + 1);
}
</script>

