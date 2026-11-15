<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="grid grid-4" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-users"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $stats['total_pegawai'] ?></div><div class="stat-label">Total Pegawai</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-plane-departure"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $stats['total_dl'] ?></div><div class="stat-label">Dinas Luar Hari Ini</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-umbrella-beach"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $stats['total_cuti'] ?></div><div class="stat-label">Cuti Hari Ini</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $stats['total_abk_belum'] ?></div><div class="stat-label">Belum Isi ABK</div></div>
    </div>
</div>

<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-table header-icon"></i> Data Pegawai &amp; Kehadiran Hari Ini</h5>
        <span style="font-size:12px;color:var(--text-muted);"><?= date('d F Y') ?></span>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblKepala">
                <thead>
                    <tr>
                        <th>Nama Pegawai</th>
                        <th>NIP</th>
                        <th>Gol / Jabatan</th>
                        <th>Status Hari Ini</th>
                        <th>Detail Status</th>
                        <th>Tgl Mulai</th>
                        <th>Tgl Selesai</th>
                        <th style="text-align:center;">ABK</th>
                        <th style="text-align:center;">TNA</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pegawaiList as $peg):
                    $st = $peg['status_hari_ini'];
                    $stBadge = '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Hadir</span>';
                    $ket = '-'; $tglM = '-'; $tglS = '-';
                    if ($st) {
                        $ket   = esc($st['keterangan'] ?? '-');
                        if (!empty($st['file'])) $ket .= ' &nbsp;<a href="'.esc($st['file']).'" target="_blank" style="font-size:11px;">[SPT]</a>';
                        $tglM  = $st['tanggal_mulai']   ? date('d M Y', strtotime($st['tanggal_mulai']))   : '-';
                        $tglS  = $st['tanggal_selesai'] ? date('d M Y', strtotime($st['tanggal_selesai'])) : '-';
                        $stBadge = match($st['status']) {
                            'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> Dinas Luar</span>',
                            'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                            'DK'   => '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>',
                            default=> '<span class="badge-custom badge-secondary">'.esc($st['status']).'</span>',
                        };
                    }

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
                    <td><a href="<?= base_url('pegawai/profil/' . urlencode($peg['nip'])) ?>" class="employee-link"><i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px;"></i><?= esc($peg['nama']) ?></a></td>
                    <td style="color:var(--text-muted);font-family:monospace;"><?= esc($peg['nip']) ?></td>
                    <td>
                        <span style="font-weight:600;"><?= esc($peg['golongan'] ?? '-') ?></span><br>
                        <span style="font-size:12px;color:var(--text-muted);"><?= esc($peg['jabatan'] ?? '-') ?></span>
                    </td>
                    <td><?= $stBadge ?></td>
                    <td style="font-size:12px;color:var(--text-secondary);"><?= $ket ?></td>
                    <td style="font-size:12px;white-space:nowrap;"><?= $tglM ?></td>
                    <td style="font-size:12px;white-space:nowrap;"><?= $tglS ?></td>
                    <td style="text-align:center;"><?= $abkBadge ?></td>
                    <td style="text-align:center;"><?= $tnaBadge ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
