<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$isHashed           = $is_hashed ?? false;
$isSuper            = $is_super ?? (session()->get('role') === 'super_admin');
$mustChange         = $must_change ?? (bool) session()->get('must_change_password');
$isOwnAccount       = $is_own_account ?? ((string) session()->get('nip') === (string) ($user['nip'] ?? ''));
$canEditRoleAndName = $can_manage_user ?? ($isSuper && ! $isOwnAccount && ! $mustChange);
$showPopupAlert     = $show_popup_alert ?? false;
$alertMessage       = $alert_message ?? '';
$pwdRequired        = (! $canEditRoleAndName);
$pwdHash            = (string) ($user['password'] ?? '');
?>

<div class="card-dark" style="max-width:580px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-key header-icon"></i> <?= $mustChange ? 'Buat Password Baru' : ($canEditRoleAndName ? 'Edit Akun &amp; Password' : 'Ubah Password') ?> &mdash; <?= esc($user['nama'] ?? '') ?></h5>
        <?php if ($isHashed): ?>
            <span class="badge-custom badge-success"><i class="fas fa-lock"></i> Ter-enkripsi (Bcrypt)</span>
        <?php else: ?>
            <span class="badge-custom badge-warning"><i class="fas fa-unlock-alt"></i> Plain-Text (Belum Di-hash)</span>
        <?php endif; ?>
    </div>
    <div class="card-body-dark">
        <?php if ($mustChange || $alertMessage !== ''): ?>
        <div class="alert-dark" style="background:rgba(245,158,11,0.14);border:1px solid var(--warning);color:var(--warning);padding:14px 16px;border-radius:var(--radius-sm);margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
            <i class="fas fa-exclamation-triangle" style="font-size:18px;margin-top:2px;flex-shrink:0;"></i>
            <div>
                <div style="font-weight:700;margin-bottom:3px;">Wajib Membuat Password Baru</div>
                <div style="font-size:13px;line-height:1.45;">
                    <?= esc($alertMessage !== '' ? $alertMessage : 'Password akun Anda saat ini masih sama dengan NIP. Silakan masukkan password baru (minimal 6 karakter dan berbeda dari NIP) untuk melanjutkan.') ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <!-- Info Status Hash -->
        <div style="padding:12px 14px;border-radius:var(--radius-sm);background:var(--bg-secondary);border:1px solid var(--border);margin-bottom:18px;font-size:12.5px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div>
                    <strong style="color:var(--text-primary);"><i class="fas fa-shield-alt" style="color:var(--accent);margin-right:6px;"></i>Status Keamanan Password:</strong>
                    <?php if ($isHashed): ?>
                        <span style="color:var(--success);font-weight:600;">Sudah di-hash (Bcrypt)</span>
                    <?php else: ?>
                        <span style="color:var(--warning);font-weight:600;">Masih Plain-Text (Simpan untuk otomatis meng-hash)</span>
                    <?php endif; ?>
                </div>
                <?php if ($isHashed): ?>
                <code style="font-size:11px;color:var(--text-muted);background:var(--bg-primary);padding:2px 8px;border-radius:4px;"><?= esc(substr($pwdHash, 0, 22)) ?>...</code>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="<?= base_url('user/edit-password/' . urlencode($user['nip'] ?? '')) ?>">
            <?= csrf_field() ?>

            <?php if (! $canEditRoleAndName): ?>
            <div class="form-group">
                <label class="form-label-dark">NIP</label>
                <input type="text" class="form-control-dark" value="<?= esc($user['nip'] ?? '') ?>" readonly style="opacity:0.75;cursor:not-allowed;">
            </div>
            <?php else: ?>
            <div class="grid grid-2" style="margin-bottom:4px;">
                <div class="form-group">
                    <label class="form-label-dark">NIP</label>
                    <input type="text" class="form-control-dark" value="<?= esc($user['nip'] ?? '') ?>" readonly style="opacity:0.75;cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Role *</label>
                    <select name="role" class="form-control-dark" required>
                        <option value="super_admin" <?= ($user['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="admin"       <?= ($user['role'] ?? '') === 'admin'       ? 'selected' : '' ?>>Admin</option>
                        <option value="kepala"      <?= ($user['role'] ?? '') === 'kepala'      ? 'selected' : '' ?>>Kepala / Pimpinan</option>
                        <option value="pegawai"     <?= ($user['role'] ?? '') === 'pegawai'     ? 'selected' : '' ?>>Pegawai</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label-dark">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control-dark" value="<?= esc($user['nama'] ?? '') ?>" required>
            </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Password Baru <?= (! $pwdRequired) ? '<span style="color:var(--text-muted);font-weight:400;">(Opsional)</span>' : '*' ?></label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pwdNew" class="form-control-dark"
                               placeholder="<?= (! $pwdRequired) ? 'Kosongkan jika tetap' : 'Min. 6 karakter (berbeda dari NIP)' ?>"
                               style="padding-right:40px;" <?= $pwdRequired ? 'required autofocus' : '' ?> oninput="cekKekuatanPwd(this.value)">
                        <button type="button" onclick="togglePwd('pwdNew','eyeNew')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                            <i class="fas fa-eye" id="eyeNew"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Konfirmasi Password <?= (! $pwdRequired) ? '' : '*' ?></label>
                    <div style="position:relative;">
                        <input type="password" name="password_confirm" id="pwdConf" class="form-control-dark"
                               placeholder="Ulangi password baru"
                               style="padding-right:40px;" <?= $pwdRequired ? 'required' : '' ?>>
                        <button type="button" onclick="togglePwd('pwdConf','eyeConf')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                            <i class="fas fa-eye" id="eyeConf"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="pwdStrengthBox" style="display:none;margin-bottom:14px;">
                <div style="height:5px;border-radius:4px;background:var(--border);overflow:hidden;margin-bottom:4px;">
                    <div id="pwdStrengthBar" style="height:100%;width:0%;transition:width .25s,background .25s;"></div>
                </div>
                <small id="pwdStrengthText" style="font-size:11px;color:var(--text-muted);"></small>
            </div>

            <hr class="divider">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Password</button>
                    <?php if ($canEditRoleAndName): ?>
                    <button type="submit" name="reset_to_nip" value="1" class="btn-warning-dark" formnovalidate onclick="return confirm('Reset password akun ini kembali menjadi sama dengan NIP agar user wajib membuat password baru saat login berikutnya?');">
                        <i class="fas fa-undo"></i> Reset Password = NIP
                    </button>
                    <?php endif; ?>
                    <?php if (! $mustChange): ?>
                    <a href="<?= base_url('pegawai/profil/' . urlencode($user['nip'] ?? '')) ?>" class="btn-back">Batal</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
<?php if ($showPopupAlert): ?>
window.addEventListener('DOMContentLoaded', function() {
    alert(<?= json_encode($alertMessage !== '' ? $alertMessage : 'Silakan masukkan password baru terlebih dahulu untuk melanjutkan.', JSON_UNESCAPED_UNICODE) ?>);
    document.getElementById('pwdNew')?.focus();
});
<?php endif; ?>
function togglePwd(inputId, iconId) {
    var inp = document.getElementById(inputId);
    var ico = document.getElementById(iconId);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    if (ico) ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
function cekKekuatanPwd(v) {
    var box = document.getElementById('pwdStrengthBox');
    var bar = document.getElementById('pwdStrengthBar');
    var txt = document.getElementById('pwdStrengthText');
    if (!v) { box.style.display = 'none'; return; }
    box.style.display = 'block';
    var score = 0;
    if (v.length >= 6) score += 25;
    if (v.length >= 10) score += 25;
    if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score += 25;
    if (/[0-9]/.test(v) || /[^A-Za-z0-9]/.test(v)) score += 25;
    bar.style.width = score + '%';
    if (score <= 25) { bar.style.background = 'var(--danger)'; txt.textContent = 'Kekuatan: Lemah (akan tetap di-hash Bcrypt)'; }
    else if (score <= 50) { bar.style.background = 'var(--warning)'; txt.textContent = 'Kekuatan: Cukup (akan di-hash Bcrypt)'; }
    else if (score <= 75) { bar.style.background = 'var(--info)'; txt.textContent = 'Kekuatan: Baik (akan di-hash Bcrypt)'; }
    else { bar.style.background = 'var(--success)'; txt.textContent = 'Kekuatan: Sangat Kuat (akan di-hash Bcrypt)'; }
}
</script>
<?php $this->endSection() ?>
