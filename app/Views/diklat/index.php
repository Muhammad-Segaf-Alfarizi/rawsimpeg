<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$canEdit = $can_edit ?? (session()->get('role') !== 'kepala' || ($nip ?? '') === session()->get('nip'));

$fmtRentangDiklat = static function (?string $mulai, ?string $selesai): string {
    $m = (! empty($mulai) && $mulai !== '0000-00-00') ? $mulai : null;
    $s = (! empty($selesai) && $selesai !== '0000-00-00') ? $selesai : null;
    if (! $m && ! $s) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t1    = strtotime($m ?: $s);
    $t2    = strtotime($s ?: $m);
    if (! $t1 || ! $t2) {
        return esc((string) ($m ?: $s));
    }
    $d1 = (int) date('j', $t1); $b1 = $bulan[(int) date('n', $t1)]; $y1 = date('Y', $t1);
    $d2 = (int) date('j', $t2); $b2 = $bulan[(int) date('n', $t2)]; $y2 = date('Y', $t2);
    if ($t1 === $t2) {
        return "{$d1} {$b1} {$y1}";
    }
    if ($b1 === $b2 && $y1 === $y2) {
        return "{$d1} – {$d2} {$b1} {$y1}";
    }
    if ($y1 === $y2) {
        return "{$d1} {$b1} – {$d2} {$b2} {$y1}";
    }
    return "{$d1} {$b1} {$y1} – {$d2} {$b2} {$y2}";
};

$badgeModelPelatihan = static function (?string $model): string {
    return match ($model) {
        'Luring'  => '<span class="badge-custom badge-success"><i class="fas fa-chalkboard-teacher"></i> Luring</span>',
        'Daring'  => '<span class="badge-custom badge-info"><i class="fas fa-laptop-house"></i> Daring</span>',
        'Blended' => '<span class="badge-custom badge-primary"><i class="fas fa-random"></i> Blended</span>',
        default   => '<span style="color:var(--text-muted);">-</span>',
    };
};
?>

<!-- Header -->
<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?></p>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('diklat/tambah/' . urlencode($nip)) ?>" class="btn-primary-dark">
            <i class="fas fa-plus"></i> Tambah Diklat
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-graduation-cap header-icon"></i> Daftar Diklat yang Pernah Diikuti</h5>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblDiklat">
                <thead>
                    <tr>
                        <th style="width:50px;">No.</th>
                        <th>Nama Diklat / Pelatihan</th>
                        <th>Penyelenggara</th>
                        <th>Tanggal Pelatihan</th>
                        <th>Tempat Pelaksanaan</th>
                        <th style="text-align:center;">Model</th>
                        <th style="text-align:center;">Sertifikat</th>
                        <?php if ($canEdit): ?><th style="text-align:center;width:145px;">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($diklatList as $i => $dk):
                    $sertifUrl = $dk['file_sertifikat'] ?? '';
                    $idDiklat  = (int) $dk['id_daftar_diklat'];
                ?>
                    <tr>
                        <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                        <td style="font-weight:600;color:var(--text-primary);"><?= esc($dk['nama_diklat']) ?></td>
                        <td><?= ! empty($dk['penyelenggara']) ? esc($dk['penyelenggara']) : '<span style="color:var(--text-muted);">-</span>' ?></td>
                        <td style="white-space:nowrap;"><?= $fmtRentangDiklat($dk['tanggal_mulai'] ?? null, $dk['tanggal_selesai'] ?? null) ?></td>
                        <td><?= ! empty($dk['tempat_pelaksanaan']) ? esc($dk['tempat_pelaksanaan']) : '<span style="color:var(--text-muted);">-</span>' ?></td>
                        <td style="text-align:center;"><?= $badgeModelPelatihan($dk['model_pelatihan'] ?? null) ?></td>
                        <td style="text-align:center;white-space:nowrap;">
                            <div style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center;">
                                <?php if (! empty($sertifUrl)): ?>
                                    <a href="<?= esc($sertifUrl) ?>" target="_blank" class="btn-success-dark btn-sm-custom" title="Buka Sertifikat di Google Drive">
                                        <i class="fas fa-certificate"></i> Lihat
                                    </a>
                                <?php elseif (! $canEdit): ?>
                                    <span style="color:var(--text-muted);">-</span>
                                <?php endif; ?>

                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn-info-dark btn-sm-custom"
                                            onclick="bukaModalUploadSertifikat(<?= $idDiklat ?>, '<?= esc($dk['nama_diklat'], 'attr') ?>', '<?= esc($sertifUrl, 'attr') ?>')"
                                            title="<?= ! empty($sertifUrl) ? 'Ganti Sertifikat' : 'Upload Sertifikat (Opsional)' ?>">
                                        <i class="fas fa-<?= ! empty($sertifUrl) ? 'sync-alt' : 'upload' ?>"></i>
                                        <?= ! empty($sertifUrl) ? 'Ganti' : 'Upload' ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php if ($canEdit): ?>
                        <td style="text-align:center;white-space:nowrap;">
                            <a href="<?= base_url('diklat/edit/' . urlencode($nip) . '/' . $idDiklat) ?>" class="btn-warning-dark btn-sm-custom">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?= base_url('diklat/hapus/' . urlencode($nip) . '/' . $idDiklat) ?>" class="btn-danger-dark btn-sm-custom confirm-delete">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- MODAL UPLOAD SERTIFIKAT DIKLAT -->
<div class="modal fade" id="modalUploadSertifikat" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-certificate" style="color:var(--accent);margin-right:8px;"></i>Upload Sertifikat Diklat
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" id="formModalSertifikat" action="" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div style="padding:10px 12px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:14px;">
                        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Nama Pelatihan</div>
                        <div id="modalNamaDiklatLabel" style="font-weight:600;color:var(--text-primary);margin-top:2px;">-</div>
                    </div>

                    <div id="modalCurrentSertifikatBox" style="display:none;margin-bottom:14px;padding:10px 12px;background:rgba(99,102,241,0.1);border:1px solid var(--border-accent);border-radius:var(--radius-sm);">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span style="font-size:12.5px;color:var(--text-secondary);">
                                <i class="fas fa-check-circle" style="color:var(--success);"></i> Sertifikat sudah tersimpan
                            </span>
                            <a id="modalCurrentSertifikatLink" href="#" target="_blank" style="font-size:12.5px;font-weight:600;">
                                <i class="fas fa-external-link-alt"></i> Lihat File Lama
                            </a>
                        </div>
                        <small style="display:block;color:var(--warning);margin-top:4px;font-size:11.5px;">
                            Mengunggah file baru akan menggantikan file sertifikat lama di Google Drive.
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom:8px;">
                        <label class="form-label-dark">Pilih File Sertifikat <span style="color:var(--danger);">*</span></label>
                        <input type="file" name="file_sertifikat" id="modalFileSertifikatInput" class="form-control-dark"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required style="padding:8px;">
                        <small style="display:block;margin-top:6px;font-size:11.5px;color:var(--text-muted);">
                            Format: <strong>PDF, JPG, PNG, DOC/DOCX</strong> (maks. 5 MB). Disimpan ke folder Google Drive <strong><?= esc($pegawai['nama'] ?? $nip) ?></strong>.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-back" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-primary-dark">
                        <i class="fas fa-cloud-upload-alt"></i> Unggah ke Google Drive
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection() ?>
<?php if ($canEdit): ?>
<?php $this->section('scripts') ?>
<script>
function bukaModalUploadSertifikat(idDiklat, namaDiklat, currentUrl) {
    var baseAction = '<?= base_url('diklat/upload-sertifikat/' . urlencode($nip)) ?>/' + idDiklat;
    document.getElementById('formModalSertifikat').action = baseAction;
    document.getElementById('modalNamaDiklatLabel').textContent = namaDiklat;
    document.getElementById('modalFileSertifikatInput').value = '';

    var curBox  = document.getElementById('modalCurrentSertifikatBox');
    var curLink = document.getElementById('modalCurrentSertifikatLink');
    if (currentUrl && currentUrl !== '') {
        curBox.style.display = 'block';
        curLink.href = currentUrl;
    } else {
        curBox.style.display = 'none';
    }

    $('#modalUploadSertifikat').modal('show');
}
</script>
<?php $this->endSection() ?>
<?php endif; ?>
