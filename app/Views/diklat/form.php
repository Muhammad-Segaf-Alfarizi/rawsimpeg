<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="max-width:540px;margin:0 auto;">
    <div class="card-header-dark">
        <h5>
            <i class="fas fa-<?= $aksi === 'tambah' ? 'plus' : 'edit' ?> header-icon"></i>
            <?= $aksi === 'tambah' ? 'Tambah Diklat' : 'Edit Diklat' ?> &mdash;
            <span style="font-weight:400;color:var(--text-secondary);"><?= esc($pegawai['nama'] ?? '') ?></span>
        </h5>
    </div>
    <div class="card-body-dark">
        <?php $actionUrl = $aksi === 'tambah' ? base_url('diklat/simpan/' . urlencode($nip)) : base_url('diklat/update/' . urlencode($nip) . '/' . $dataEdit['id_daftar_diklat']) ?>
        <form method="POST" action="<?= $actionUrl ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label-dark">Nama Diklat / Pelatihan *</label>
                <input type="text" name="nama_diklat" class="form-control-dark"
                       placeholder="Masukkan nama diklat atau pelatihan"
                       value="<?= esc($dataEdit['nama_diklat'] ?? '') ?>" required autofocus>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan</button>
                <a href="<?= base_url('diklat/' . urlencode($nip)) ?>" class="btn-back">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
