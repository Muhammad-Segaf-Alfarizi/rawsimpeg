<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php $role = session()->get('role'); ?>

<?php if ($role === 'pegawai'): ?>
<div class="alert-dark alert-info-dark" style="margin-bottom:20px;">
    <i class="fas fa-info-circle"></i>
    Setiap pengajuan TNA yang Anda tambahkan akan berstatus <strong>Menunggu Persetujuan</strong> dan perlu diverifikasi oleh Tim Kepegawaian.
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert-dark alert-success-dark" style="margin-bottom:16px;">
    <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert-dark alert-danger-dark" style="margin-bottom:16px;">
    <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
</div>
<?php endif; ?>

<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h4 style="margin-bottom:4px;"><i class="fas fa-brain" style="color:var(--accent);margin-right:8px;"></i><?= esc($pegawai['nama'] ?? '-') ?></h4>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">NIP: <?= esc($nip) ?> &mdash; Training Needs Analysis (TNA)</p>
        </div>
        <button type="button" class="btn-primary-dark" data-toggle="modal" data-target="#modalTambahTna">
            <i class="fas fa-plus"></i> Tambah Usulan Pelatihan
        </button>
    </div>
</div>

<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-chalkboard-teacher header-icon"></i> Daftar Analisis &amp; Usulan Pelatihan</h5>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="table-dark-custom auto-datatable" id="tblTna">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Kompetensi Dibutuhkan</th>
                        <th>Usulan Pelatihan</th>
                        <th style="text-align:center;">Prioritas</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Persetujuan</th>
                        <th style="text-align:center;width:180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($tnaList as $t):
                        $sBadge = match($t['status_pelaksanaan']) {
                            'Selesai'        => '<span class="badge-custom badge-success"><i class="fas fa-check-circle"></i> Selesai</span>',
                            'Sedang Berjalan'=> '<span class="badge-custom badge-primary"><i class="fas fa-spinner fa-spin"></i> Berjalan</span>',
                            default          => '<span class="badge-custom badge-secondary"><i class="fas fa-clock"></i> Belum</span>',
                        };
                        $pBadge = match($t['status_persetujuan'] ?? 'Menunggu Persetujuan') {
                            'Disetujui' => '<span class="badge-custom badge-success"><i class="fas fa-check"></i> Disetujui</span>',
                            'Ditolak'   => '<span class="badge-custom badge-danger"><i class="fas fa-times"></i> Ditolak</span>',
                            default     => '<span class="badge-custom badge-warning"><i class="fas fa-hourglass-half"></i> Menunggu</span>',
                        };
                        $prBadge = match($t['prioritas'] ?? 'Sedang') {
                            'Tinggi' => '<span class="badge-custom badge-danger">Tinggi</span>',
                            'Rendah' => '<span class="badge-custom badge-secondary">Rendah</span>',
                            default  => '<span class="badge-custom badge-warning">Sedang</span>',
                        };
                        $tData = htmlspecialchars(json_encode($t), ENT_QUOTES);
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td style="font-weight:500;"><?= esc($t['kompetensi_dibutuhkan'] ?? '-') ?></td>
                        <td>
                            <strong><?= esc($t['usulan_pelatihan'] ?? '-') ?></strong><br>
                            <small style="color:var(--text-muted);"><?= esc($t['kategori_kesenjangan'] ?? '') ?></small>
                        </td>
                        <td style="text-align:center;"><?= $prBadge ?></td>
                        <td style="text-align:center;"><?= $sBadge ?></td>
                        <td style="text-align:center;"><?= $pBadge ?></td>
                        <td style="text-align:center;white-space:nowrap;">
                            <?php if (($t['status_persetujuan'] ?? '') === 'Menunggu Persetujuan' && in_array($role, ['super_admin', 'admin'])): ?>
                            <form action="<?= base_url('tna/persetujuan/' . urlencode($nip) . '/' . $t['id_tna']) ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="Disetujui">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui usulan TNA ini?');" title="Setujui">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form action="<?= base_url('tna/persetujuan/' . urlencode($nip) . '/' . $t['id_tna']) ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="Ditolak">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak usulan TNA ini?');" title="Tolak">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <button class="btn btn-info btn-sm" onclick="detailTna(<?= $tData ?>)" title="Detail">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning btn-sm text-dark" onclick="editTna(<?= $tData ?>)" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="<?= base_url('tna/hapus/' . urlencode($nip) . '/' . $t['id_tna']) ?>"
                               class="btn btn-danger btn-sm confirm-delete" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================================================================== -->
<!-- MODAL TAMBAH TNA                                                       -->
<!-- ===================================================================== -->
<div class="modal fade" id="modalTambahTna" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle"></i> Tambah Analisis Kebutuhan Pelatihan (TNA)</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('tna/simpan/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="row">
                    <!-- Kolom Kiri: Informasi Dasar & Gap -->
                    <div class="col-md-6 border-right border-secondary">
                        <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Dasar &amp; Analisis Gap</h6>
                        <div class="form-group">
                            <label>1. Kompetensi yang Dibutuhkan: *</label>
                            <input type="text" class="form-control" name="kompetensi_dibutuhkan" required>
                        </div>
                        <div class="form-group">
                            <label>2. Kondisi Saat Ini (Kesenjangan/Gap): *</label>
                            <textarea class="form-control" name="kondisi_saat_ini" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>3. Kategori Kesenjangan Kompetensi:</label>
                            <select name="kategori_kesenjangan" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Keterampilan Teknis">Keterampilan Teknis</option>
                                <option value="Pengetahuan">Pengetahuan</option>
                                <option value="Sikap Perilaku">Sikap Perilaku</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>4. Usulan Nama Pelatihan/Diklat: *</label>
                            <input type="text" class="form-control" name="usulan_pelatihan" required>
                        </div>
                        <div class="form-group">
                            <label>5. Materi / Rincian Pelatihan:</label>
                            <textarea class="form-control" name="materi_pelatihan" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>6. Indikator Keberhasilan / Output:</label>
                            <textarea class="form-control" name="indikator_keberhasilan" rows="2"></textarea>
                        </div>
                    </div>
                    <!-- Kolom Kanan: Pelaksanaan & Evaluasi -->
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Pelaksanaan &amp; Evaluasi</h6>
                        <div class="form-group">
                            <label>7. Metode / Moda Pelaksanaan:</label>
                            <select name="metode_pelaksanaan" class="form-control">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Klasikal (Luring)">Klasikal (Luring)</option>
                                <option value="Non-Klasikal (Daring)">Non-Klasikal (Daring)</option>
                                <option value="Blended Learning">Blended Learning</option>
                                <option value="Mentoring">Mentoring</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>8. Narasumber / Penyelenggara:</label>
                            <input type="text" class="form-control" name="penyelenggara">
                        </div>
                        <div class="form-group">
                            <label>9. Jadwal Pelaksanaan (Rencana/Aktual):</label>
                            <input type="text" class="form-control" name="jadwal_pelaksanaan" placeholder="Contoh: Kuartal II 2025">
                        </div>
                        <div class="form-group">
                            <label>10. Evaluasi Pasca-Pelatihan:</label>
                            <textarea class="form-control" name="evaluasi_pasca" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>11. Catatan / Rekomendasi:</label>
                            <textarea class="form-control" name="catatan_rekomendasi" rows="2"></textarea>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Prioritas: *</label>
                                <select name="prioritas" class="form-control" required>
                                    <option value="Tinggi">Tinggi</option>
                                    <option value="Sedang" selected>Sedang</option>
                                    <option value="Rendah">Rendah</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Status Pelaksanaan: *</label>
                                <select name="status_pelaksanaan" class="form-control" required>
                                    <option value="Belum" selected>Belum Dilaksanakan</option>
                                    <option value="Sedang Berjalan">Sedang Berjalan</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group p-2 border border-secondary rounded">
                            <label>12. Unggah Dokumen Pendukung (Maks 5MB):</label>
                            <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png,.jpeg">
                            <small class="text-muted">Format: PDF, DOC, XLS, JPG, PNG</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save"></i> Simpan Analisis</button>
            </div>
        </form>
    </div></div>
</div>

<!-- ===================================================================== -->
<!-- MODAL EDIT TNA                                                         -->
<!-- ===================================================================== -->
<div class="modal fade" id="modalEditTna" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
        <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title font-weight-bold"><i class="fas fa-edit"></i> Edit Analisis Kebutuhan Pelatihan (TNA)</h5>
            <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('tna/update/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_tna" id="edit_id_tna">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right border-secondary">
                        <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Dasar &amp; Analisis Gap</h6>
                        <div class="form-group">
                            <label>1. Kompetensi yang Dibutuhkan: *</label>
                            <input type="text" class="form-control" name="kompetensi_dibutuhkan" id="edit_kompetensi" required>
                        </div>
                        <div class="form-group">
                            <label>2. Kondisi Saat Ini (Gap): *</label>
                            <textarea class="form-control" name="kondisi_saat_ini" id="edit_kondisi" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>3. Kategori Kesenjangan:</label>
                            <select name="kategori_kesenjangan" id="edit_kategori" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <option value="Keterampilan Teknis">Keterampilan Teknis</option>
                                <option value="Pengetahuan">Pengetahuan</option>
                                <option value="Sikap Perilaku">Sikap Perilaku</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>4. Usulan Nama Pelatihan: *</label>
                            <input type="text" class="form-control" name="usulan_pelatihan" id="edit_usulan" required>
                        </div>
                        <div class="form-group">
                            <label>5. Materi / Rincian Pelatihan:</label>
                            <textarea class="form-control" name="materi_pelatihan" id="edit_materi" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>6. Indikator Keberhasilan:</label>
                            <textarea class="form-control" name="indikator_keberhasilan" id="edit_indikator" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Pelaksanaan &amp; Evaluasi</h6>
                        <div class="form-group">
                            <label>7. Metode Pelaksanaan:</label>
                            <select name="metode_pelaksanaan" id="edit_metode" class="form-control">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Klasikal (Luring)">Klasikal (Luring)</option>
                                <option value="Non-Klasikal (Daring)">Non-Klasikal (Daring)</option>
                                <option value="Blended Learning">Blended Learning</option>
                                <option value="Mentoring">Mentoring</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>8. Penyelenggara:</label>
                            <input type="text" class="form-control" name="penyelenggara" id="edit_penyelenggara">
                        </div>
                        <div class="form-group">
                            <label>9. Jadwal Pelaksanaan:</label>
                            <input type="text" class="form-control" name="jadwal_pelaksanaan" id="edit_jadwal">
                        </div>
                        <div class="form-group">
                            <label>10. Evaluasi Pasca-Pelatihan:</label>
                            <textarea class="form-control" name="evaluasi_pasca" id="edit_evaluasi" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>11. Catatan / Rekomendasi:</label>
                            <textarea class="form-control" name="catatan_rekomendasi" id="edit_catatan" rows="2"></textarea>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label>Prioritas: *</label>
                                <select name="prioritas" id="edit_prioritas" class="form-control" required>
                                    <option value="Tinggi">Tinggi</option>
                                    <option value="Sedang">Sedang</option>
                                    <option value="Rendah">Rendah</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Status Pelaksanaan: *</label>
                                <select name="status_pelaksanaan" id="edit_status" class="form-control" required>
                                    <option value="Belum">Belum Dilaksanakan</option>
                                    <option value="Sedang Berjalan">Sedang Berjalan</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group p-2 border border-secondary rounded">
                            <label>12. Ganti Dokumen Pendukung (Maks 5MB):</label>
                            <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png,.jpeg">
                            <div id="edit_file_display" class="mt-2 font-weight-bold text-info small"></div>
                            <small class="text-muted">Biarkan kosong jika tidak mengubah file.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning font-weight-bold text-dark"><i class="fas fa-save"></i> Update Analisis</button>
            </div>
        </form>
    </div></div>
</div>

<!-- ===================================================================== -->
<!-- MODAL DETAIL TNA (View Only)                                           -->
<!-- ===================================================================== -->
<div class="modal fade" id="modalDetailTna" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
        <div class="modal-header bg-info text-white">
            <h5 class="modal-title font-weight-bold"><i class="fas fa-eye"></i> Detail Lengkap TNA</h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 border-right border-secondary">
                    <table class="table table-sm table-borderless text-white">
                        <tr><th style="width:45%;color:var(--text-muted);">Kompetensi Dibutuhkan</th><td id="det_kompetensi"></td></tr>
                        <tr><th style="color:var(--text-muted);">Kondisi Saat Ini</th><td id="det_kondisi"></td></tr>
                        <tr><th style="color:var(--text-muted);">Kategori Kesenjangan</th><td id="det_kategori"></td></tr>
                        <tr><th style="color:var(--text-muted);">Usulan Pelatihan</th><td id="det_usulan"></td></tr>
                        <tr><th style="color:var(--text-muted);">Materi / Rincian</th><td id="det_materi"></td></tr>
                        <tr><th style="color:var(--text-muted);">Indikator Keberhasilan</th><td id="det_indikator"></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless text-white">
                        <tr><th style="width:45%;color:var(--text-muted);">Metode Pelaksanaan</th><td id="det_metode"></td></tr>
                        <tr><th style="color:var(--text-muted);">Penyelenggara</th><td id="det_penyelenggara"></td></tr>
                        <tr><th style="color:var(--text-muted);">Jadwal Pelaksanaan</th><td id="det_jadwal"></td></tr>
                        <tr><th style="color:var(--text-muted);">Evaluasi Pasca-Pelatihan</th><td id="det_evaluasi"></td></tr>
                        <tr><th style="color:var(--text-muted);">Catatan / Rekomendasi</th><td id="det_catatan"></td></tr>
                        <tr><th style="color:var(--text-muted);">Prioritas / Status</th><td id="det_status_info"></td></tr>
                        <tr><th style="color:var(--text-muted);">Status Persetujuan</th><td id="det_persetujuan"></td></tr>
                        <tr><th style="color:var(--text-muted);">Dokumen Pendukung</th><td id="det_file"></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
        </div>
    </div></div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
function setSelect(id, val) {
    var el = document.getElementById(id);
    if (!el) return;
    for (var i = 0; i < el.options.length; i++) {
        if (el.options[i].value === val) { el.selectedIndex = i; break; }
    }
}

function editTna(d) {
    document.getElementById('edit_id_tna').value       = d.id_tna;
    document.getElementById('edit_kompetensi').value   = d.kompetensi_dibutuhkan || '';
    document.getElementById('edit_kondisi').value      = d.kondisi_saat_ini || '';
    document.getElementById('edit_usulan').value       = d.usulan_pelatihan || '';
    document.getElementById('edit_materi').value       = d.materi_pelatihan || '';
    document.getElementById('edit_indikator').value    = d.indikator_keberhasilan || '';
    document.getElementById('edit_penyelenggara').value= d.penyelenggara || '';
    document.getElementById('edit_jadwal').value       = d.jadwal_pelaksanaan || '';
    document.getElementById('edit_evaluasi').value     = d.evaluasi_pasca || '';
    document.getElementById('edit_catatan').value      = d.catatan_rekomendasi || '';

    setSelect('edit_kategori', d.kategori_kesenjangan || '');
    setSelect('edit_metode',   d.metode_pelaksanaan   || '');
    setSelect('edit_prioritas',d.prioritas            || 'Sedang');
    setSelect('edit_status',   d.status_pelaksanaan   || 'Belum');

    var fd = document.getElementById('edit_file_display');
    if (d.dokumen_pendukung && d.dokumen_pendukung !== '') {
        fd.innerHTML = '<i class="fas fa-file-alt"></i> <a href="' + d.dokumen_pendukung + '" target="_blank" class="text-info">Lihat Dokumen Tersimpan</a>';
    } else {
        fd.innerHTML = '<span class="text-danger">Belum ada dokumen pendukung.</span>';
    }
    $('#modalEditTna').modal('show');
}

function detailTna(d) {
    document.getElementById('det_kompetensi').innerText   = d.kompetensi_dibutuhkan   || '-';
    document.getElementById('det_kondisi').innerText      = d.kondisi_saat_ini         || '-';
    document.getElementById('det_kategori').innerText     = d.kategori_kesenjangan     || '-';
    document.getElementById('det_usulan').innerText       = d.usulan_pelatihan         || '-';
    document.getElementById('det_materi').innerText       = d.materi_pelatihan         || '-';
    document.getElementById('det_indikator').innerText    = d.indikator_keberhasilan   || '-';
    document.getElementById('det_metode').innerText       = d.metode_pelaksanaan       || '-';
    document.getElementById('det_penyelenggara').innerText= d.penyelenggara            || '-';
    document.getElementById('det_jadwal').innerText       = d.jadwal_pelaksanaan       || '-';
    document.getElementById('det_evaluasi').innerText     = d.evaluasi_pasca           || '-';
    document.getElementById('det_catatan').innerText      = d.catatan_rekomendasi      || '-';

    // Status info
    var pr = d.prioritas || 'Sedang';
    var st = d.status_pelaksanaan || 'Belum';
    var prColor = pr === 'Tinggi' ? 'badge-danger' : (pr === 'Rendah' ? 'badge-secondary' : 'badge-warning');
    document.getElementById('det_status_info').innerHTML =
        '<span class="badge-custom ' + prColor + '">' + pr + '</span> ' +
        '<span class="badge-custom badge-secondary">' + st + '</span>';

    // Persetujuan badge
    var p  = d.status_persetujuan || 'Menunggu Persetujuan';
    var pColor = p === 'Disetujui' ? 'badge-success' : (p === 'Ditolak' ? 'badge-danger' : 'badge-warning');
    document.getElementById('det_persetujuan').innerHTML = '<span class="badge-custom ' + pColor + '">' + p + '</span>';

    // File
    var f = d.dokumen_pendukung;
    document.getElementById('det_file').innerHTML = (f && f !== '')
        ? '<a href="' + f + '" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-external-link-alt"></i> Buka File</a>'
        : '<span style="color:var(--text-muted);">Tidak ada</span>';

    $('#modalDetailTna').modal('show');
}

// Konfirmasi hapus
document.querySelectorAll('.confirm-delete').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm('Yakin ingin menghapus data TNA ini beserta dokumennya?')) {
            e.preventDefault();
        }
    });
});
</script>
<?php $this->endSection() ?>
