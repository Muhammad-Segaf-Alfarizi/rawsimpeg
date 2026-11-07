<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="grid grid-3" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-users"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_pegawai'] ?? 0 ?></div>
            <div class="stat-label">Total Pegawai</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-plane-departure"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_dl'] ?? 0 ?></div>
            <div class="stat-label">Dinas Luar Hari Ini</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-umbrella-beach"></i></div>
        <div class="stat-text">
            <div class="stat-value"><?= $stats['total_cuti'] ?? 0 ?></div>
            <div class="stat-label">Cuti Hari Ini</div>
        </div>
    </div>
</div>

<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-table header-icon"></i> Data Pegawai (Admin)</h5>
        <span style="font-size:12px;color:var(--text-muted);"><?= date('d F Y') ?></span>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblAdmin">
                <thead>
                    <tr>
                        <th>Nama Pegawai</th>
                        <th>NIP</th>
                        <th>Golongan</th>
                        <th>Jabatan</th>
                        <th>Status Hari Ini</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pegawaiList as $peg):
                    $st = $peg['status_hari_ini'] ?? null;
                    $stBadge = '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Hadir</span>';
                    $ket = '-';
                    if ($st && !empty($st['status'])) {
                        $ket = esc($st['keterangan'] ?? '-');
                        $stBadge = match($st['status']) {
                            'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> Dinas Luar</span>',
                            'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                            'DK'   => '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>',
                            default=> '<span class="badge-custom badge-secondary">'.esc($st['status']).'</span>',
                        };
                    }
                ?>
                <tr>
                    <td>
                        <a href="<?= base_url('pegawai/profil/' . urlencode($peg['nip'])) ?>" class="employee-link">
                            <i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px;"></i>
                            <?= esc($peg['nama']) ?>
                        </a>
                    </td>
                    <td style="color:var(--text-muted);font-family:monospace;"><?= esc($peg['nip']) ?></td>
                    <td><?= esc($peg['golongan'] ?? '-') ?></td>
                    <td style="font-size:12px;"><?= esc($peg['jabatan'] ?? '-') ?></td>
                    <td><?= $stBadge ?></td>
                    <td style="font-size:12px;"><?= $ket ?></td>
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
document.querySelector('.page-title').innerHTML = '<i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Admin';
</script>
<?php $this->endSection() ?>


<?php $this->section('scripts') ?>
<script>
document.querySelector('.page-title').innerHTML = '<i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Admin';
</script>
<?php $this->endSection() ?>