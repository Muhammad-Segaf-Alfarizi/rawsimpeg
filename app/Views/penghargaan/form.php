<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="max-width:540px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-award header-icon"></i> <?= $aksi === 'tambah' ? 'Tambah Penghargaan' : 'Edit Penghargaan' ?></h5>
    </div>
    <div class="card-body-dark">
        <?php $actionUrl = $aksi === 'tambah' ? base_url('penghargaan/simpan/' . urlencode($nip)) : base_url('penghargaan/update/' . urlencode($nip) . '/' . ($dataEdit['id_penghargaan'] ?? 0)) ?>
        <form method="POST" action="<?= $actionUrl ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label-dark">Nama Penghargaan *</label>
                <input type="text" name="nama_penghargaan" class="form-control-dark" value="<?= esc($dataEdit['nama_penghargaan'] ?? '') ?>" required>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Tahun *</label>
                    <input type="number" name="tahun" class="form-control-dark" min="1970" max="2099" value="<?= esc($dataEdit['tahun'] ?? date('Y')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Pemberi</label>
                    <input type="text" name="pemberi" class="form-control-dark" value="<?= esc($dataEdit['pemberi'] ?? '') ?>">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan</button>
                <a href="<?= base_url('penghargaan/' . urlencode($nip)) ?>" class="btn-back">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
