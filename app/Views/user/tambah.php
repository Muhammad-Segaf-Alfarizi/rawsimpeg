<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="max-width:560px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-user-shield header-icon"></i> Tambah Akun User</h5>
        <span class="badge-custom badge-success"><i class="fas fa-lock"></i> Auto Bcrypt Hash</span>
    </div>
    <div class="card-body-dark">
        <?php if (!empty($errors)): ?>
        <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('user/tambah') ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label-dark">NIP *</label>
                <input type="text" name="nip" class="form-control-dark" placeholder="NIP pegawai" value="<?= esc(old('nip')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label-dark">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control-dark" placeholder="Nama lengkap" value="<?= esc(old('nama')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label-dark">Role *</label>
                <select name="role" class="form-control-dark" required>
                    <option value="">-- Pilih Role --</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="kepala">Kepala / Pimpinan</option>
                    <option value="pegawai">Pegawai</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label-dark">Password *</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="pwdTambah" class="form-control-dark" placeholder="Min. 6 karakter" style="padding-right:42px;" required>
                    <button type="button" onclick="togglePwd('pwdTambah','eyeTambah')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                        <i class="fas fa-eye" id="eyeTambah"></i>
                    </button>
                </div>
                <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:6px;">
                    <i class="fas fa-shield-alt" style="color:var(--success);"></i> Password otomatis di-hash menggunakan algoritma <code>Bcrypt (PASSWORD_DEFAULT)</code> sebelum disimpan ke database.
                </small>
            </div>
            <hr class="divider">
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan User</button>
                <a href="<?= base_url('dashboard/super-admin') ?>" class="btn-back">Batal</a>
            </div>
        </form>
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
</script>
<?php $this->endSection() ?>
