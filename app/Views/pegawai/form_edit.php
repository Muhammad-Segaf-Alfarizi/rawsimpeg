<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="max-width:860px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-user-edit header-icon"></i> Edit Data Pegawai</h5>
    </div>
    <div class="card-body-dark">
        <?php if (!empty($errors)): ?>
        <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('pegawai/edit/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <p class="section-title">Data Identitas</p>
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label-dark">Nama Lengkap *</label>
                    <input type="text" name="nama" class="form-control-dark" value="<?= esc($data['nama'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">NIP</label>
                    <input type="text" class="form-control-dark" value="<?= esc($nip) ?>" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Status Pegawai *</label>
                    <select name="status_pegawai" class="form-control-dark" required>
                        <?php foreach (['PNS', 'PPPK', 'PPPNP'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($data['status_pegawai'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-4">
                <div class="form-group">
                    <label class="form-label-dark">Golongan</label>
                    <input type="text" name="golongan" class="form-control-dark" value="<?= esc($data['golongan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control-dark" value="<?= esc($data['jabatan'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" class="form-control-dark" value="<?= esc($data['tanggal_lahir'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">TMT Awal *</label>
                    <input type="date" name="tmt_awal" class="form-control-dark" value="<?= esc($data['tmt_awal'] ?? '') ?>" required>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" class="form-control-dark">
                        <option value="">-- Pilih Pendidikan --</option>
                        <?php foreach (['SMP/ Setara'=>'SMP / Setara', 'SMA/ Setara'=>'SMA / Setara', 'D1'=>'D1', 'D2'=>'D2', 'D3'=>'D3', 'S1/ Setara'=>'S1 / Setara', 'S2'=>'S2', 'S3'=>'S3'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($data['pendidikan_terakhir'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Asal Sekolah/Perguruan Tinggi</label>
                    <input type="text" name="lulusan_pendidikan" class="form-control-dark" value="<?= esc($data['lulusan_pendidikan'] ?? '') ?>">
                </div>
            </div>

            <hr class="divider">
            <?php
            $isPppkEdit = ($data['status_pegawai'] ?? '') === 'PPPK';
            ?>
            <p class="section-title">Update Dokumen SK <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(biarkan kosong jika tidak diubah)</span></p>
            <div class="grid grid-3">
                <?php foreach ([
                    ['sk_cpns', $isPppkEdit ? 'SMPT Terakhir' : 'SK CPNS'],
                    ['sk_pns',  $isPppkEdit ? 'SK PPPK' : 'SK PNS'],
                    ['sk_tukin', $isPppkEdit ? 'SK Tukin ⚠️ (opsional)' : 'SK Tukin'],
                    ['sk_jabatan_terakhir', 'SK Jabatan Terakhir'],
                    ['sk_pak', $isPppkEdit ? 'Dokumen Kontrak Terakhir' : 'SK PAK'],
                    ['skp_terakhir', 'SKP Terakhir'],
                ] as [$fname, $flabel]):
                    $existing = $data[$fname] ?? null;
                ?>
                <div class="form-group">
                    <label class="form-label-dark"><?= $flabel ?></label>
                    <?php if ($existing): ?>
                    <p style="font-size:11px;margin-bottom:4px;"><a href="<?= esc($existing) ?>" target="_blank" style="color:var(--accent-light);">File saat ini</a></p>
                    <?php endif; ?>
                    <input type="file" name="<?= $fname ?>" class="form-control-dark" accept=".pdf,.doc,.docx,.jpg,.png" style="padding:8px;">
                </div>
                <?php endforeach; ?>
            </div>

            <hr class="divider">
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Perubahan</button>
                <a href="<?= base_url('dashboard/super-admin') ?>" class="btn-back">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
