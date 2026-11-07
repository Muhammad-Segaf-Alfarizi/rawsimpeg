<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$isHashed = $is_hashed ?? false;
$isSuper  = $is_super ?? (session()->get('role') === 'super_admin');
$pwdHash  = (string) ($user['password'] ?? '');
?>

<div class="card-dark" style="max-width:580px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-key header-icon"></i> <?= $isSuper ? 'Edit Akun &amp; Password' : 'Ubah Password' ?> &mdash; <?= esc($user['nama'] ?? '') ?></h5>
        <?php if ($isHashed): ?>
            <span class="badge-custom badge-success"><i class="fas fa-lock"></i> Ter-enkripsi (Bcrypt)</span>
        <?php else: ?>
            <span class="badge-custom badge-warning"><i class="fas fa-unlock-alt"></i> Plain-Text (Belum Di-hash)</span>
        <?php endif; ?>
    </div>
    <div class="card-body-dark">
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

            <div class="grid grid-2" style="margin-bottom:4px;">
                <div class="form-group">
                    <label class="form-label-dark">NIP</label>
                    <input type="text" class="form-control-dark" value="<?= esc($user['nip'] ?? '') ?>" readonly style="opacity:0.75;cursor:not-allowed;">
                </div>
                <?php if ($isSuper): ?>
                <div class="form-group">
                    <label class="form-label-dark">Role *</label>
                    <select name="role" class="form-control-dark" required>
                        <option value="super_admin" <?= ($user['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="admin"       <?= ($user['role'] ?? '') === 'admin'       ? 'selected' : '' ?>>Admin</option>
                        <option value="kepala"      <?= ($user['role'] ?? '') === 'kepala'      ? 'selected' : '' ?>>Kepala / Pimpinan</option>
                        <option value="pegawai"     <?= ($user['role'] ?? '') === 'pegawai'     ? 'selected' : '' ?>>Pegawai</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label-dark">Role</label>
                    <input type="text" class="form-control-dark" value="<?= esc(ucwords(str_replace('_', ' ', $user['role'] ?? 'pegawai'))) ?>" readonly style="opacity:0.75;cursor:not-allowed;">
                </div>
                <?php endif; ?>
            </div>

            <?php if ($isSuper): ?>
            <div class="form-group">
                <label class="form-label-dark">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control-dark" value="<?= esc($user['nama'] ?? '') ?>" required>
            </div>
            <?php endif; ?>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Password Baru <?= $isSuper ? '<span style="color:var(--text-muted);font-weight:400;">(Opsional)</span>' : '*' ?></label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pwdNew" class="form-control-dark"
                               placeholder="<?= $isSuper ? 'Kosongkan jika tetap' : 'Min. 6 karakter' ?>"
                               style="padding-right:40px;" <?= $isSuper ? '' : 'required' ?> oninput="cekKekuatanPwd(this.value)">
                        <button type="button" onclick="togglePwd('pwdNew','eyeNew')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                            <i class="fas fa-eye" id="eyeNew"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Konfirmasi Password <?= $isSuper ? '' : '*' ?></label>
                    <div style="position:relative;">
                        <input type="password" name="password_confirm" id="pwdConf" class="form-control-dark"
                               placeholder="Ulangi password baru"
                               style="padding-right:40px;" <?= $isSuper ? '' : 'required' ?>>
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
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan &amp; Hash Password</button>
                    <a href="<?= base_url('pegawai/profil/' . urlencode($user['nip'] ?? '')) ?>" class="btn-back">Batal</a>
                </div>
            </div>
        </form>

        <?php if ($isSuper): ?>
        <hr class="divider">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding:12px 14px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);">
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><i class="fas fa-database" style="color:var(--accent);margin-right:6px;"></i>Migrasi Hash Seluruh User</div>
                <div style="font-size:11.5px;color:var(--text-muted);">Enkripsi otomatis seluruh password di tabel <code>user</code> yang masih berformat plain-text menjadi Bcrypt.</div>
            </div>
            <form method="POST" action="<?= base_url('user/hash-all') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-success-dark"><i class="fas fa-shield-alt"></i> Hash Semua Password</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
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
