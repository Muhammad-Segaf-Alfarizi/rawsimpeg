<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$totalWt = array_sum(array_column($abkList, 'waktu_penyelesaian'));
$jmlKegiatan = count($abkList);
$efektivitas = ($totalWt > 0) ? min(100, round(($totalWt / 1250) * 100, 1)) : 0;
?>

<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?></p>
        </div>
        <button type="button" class="btn-primary-dark" data-toggle="modal" data-target="#modalTambahAbk">
            <i class="fas fa-plus"></i> Tambah Kegiatan ABK
        </button>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-list-check"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $jmlKegiatan ?></div><div class="stat-label">Total Kegiatan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-clock"></i></div>
        <div class="stat-text"><div class="stat-value"><?= number_format($totalWt, 2) ?></div><div class="stat-label">Total Waktu (menit)</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $efektivitas >= 100 ? 'success' : 'warning' ?>"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $efektivitas ?>%</div><div class="stat-label">Efektivitas Kerja</div></div>
    </div>
</div>

<!-- Tabel ABK -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-briefcase header-icon"></i> Rincian Analisis Beban Kerja (ABK)</h5>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblAbk">
                <thead>
                    <tr><th>Nama Kegiatan</th><th>Satuan</th><th>Volume</th><th>Norma Waktu</th><th>Total Waktu</th><th>Keterangan</th><th>File</th><th style="text-align:center;">Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($abkList as $a): ?>
                    <tr>
                        <td style="font-weight:500;"><?= esc($a['nama_kegiatan']) ?></td>
                        <td style="color:var(--text-muted);"><?= esc($a['satuan']) ?></td>
                        <td><?= esc($a['volume']) ?></td>
                        <td><?= esc($a['norma_waktu']) ?></td>
                        <td style="color:var(--accent-light);font-weight:600;"><?= number_format((float)$a['waktu_penyelesaian'], 2) ?></td>
                        <td style="font-size:12px;color:var(--text-muted);"><?= esc($a['keterangan'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($a['file'])): ?>
                            <a href="<?= esc($a['file']) ?>" target="_blank" class="btn-info-dark btn-sm-custom"><i class="fas fa-file"></i> Lihat</a>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:12px;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;width:120px;">
                            <button class="btn-warning-dark btn-sm-custom" onclick="editAbk(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="<?= base_url('abk/hapus/' . urlencode($nip) . '/' . $a['id_abk']) ?>" class="btn-danger-dark btn-sm-custom confirm-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($abkList)): ?>
                <tfoot>
                    <tr>
                        <td colspan="4" style="padding:12px 16px;font-weight:700;color:var(--text-primary);font-size:13px;">TOTAL BEBAN KERJA</td>
                        <td style="padding:12px 16px;font-weight:800;color:var(--accent);font-size:16px;"><?= number_format($totalWt, 2) ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambahAbk" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Kegiatan ABK</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="POST" action="<?= base_url('abk/simpan/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group"><label>Nama Kegiatan *</label><input type="text" name="nama_kegiatan" class="form-control" required placeholder="Uraian kegiatan"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Satuan *</label><input type="text" name="satuan" class="form-control" required placeholder="Dok/Laporan/Kegiatan"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Volume *</label><input type="number" name="volume" id="addVol" class="form-control" min="0" step="0.01" required oninput="calcAdd()"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Norma Waktu (menit) *</label><input type="number" name="norma_waktu" id="addNW" class="form-control" min="0" step="0.01" required oninput="calcAdd()"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Total Waktu</label><input type="text" id="addWT" class="form-control" readonly style="background:rgba(99,102,241,0.05);color:var(--accent-light);font-weight:700;"></div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group"><label>Keterangan</label><input type="text" name="keterangan" class="form-control" placeholder="Opsional"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Upload File (maks 5MB)</label><input type="file" name="file" class="form-control-file"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div></div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEditAbk" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-edit"></i> Edit Kegiatan ABK</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="POST" action="<?= base_url('abk/update/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_abk" id="edit_id_abk">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group"><label>Nama Kegiatan *</label><input type="text" name="nama_kegiatan" id="edit_kegiatan" class="form-control" required></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Satuan *</label><input type="text" name="satuan" id="edit_satuan" class="form-control" required></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Volume *</label><input type="number" name="volume" id="edit_vol" class="form-control" min="0" step="0.01" required oninput="calcEdit()"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Norma Waktu (menit) *</label><input type="number" name="norma_waktu" id="edit_nw" class="form-control" min="0" step="0.01" required oninput="calcEdit()"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Total Waktu</label><input type="text" id="edit_wt" class="form-control" readonly style="background:rgba(99,102,241,0.05);color:var(--accent-light);font-weight:700;"></div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group"><label>Keterangan</label><input type="text" name="keterangan" id="edit_ket" class="form-control"></div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group"><label>Ganti File (opsional)</label><input type="file" name="file" class="form-control-file"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div></div>
</div>
<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
function calcAdd() {
    var v = parseFloat(document.getElementById('addVol').value) || 0;
    var n = parseFloat(document.getElementById('addNW').value) || 0;
    document.getElementById('addWT').value = (v * n).toFixed(2);
}
function calcEdit() {
    var v = parseFloat(document.getElementById('edit_vol').value) || 0;
    var n = parseFloat(document.getElementById('edit_nw').value) || 0;
    document.getElementById('edit_wt').value = (v * n).toFixed(2);
}
function editAbk(data) {
    document.getElementById('edit_id_abk').value    = data.id_abk;
    document.getElementById('edit_kegiatan').value  = data.nama_kegiatan;
    document.getElementById('edit_satuan').value    = data.satuan;
    document.getElementById('edit_vol').value       = data.volume;
    document.getElementById('edit_nw').value        = data.norma_waktu;
    document.getElementById('edit_wt').value        = parseFloat(data.waktu_penyelesaian).toFixed(2);
    document.getElementById('edit_ket').value       = data.keterangan || '';
    $('#modalEditAbk').modal('show');
}
</script>
<?php $this->endSection() ?>
