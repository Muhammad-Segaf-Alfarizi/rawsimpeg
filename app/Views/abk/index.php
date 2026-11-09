<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$totalWt = 0;
foreach ($abkList as $item) {
    $wm = (float) ($item['waktu_penyelesaian'] ?? $item['norma_waktu'] ?? 0);
    $vk = (float) ($item['volume_kerja'] ?? $item['volume'] ?? 0);
    $totalWt += ($wm * $vk);
}
$jmlKegiatan = count($abkList);
$efektivitas = ($totalWt > 0) ? min(100, round(($totalWt / 75000) * 100, 1)) : 0;
?>

<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?></p>
        </div>
        <button type="button" class="btn-primary-dark" data-toggle="modal" data-target="#modalTambahAbk">
            <i class="fas fa-plus"></i> Tambah Uraian Tugas
        </button>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-3" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-list-check"></i></div>
        <div class="stat-text"><div class="stat-value"><?= $jmlKegiatan ?></div><div class="stat-label">Total Uraian Tugas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-clock"></i></div>
        <div class="stat-text"><div class="stat-value"><?= number_format($totalWt, 0, ',', '.') ?></div><div class="stat-label">Total Menit</div></div>
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
                    <tr>
                        <th style="width:5%;text-align:center;">No</th>
                        <th style="width:38%;">Uraian Tugas Pokok</th>
                        <th style="width:15%;">Satuan Hasil</th>
                        <th style="width:10%;text-align:center;">Waktu (Menit)</th>
                        <th style="width:10%;text-align:center;">Volume Kerja</th>
                        <th style="width:12%;text-align:center;">Total Menit</th>
                        <th style="width:10%;text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($abkList as $a):
                    $uraianTugas = $a['uraian_tugas'] ?: ($a['nama_kegiatan'] ?? '-');
                    $satuanHasil = $a['satuan_hasil'] ?: ($a['satuan'] ?: '-');
                    $waktuMenit  = (float) ($a['waktu_penyelesaian'] ?? $a['norma_waktu'] ?? 0);
                    $volumeKerja = (float) ($a['volume_kerja'] ?? $a['volume'] ?? 0);
                    $totalMenit  = $waktuMenit * $volumeKerja;
                ?>
                    <tr>
                        <td style="text-align:center;"><?= $no++ ?></td>
                        <td style="font-weight:500;"><?= esc($uraianTugas) ?></td>
                        <td style="color:var(--text-muted);"><?= esc($satuanHasil) ?></td>
                        <td style="text-align:center;"><?= $waktuMenit > 0 ? number_format($waktuMenit, 0, ',', '.') : '0' ?></td>
                        <td style="text-align:center;"><?= $volumeKerja > 0 ? number_format($volumeKerja, 0, ',', '.') : '0' ?></td>
                        <td style="text-align:center;color:var(--accent-light);font-weight:600;"><?= number_format($totalMenit, 0, ',', '.') ?></td>
                        <td style="text-align:center;white-space:nowrap;">
                            <button type="button" class="btn-warning-dark btn-sm-custom" onclick="editAbk(<?= htmlspecialchars(json_encode([
                                'id_abk'             => $a['id_abk'],
                                'uraian_tugas'       => $a['uraian_tugas'] ?: ($a['nama_kegiatan'] ?? ''),
                                'satuan_hasil'       => $a['satuan_hasil'] ?: ($a['satuan'] ?? ''),
                                'waktu_penyelesaian' => $waktuMenit,
                                'volume_kerja'       => $volumeKerja,
                            ]), ENT_QUOTES) ?>)" title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="<?= base_url('abk/hapus/' . urlencode($nip) . '/' . $a['id_abk']) ?>" class="btn-danger-dark btn-sm-custom confirm-delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if (!empty($abkList)): ?>
                <tfoot>
                    <tr>
                        <td colspan="5" style="padding:12px 16px;text-align:right;font-weight:700;color:var(--text-primary);font-size:13px;">TOTAL MENIT BEBAN KERJA</td>
                        <td style="padding:12px 16px;text-align:center;font-weight:800;color:var(--accent);font-size:15px;"><?= number_format($totalWt, 0, ',', '.') ?></td>
                        <td></td>
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
        <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Uraian Tugas ABK</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="POST" action="<?= base_url('abk/simpan/' . urlencode($nip)) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Uraian Tugas Pokok *</label>
                            <textarea name="uraian_tugas" class="form-control" rows="3" required placeholder="Uraian tugas pokok"></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Satuan Hasil *</label>
                            <input type="text" name="satuan_hasil" class="form-control" required placeholder="Dokumen / Laporan / Kegiatan">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Waktu (Menit) *</label>
                            <input type="number" name="waktu_penyelesaian" id="addNW" class="form-control" min="0" step="1" required oninput="calcAdd()">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Volume Kerja *</label>
                            <input type="number" name="volume_kerja" id="addVol" class="form-control" min="0" step="1" required oninput="calcAdd()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total Menit</label>
                            <input type="text" id="addWT" class="form-control" readonly style="background:rgba(99,102,241,0.05);color:var(--accent-light);font-weight:700;">
                        </div>
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
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-edit"></i> Edit Uraian Tugas ABK</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form method="POST" action="<?= base_url('abk/update/' . urlencode($nip)) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id_abk" id="edit_id_abk">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Uraian Tugas Pokok *</label>
                            <textarea name="uraian_tugas" id="edit_uraian_tugas" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Satuan Hasil *</label>
                            <input type="text" name="satuan_hasil" id="edit_satuan_hasil" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Waktu (Menit) *</label>
                            <input type="number" name="waktu_penyelesaian" id="edit_nw" class="form-control" min="0" step="1" required oninput="calcEdit()">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Volume Kerja *</label>
                            <input type="number" name="volume_kerja" id="edit_vol" class="form-control" min="0" step="1" required oninput="calcEdit()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Total Menit</label>
                            <input type="text" id="edit_wt" class="form-control" readonly style="background:rgba(99,102,241,0.05);color:var(--accent-light);font-weight:700;">
                        </div>
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
    document.getElementById('addWT').value = Math.round(v * n).toLocaleString('id-ID');
}
function calcEdit() {
    var v = parseFloat(document.getElementById('edit_vol').value) || 0;
    var n = parseFloat(document.getElementById('edit_nw').value) || 0;
    document.getElementById('edit_wt').value = Math.round(v * n).toLocaleString('id-ID');
}
function editAbk(data) {
    document.getElementById('edit_id_abk').value       = data.id_abk;
    document.getElementById('edit_uraian_tugas').value = data.uraian_tugas || '';
    document.getElementById('edit_satuan_hasil').value = data.satuan_hasil || '';
    document.getElementById('edit_nw').value           = data.waktu_penyelesaian || 0;
    document.getElementById('edit_vol').value          = data.volume_kerja || 0;
    calcEdit();
    $('#modalEditAbk').modal('show');
}
</script>
<?php $this->endSection() ?>
