<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$modelOpts   = $modelOptions ?? ['Luring' => 'Luring (Tatap Muka)', 'Daring' => 'Daring (Online)', 'Blended' => 'Blended (Luring & Daring)'];
$valNama     = old('nama_diklat', $dataEdit['nama_diklat'] ?? '');
$valMulai    = old('tanggal_mulai', (! empty($dataEdit['tanggal_mulai']) && $dataEdit['tanggal_mulai'] !== '0000-00-00') ? $dataEdit['tanggal_mulai'] : '');
$valSelesai  = old('tanggal_selesai', (! empty($dataEdit['tanggal_selesai']) && $dataEdit['tanggal_selesai'] !== '0000-00-00') ? $dataEdit['tanggal_selesai'] : '');
$valTempat   = old('tempat_pelaksanaan', $dataEdit['tempat_pelaksanaan'] ?? '');
$valModel    = old('model_pelatihan', $dataEdit['model_pelatihan'] ?? '');
$existingUrl = $dataEdit['file_sertifikat'] ?? '';
$actionUrl   = $aksi === 'tambah'
    ? base_url('diklat/simpan/' . urlencode($nip))
    : base_url('diklat/update/' . urlencode($nip) . '/' . $dataEdit['id_daftar_diklat']);
?>

<div class="card-dark" style="max-width:680px;margin:0 auto;">
    <div class="card-header-dark">
        <h5>
            <i class="fas fa-<?= $aksi === 'tambah' ? 'plus' : 'edit' ?> header-icon"></i>
            <?= $aksi === 'tambah' ? 'Tambah Diklat / Pelatihan' : 'Edit Diklat / Pelatihan' ?> &mdash;
            <span style="font-weight:400;color:var(--text-secondary);"><?= esc($pegawai['nama'] ?? '') ?></span>
        </h5>
    </div>
    <div class="card-body-dark">
        <form method="POST" action="<?= $actionUrl ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label-dark">Nama Diklat / Pelatihan <span style="color:var(--danger);">*</span></label>
                <input type="text" name="nama_diklat" class="form-control-dark"
                       placeholder="Masukkan nama diklat atau pelatihan"
                       value="<?= esc($valNama) ?>" required autofocus>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Tanggal Mulai Pelatihan</label>
                    <input type="date" name="tanggal_mulai" id="tglMulaiDiklat" class="form-control-dark"
                           value="<?= esc($valMulai) ?>" onchange="syncTglSelesaiDiklat()">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Tanggal Selesai Pelatihan</label>
                    <input type="date" name="tanggal_selesai" id="tglSelesaiDiklat" class="form-control-dark"
                           value="<?= esc($valSelesai) ?>">
                </div>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Tempat Pelaksanaan</label>
                    <input type="text" name="tempat_pelaksanaan" class="form-control-dark"
                           placeholder="Contoh: Hotel Novotel / BBGTK / Zoom Meeting"
                           value="<?= esc($valTempat) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Model Pelatihan</label>
                    <select name="model_pelatihan" class="form-control-dark">
                        <option value="">-- Pilih Model Pelatihan --</option>
                        <?php foreach ($modelOpts as $mKey => $mLabel): ?>
                        <option value="<?= esc($mKey) ?>" <?= $valModel === $mKey ? 'selected' : '' ?>><?= esc($mLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label-dark">
                    Sertifikat Pelatihan <span style="color:var(--text-muted);font-weight:400;">(Opsional &mdash; PDF, JPG, PNG maks. 5 MB)</span>
                </label>

                <?php if (! empty($existingUrl)): ?>
                <div style="margin-bottom:10px;padding:10px 12px;background:rgba(99,102,241,0.1);border:1px solid var(--border-accent);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <span style="font-size:12.5px;color:var(--text-secondary);">
                        <i class="fas fa-certificate" style="color:var(--success);margin-right:6px;"></i>Sertifikat tersimpan di Google Drive
                    </span>
                    <a href="<?= esc($existingUrl) ?>" target="_blank" class="btn-info-dark btn-sm-custom">
                        <i class="fas fa-external-link-alt"></i> Lihat Sertifikat Saat Ini
                    </a>
                </div>
                <?php endif; ?>

                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <input type="file" name="file_sertifikat" id="inputFileSertifikat"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;" onchange="previewSertifikatForm(this)">
                    <button type="button" class="btn-info-dark" onclick="document.getElementById('inputFileSertifikat').click()">
                        <i class="fas fa-cloud-upload-alt"></i> <?= ! empty($existingUrl) ? 'Ganti File Sertifikat' : 'Pilih File Sertifikat' ?>
                    </button>
                    <span id="namaFileSertifikat" style="font-size:12.5px;color:var(--text-muted);">
                        Belum ada file dipilih (boleh dikosongkan)
                    </span>
                    <button type="button" id="btnResetSertifikat" class="btn-danger-dark btn-sm-custom" style="display:none;" onclick="batalPilihSertifikat()">
                        <i class="fas fa-times"></i> Batal Pilih
                    </button>
                </div>
            </div>

            <hr class="divider">
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan</button>
                <a href="<?= base_url('diklat/' . urlencode($nip)) ?>" class="btn-back">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
function syncTglSelesaiDiklat() {
    var m = document.getElementById('tglMulaiDiklat');
    var s = document.getElementById('tglSelesaiDiklat');
    if (m && s && m.value && (!s.value || s.value < m.value)) {
        s.value = m.value;
    }
}

function previewSertifikatForm(input) {
    var span = document.getElementById('namaFileSertifikat');
    var btnReset = document.getElementById('btnResetSertifikat');
    if (input.files && input.files[0]) {
        var f = input.files[0];
        var mb = (f.size / (1024 * 1024)).toFixed(2);
        span.textContent = f.name + ' (' + mb + ' MB)';
        span.style.color = 'var(--success)';
        span.style.fontWeight = '600';
        btnReset.style.display = 'inline-flex';
    } else {
        batalPilihSertifikat();
    }
}

function batalPilihSertifikat() {
    var input = document.getElementById('inputFileSertifikat');
    var span = document.getElementById('namaFileSertifikat');
    var btnReset = document.getElementById('btnResetSertifikat');
    if (input) input.value = '';
    if (span) {
        span.textContent = 'Belum ada file dipilih (boleh dikosongkan)';
        span.style.color = 'var(--text-muted)';
        span.style.fontWeight = '400';
    }
    if (btnReset) btnReset.style.display = 'none';
}
</script>
<?php $this->endSection() ?>
