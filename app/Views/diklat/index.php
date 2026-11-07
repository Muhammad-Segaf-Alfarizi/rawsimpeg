<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<!-- Header -->
<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?></p>
        </div>
        <a href="<?= base_url('diklat/tambah/' . urlencode($nip)) ?>" class="btn-primary-dark">
            <i class="fas fa-plus"></i> Tambah Diklat
        </a>
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
                    <tr><th>No.</th><th>Nama Diklat / Pelatihan</th><th style="text-align:center;">Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($diklatList as $i => $dk): ?>
                    <tr>
                        <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                        <td><?= esc($dk['nama_diklat']) ?></td>
                        <td style="text-align:center;width:140px;">
                            <a href="<?= base_url('diklat/edit/' . urlencode($nip) . '/' . $dk['id_daftar_diklat']) ?>" class="btn-warning-dark btn-sm-custom">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?= base_url('diklat/hapus/' . urlencode($nip) . '/' . $dk['id_daftar_diklat']) ?>" class="btn-danger-dark btn-sm-custom confirm-delete">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $this->endSection() ?>
