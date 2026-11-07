<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<!-- Stats -->
<div class="grid grid-4" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-users"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_pegawai'] ?></div>
            <div class="stat-label">Total Pegawai</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-plane-departure"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_dl'] ?></div>
            <div class="stat-label">Dinas Luar Hari Ini</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-umbrella-beach"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_cuti'] ?></div>
            <div class="stat-label">Cuti Hari Ini</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_abk_belum'] ?></div>
            <div class="stat-label">Belum Isi ABK</div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-table header-icon"></i> Data Pegawai BGTK</h5>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a href="<?= base_url('pegawai/tambah') ?>" class="btn-primary-dark">
                <i class="fas fa-user-plus"></i> Tambah Pegawai
            </a>
            <a href="<?= base_url('user/tambah') ?>" class="btn-info-dark">
                <i class="fas fa-user-shield"></i> Tambah User
            </a>
            <form method="POST" action="<?= base_url('user/hash-all') ?>" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn-success-dark" title="Enkripsi otomatis seluruh password plain-text di database menjadi Bcrypt">
                    <i class="fas fa-lock"></i> Hash Semua Password
                </button>
            </form>
        </div>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblPegawai">
                <thead>
                    <tr>
                        <th>Nama Pegawai</th>
                        <th>NIP</th>
                        <th>Gol / Jabatan</th>
                        <th>Status Hari Ini</th>
                        <th>Detail Status</th>
                        <th style="text-align:center;">ABK</th>
                        <th style="text-align:center;">TNA</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pegawaiList as $peg):
                    $st = $peg['status_hari_ini'];
                    $stBadge = match($st['status'] ?? '-') {
                        'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> DL</span>',
                        'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                        'DK'   => '<span class="badge-custom badge-secondary"><i class="fas fa-building"></i> DK</span>',
                        default=> '<span class="badge-custom badge-secondary">&mdash;</span>',
                    };
                    $detail = $st ? esc($st['keterangan'] ?? '-') : '-';
                    if ($st && !empty($st['file'])) $detail .= ' &nbsp;<a href="'.esc($st['file']).'" target="_blank" style="font-size:11px;">[SPT]</a>';

                    $abkBadge = ($peg['jml_abk'] > 0)
                        ? '<span class="badge-custom badge-success"><i class="fas fa-check-circle"></i> Sudah</span>'
                        : '<span class="badge-custom badge-danger"><i class="fas fa-times-circle"></i> Belum</span>';

                    if ($peg['tna_belum'] > 0)
                        $tnaBadge = '<span class="badge-custom badge-danger"><i class="fas fa-exclamation-triangle"></i> Butuh</span>';
                    elseif ($peg['tna_berjalan'] > 0)
                        $tnaBadge = '<span class="badge-custom badge-primary"><i class="fas fa-spinner fa-spin"></i> Berjalan</span>';
                    elseif ($peg['jml_tna'] > 0)
                        $tnaBadge = '<span class="badge-custom badge-success"><i class="fas fa-check"></i> Selesai</span>';
                    else
                        $tnaBadge = '<span class="badge-custom badge-secondary">Belum Analisis</span>';
                ?>
                <tr>
                    <td>
                        <a href="<?= base_url('pegawai/profil/' . urlencode($peg['nip'])) ?>" class="employee-link">
                            <i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px;"></i>
                            <?= esc($peg['nama']) ?>
                        </a>
                    </td>
                    <td style="color:var(--text-muted);font-family:monospace;"><?= esc($peg['nip']) ?></td>
                    <td>
                        <span style="font-weight:600;"><?= esc($peg['golongan'] ?? '-') ?></span><br>
                        <span style="font-size:12px;color:var(--text-muted);"><?= esc($peg['jabatan'] ?? '-') ?></span>
                    </td>
                    <td><?= $stBadge ?></td>
                    <td style="font-size:12px;color:var(--text-secondary);"><?= $detail ?></td>
                    <td style="text-align:center;"><?= $abkBadge ?></td>
                    <td style="text-align:center;"><?= $tnaBadge ?></td>
                    <td style="vertical-align:middle;width:140px;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                            <a href="<?= base_url('pegawai/edit/' . urlencode($peg['nip'])) ?>" class="btn btn-warning-dark btn-sm-custom" style="width:100%;justify-content:center;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <div style="display:flex;gap:6px;width:100%;">
                                <a href="<?= base_url('abk/' . urlencode($peg['nip'])) ?>" class="btn btn-info-dark btn-sm-custom" style="flex:1;justify-content:center;">
                                    <i class="fas fa-briefcase"></i> ABK
                                </a>
                                <a href="<?= base_url('tna/' . urlencode($peg['nip'])) ?>" class="btn btn-success-dark btn-sm-custom" style="flex:1;justify-content:center;">
                                    <i class="fas fa-clipboard-list"></i> TNA
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
// Override page_title dan title_icon untuk topbar
document.querySelector('.page-title').innerHTML = '<i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Super Admin';
</script>
<?php $this->endSection() ?>
