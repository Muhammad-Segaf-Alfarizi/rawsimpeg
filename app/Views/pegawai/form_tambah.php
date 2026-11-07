<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="max-width:860px;margin:0 auto;">
    <div class="card-header-dark">
        <h5><i class="fas fa-user-plus header-icon"></i> Form Tambah Data Pegawai Baru</h5>
    </div>
    <div class="card-body-dark">
        <?php if (!empty($errors)): ?>
        <div class="alert-dark alert-danger-dark">
            <i class="fas fa-exclamation-circle"></i>
            <?= implode('<br>', $errors) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('pegawai/tambah') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <p class="section-title">Data Identitas Pegawai</p>
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label-dark">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="nama" class="form-control-dark" placeholder="Masukkan nama lengkap" required>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">NIP <span style="color:var(--danger);">*</span></label>
                    <input type="text" name="nip" class="form-control-dark" placeholder="Nomor Induk Pegawai" required>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Status Pegawai <span style="color:var(--danger);">*</span></label>
                    <select name="status_pegawai" class="form-control-dark" required>
                        <option value="">-- Pilih Status --</option>
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                        <option value="PPPNP">PPPNP</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-4">
                <div class="form-group">
                    <label class="form-label-dark">Golongan</label>
                    <input type="text" name="golongan" class="form-control-dark" placeholder="Contoh: III/a">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Jabatan</label>
                    <input type="text" name="jabatan" class="form-control-dark" placeholder="Jabatan fungsional">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" class="form-control-dark">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">TMT Awal <span style="color:var(--danger);">*</span></label>
                    <input type="date" name="tmt_awal" class="form-control-dark" required>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" class="form-control-dark">
                        <option value="">-- Pilih Pendidikan --</option>
                        <option value="SMP/ Setara">SMP / Setara</option>
                        <option value="SMA/ Setara">SMA / Setara</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="D3">D3</option>
                        <option value="S1/ Setara">S1 / Setara</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Asal Sekolah/Perguruan Tinggi</label>
                    <input type="text" name="lulusan_pendidikan" class="form-control-dark" placeholder="Nama institusi pendidikan">
                </div>
            </div>

            <hr class="divider">
            <p class="section-title">Unggah Dokumen SK <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(opsional, format PDF, maks 5MB)</span></p>
            <div class="grid grid-3">
                <?php foreach ([
                    ['sk_cpns', 'SK CPNS'], ['sk_pns', 'SK PNS'], ['sk_tukin', 'SK Tukin'],
                    ['sk_jabatan_terakhir', 'SK Jabatan Terakhir'], ['sk_pak', 'SK PAK'], ['skp_terakhir', 'SKP Terakhir'],
                ] as [$name, $label]): ?>
                <div class="form-group">
                    <label class="form-label-dark"><?= $label ?></label>
                    <input type="file" name="<?= $name ?>" class="form-control-dark" accept=".pdf" style="padding:8px;">
                </div>
                <?php endforeach; ?>
            </div>

            <hr class="divider">
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Data Pegawai</button>
                <a href="<?= base_url('dashboard/super-admin') ?>" class="btn-back">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
