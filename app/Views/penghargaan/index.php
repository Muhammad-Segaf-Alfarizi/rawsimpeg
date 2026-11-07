<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?></p>
        </div>
        <a href="<?= base_url('penghargaan/tambah/' . urlencode($nip)) ?>" class="btn-primary-dark">
            <i class="fas fa-plus"></i> Tambah Penghargaan
        </a>
    </div>
</div>

<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-award header-icon"></i> Riwayat Penghargaan</h5>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <table class="table-dark-custom auto-datatable" id="tblPenghargaan">
            <thead>
                <tr><th>Nama Penghargaan</th><th>Tahun</th><th>Pemberi</th><th style="text-align:center;">Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($penghargaanList as $p): ?>
                <tr>
                    <td><?= esc($p['nama_penghargaan']) ?></td>
                    <td><?= esc($p['tahun']) ?></td>
                    <td style="color:var(--text-secondary);"><?= esc($p['pemberi'] ?? '-') ?></td>
                    <td style="text-align:center;width:140px;">
                        <a href="<?= base_url('penghargaan/edit/' . urlencode($nip) . '/' . $p['id_penghargaan']) ?>" class="btn-warning-dark btn-sm-custom"><i class="fas fa-edit"></i> Edit</a>
                        <a href="<?= base_url('penghargaan/hapus/' . urlencode($nip) . '/' . $p['id_penghargaan']) ?>" class="btn-danger-dark btn-sm-custom confirm-delete"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
