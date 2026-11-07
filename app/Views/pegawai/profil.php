<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
$status    = $data['status'] ?? 'DK';
$statusBadge = match($status) {
    'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> Dinas Luar</span>',
    'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
    default=> '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>',
};
function fmtTgl($d) { if (empty($d) || $d === '0000-00-00') return '-'; return date('d F Y', strtotime($d)); }
function docLink($url, $label = 'Lihat Dokumen') {
    if (empty($url)) return '<span style="color:var(--text-muted);">-</span>';
    return '<a href="'.esc($url).'" target="_blank" style="color:var(--accent-light);display:inline-flex;align-items:center;gap:4px;font-size:13px;"><i class="fas fa-file-pdf"></i> '.$label.'</a>';
}
?>

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

<!-- Header Profil -->
<div class="card-dark" style="margin-bottom:20px;">
    <div class="card-body-dark">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
            <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:white;flex-shrink:0;box-shadow:0 0 24px var(--accent-glow);">
                <?= strtoupper(substr($data['nama'] ?? 'P', 0, 1)) ?>
            </div>
            <div style="flex:1;">
                <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;"><?= esc($data['nama'] ?? '-') ?></h2>
                <p style="color:var(--text-muted);font-size:13px;margin-bottom:8px;font-family:monospace;">NIP: <?= esc($data['nip'] ?? '-') ?></p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if (!empty($data['jabatan'])): ?>
                    <span class="badge-custom badge-primary"><i class="fas fa-briefcase"></i> <?= esc($data['jabatan']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($data['golongan'])): ?>
                    <span class="badge-custom badge-secondary">Gol. <?= esc($data['golongan']) ?></span>
                    <?php endif; ?>
                    <?= $statusBadge ?>
                </div>
            </div>
            <?php if ($can_edit || session()->get('nip') === $nip): ?>
            <a href="<?= base_url('user/edit-password/' . urlencode($nip)) ?>" class="btn-warning-dark">
                <i class="fas fa-<?= $can_edit ? 'user-edit' : 'key' ?>"></i> <?= $can_edit ? 'Edit User & Password' : 'Ubah Password' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <!-- Kolom Kiri -->
    <div style="display:flex;flex-direction:column;gap:1px;">

        <!-- Data Pokok -->
        <div class="card-dark">
            <div class="card-header-dark"><h6><i class="fas fa-user header-icon"></i> Data Pokok Pegawai</h6></div>
            <div class="card-body-dark">
                <div class="info-row"><span class="info-label">Status Pegawai</span><span class="info-value"><?= esc($data['status_pegawai'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-label">Tanggal Lahir</span><span class="info-value"><?= fmtTgl($data['tanggal_lahir'] ?? null) ?></span></div>
                <div class="info-row"><span class="info-label">Usia</span><span class="info-value"><?= $usia ?></span></div>
                <div class="info-row"><span class="info-label">TMT Awal</span><span class="info-value"><?= fmtTgl($data['tmt_awal'] ?? null) ?></span></div>
                <div class="info-row"><span class="info-label">TMT Pensiun</span><span class="info-value">
                    <?= (!empty($data['tanggal_lahir']) && $data['tanggal_lahir'] !== '0000-00-00') ? date('d F Y', strtotime('+696 months', strtotime($data['tanggal_lahir']))) : '-' ?>
                </span></div>
                <div class="info-row"><span class="info-label">Pendidikan Terakhir</span><span class="info-value"><?= esc($data['pendidikan_terakhir'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-label">Lulusan</span><span class="info-value"><?= esc($data['lulusan_pendidikan'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-label">SK KGB Terakhir</span><span class="info-value"><?= $kgb['terakhir'] ?></span></div>
                <div class="info-row"><span class="info-label">SK KGB Berikutnya</span><span class="info-value" style="color:var(--warning);"><?= $kgb['berikutnya'] ?></span></div>
            </div>
        </div>

        <!-- Dokumen SK -->
        <div class="card-dark">
            <div class="card-header-dark" style="display:flex;justify-content:space-between;align-items:center;">
                <h6><i class="fas fa-file-alt header-icon"></i> Dokumen SK</h6>
                <?php if ($role === 'pegawai'): ?>
                <button type="button" class="btn-primary-dark" style="padding:6px 12px;font-size:12px;" data-toggle="modal" data-target="#modalUploadSk">
                    <i class="fas fa-upload"></i> Upload SK Saya
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body-dark">
                <?php
                $skList = [
                    'sk_cpns'             => 'SK CPNS',
                    'sk_pns'              => 'SK PNS',
                    'sk_pak'              => 'SK PAK',
                    'sk_tukin'            => 'SK TUKIN',
                    'sk_jabatan_terakhir' => 'SK Jabatan Terakhir',
                    'skp_terakhir'        => 'SKP Terakhir',
                ];
                foreach ($skList as $fieldKey => $fieldLabel):
                    $skUrl = $data[$fieldKey] ?? null;
                ?>
                <div class="info-row">
                    <span class="info-label"><?= $fieldLabel ?></span>
                    <span class="info-value" style="display:flex;align-items:center;gap:8px;">
                        <?= docLink($skUrl) ?>
                        <?php if ($role === 'pegawai'): ?>
                        <button type="button"
                            class="btn btn-sm" 
                            style="padding:2px 8px;font-size:11px;background:rgba(99,102,241,0.2);color:var(--accent-light);border:1px solid var(--accent);border-radius:4px;"
                            onclick="openUploadSk('<?= $fieldKey ?>', '<?= $fieldLabel ?>', '<?= esc($skUrl ?? '', 'attr') ?>')"
                            title="<?= $skUrl ? 'Ganti' : 'Upload' ?> <?= $fieldLabel ?>">
                            <i class="fas fa-<?= $skUrl ? 'sync-alt' : 'upload' ?>"></i>
                            <?= $skUrl ? 'Ganti' : 'Upload' ?>
                        </button>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status Kedinasan -->
        <div class="card-dark">
            <div class="card-header-dark"><h6><i class="fas fa-clipboard-list header-icon"></i> Status Kedinasan</h6></div>
            <div class="card-body-dark">
                <div class="info-row"><span class="info-label">Status</span><span class="info-value"><?= $statusBadge ?></span></div>
                <?php if (!empty($data['status']) && $data['status'] !== 'DK'): ?>
                <div class="info-row"><span class="info-label">Keterangan</span><span class="info-value"><?= esc($data['keterangan'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-label">No. SPT</span><span class="info-value"><?= esc($data['no_spt'] ?? '-') ?></span></div>
                <div class="info-row"><span class="info-label">Tanggal Mulai</span><span class="info-value"><?= fmtTgl($data['tanggal_mulai'] ?? null) ?></span></div>
                <div class="info-row"><span class="info-label">Tanggal Selesai</span><span class="info-value"><?= fmtTgl($data['tanggal_selesai'] ?? null) ?></span></div>
                <?php if (!empty($data['file'])): ?>
                <div class="info-row"><span class="info-label">File Status</span><span class="info-value"><?= docLink($data['file'], 'Lihat File SPT') ?></span></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Data Terkait -->
        <div class="card-dark">
            <div class="card-header-dark"><h6><i class="fas fa-link header-icon"></i> Data Terkait</h6></div>
            <div class="card-body-dark">
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="<?= base_url('diklat/' . urlencode($nip)) ?>" class="btn-primary-dark"><i class="fas fa-graduation-cap"></i> Daftar Diklat</a>
                    <a href="<?= base_url('penghargaan/' . urlencode($nip)) ?>" class="btn-success-dark"><i class="fas fa-award"></i> Penghargaan</a>
                    <a href="<?= base_url('tna/' . urlencode($nip)) ?>" class="btn-info-dark"><i class="fas fa-chalkboard-teacher"></i> Data TNA</a>
                    <a href="<?= base_url('abk/' . urlencode($nip)) ?>" class="btn-warning-dark"><i class="fas fa-briefcase"></i> Data ABK</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Kalender Kedinasan & Rincian Penugasan Kode & SDM 2026 -->
    <?php
    $penList       = $penugasanList ?? [];
    $totalKegiatan = count($penList);
    $totalHariPen  = array_sum(array_map(static fn ($x) => (int) ($x['jumlah_hari'] ?? 0), $penList));
    ?>
    <div style="display:flex;flex-direction:column;gap:1px;">
        <div class="card-dark">
            <div class="card-header-dark">
                <h6>
                    <i class="fas fa-calendar-alt header-icon"></i> Kalender Kedinasan &amp; Kode SDM 2026
                    <span class="badge-custom badge-primary" style="margin-left:6px;"><?= $totalKegiatan ?> Kegiatan · <?= $totalHariPen ?> Hari</span>
                </h6>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="<?= base_url('jadwal-penugasan?nip=' . urlencode($nip)) ?>" class="btn-info-dark btn-sm-custom" title="Lihat Matriks Jadwal Tahunan Pegawai Ini">
                        <i class="fas fa-calendar-check"></i> Matriks Jadwal
                    </a>
                    <a href="<?= base_url('kegiatan') ?>" class="btn-primary-dark btn-sm-custom" title="Buka Halaman Kode dan SDM">
                        <i class="fas fa-clipboard-list"></i> Kode &amp; SDM
                    </a>
                </div>
            </div>
            <div class="card-body-dark">
                <!-- Legenda Warna Kode & SDM -->
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;font-size:11px;color:var(--text-secondary);">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                        <span style="width:11px;height:11px;border-radius:3px;background:#E5B8B7;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 1
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                        <span style="width:11px;height:11px;border-radius:3px;background:#FFFF00;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 2
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                        <span style="width:11px;height:11px;border-radius:3px;background:#92D050;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 3
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                        <span style="width:11px;height:11px;border-radius:3px;background:#B6DDE8;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Subbag Umum
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                        <span style="width:11px;height:11px;border-radius:3px;background:#f59e0b;display:inline-block;"></span> Cuti
                    </span>
                </div>

                <div id="calendar"></div>

                <!-- Box Info Detail Saat Event Kalender Diklik -->
                <div id="calEventDetailBox" style="display:none;margin-top:14px;padding:12px 14px;border-radius:var(--radius-sm);background:var(--bg-secondary);border:1px solid var(--border-accent);font-size:12.5px;"></div>
            </div>
        </div>

        <!-- Daftar Rincian Penugasan dari Kode dan SDM -->
        <div class="card-dark">
            <div class="card-header-dark">
                <h6><i class="fas fa-tasks header-icon"></i> Rincian Penugasan Kode &amp; SDM 2026</h6>
                <span style="font-size:11.5px;color:var(--text-muted);">Klik baris untuk lompat ke tanggal di kalender</span>
            </div>
            <div class="card-body-dark" style="padding:0;">
                <?php if (empty($penList)): ?>
                    <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">Belum ada penugasan pada Kode dan SDM 2026.</div>
                <?php else: ?>
                    <div style="max-height:320px;overflow-y:auto;">
                        <table class="table-dark-custom">
                            <thead>
                                <tr>
                                    <th style="width:64px;text-align:center;">Kode</th>
                                    <th>Kegiatan &amp; Lokus</th>
                                    <th>Peran</th>
                                    <th style="text-align:center;">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($penList as $ps):
                                    $warna = !empty($ps['warna_hex']) ? $ps['warna_hex'] : '#E5B8B7';
                                    $kab   = $ps['kab_kota_lokus'] ?? '-';
                                    $det   = $ps['detail_lokus'] ?? '-';
                                    $lok   = ($det && $det !== '-' && $det !== $kab) ? "{$kab} — {$det}" : $kab;
                                    $tglM  = $ps['tanggal_mulai_tugas'] ?? '';
                                ?>
                                <tr style="cursor:pointer;" onclick="fokusTanggalKalender('<?= esc($tglM, 'attr') ?>', <?= esc(json_encode([
                                    'kode'            => (string) ($ps['kode_kegiatan'] ?? '-'),
                                    'nama_kegiatan'   => $ps['nama_kegiatan'] ?? '-',
                                    'tim_kerja'       => $ps['tim_kerja'] ?? '-',
                                    'peran'           => $ps['kategori_peran'] ?? '-',
                                    'peran_detail'    => $ps['peran_detail'] ?? '',
                                    'lokus'           => $lok,
                                    'zona'            => $ps['zona'] ?? 'NON_ZONA',
                                    'tahap'           => $ps['tahap'] ?? '',
                                    'tanggal_mulai'   => $ps['tanggal_mulai_tugas'] ?? '-',
                                    'tanggal_selesai' => $ps['tanggal_selesai_tugas'] ?? '-',
                                    'jumlah_hari'     => (int) ($ps['jumlah_hari'] ?? 1),
                                    'warna'           => $warna,
                                ]), 'attr') ?>)">
                                    <td style="text-align:center;">
                                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:26px;padding:0 7px;border-radius:6px;font-weight:800;font-size:12px;color:#0f172a;background:<?= esc($warna) ?>;border:1px solid rgba(0,0,0,0.25);">
                                            <?= esc($ps['kode_kegiatan']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600;color:var(--text-primary);font-size:12.5px;"><?= esc($ps['nama_kegiatan']) ?></div>
                                        <small style="color:var(--text-muted);"><i class="fas fa-map-marker-alt"></i> <?= esc($lok) ?><?= (!empty($ps['zona']) && $ps['zona'] !== 'NON_ZONA') ? ' · ' . esc($ps['zona']) : '' ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-custom badge-info"><?= esc($ps['kategori_peran']) ?></span>
                                        <?php if (!empty($ps['tahap'])): ?>
                                            <br><small style="color:var(--text-muted);"><?= esc($ps['tahap']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;white-space:nowrap;font-size:11.5px;">
                                        <span style="color:var(--text-primary);font-weight:600;"><?= esc($ps['tanggal_mulai_tugas'] ?? '-') ?></span><br>
                                        <small style="color:var(--text-muted);">s.d. <?= esc($ps['tanggal_selesai_tugas'] ?? '-') ?> (<?= (int) ($ps['jumlah_hari'] ?? 1) ?> hr)</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($role === 'pegawai'): ?>
<!-- ===================================================================== -->
<!-- MODAL UPLOAD SK MANDIRI                                                -->
<!-- ===================================================================== -->
<div class="modal fade" id="modalUploadSk" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document"><div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title font-weight-bold">
                <i class="fas fa-upload"></i> Upload Dokumen SK
            </h5>
            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <form method="POST" action="<?= base_url('pegawai/upload-sk/' . urlencode($nip)) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="field_sk" id="field_sk_input">
            <div class="modal-body">

                <!-- Info dokumen yang dipilih -->
                <div class="alert alert-info" id="sk_info_box" style="border-radius:8px;margin-bottom:16px;">
                    <i class="fas fa-info-circle"></i>
                    Anda akan mengupload: <strong id="sk_label_display">-</strong>
                </div>

                <!-- File saat ini (jika ada) -->
                <div id="sk_current_file" style="display:none;margin-bottom:16px;padding:10px;background:rgba(99,102,241,0.1);border-radius:8px;border:1px solid var(--accent);">
                    <p style="margin:0;font-size:13px;color:var(--text-muted);">
                        <i class="fas fa-file-alt" style="color:var(--accent);"></i> File saat ini:
                        <a id="sk_current_link" href="#" target="_blank" style="color:var(--accent-light);">Lihat dokumen tersimpan</a>
                    </p>
                    <p style="margin:4px 0 0;font-size:12px;color:var(--warning);">
                        <i class="fas fa-exclamation-triangle"></i> File lama akan diganti jika Anda upload file baru.
                    </p>
                </div>

                <!-- Input file -->
                <div class="form-group">
                    <label class="font-weight-bold">Pilih File: <span class="text-danger">*</span></label>
                    <input type="file" name="file_sk" id="file_sk_input" class="form-control-file" required
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Format yang diizinkan: <strong>PDF, DOC, DOCX, JPG, PNG</strong> &mdash; Maksimal <strong>5 MB</strong>
                    </small>
                </div>

                <!-- Preview nama file -->
                <div id="file_preview" style="display:none;padding:8px 12px;background:rgba(16,185,129,0.1);border-radius:6px;border:1px solid #10b981;margin-top:8px;">
                    <i class="fas fa-file-check" style="color:#10b981;"></i>
                    <span id="file_name_preview" style="font-size:13px;color:#10b981;"></span>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-primary font-weight-bold">
                    <i class="fas fa-cloud-upload-alt"></i> Unggah ke Google Drive
                </button>
            </div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
var calendarEvents = <?= $events ?>;

function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}

function tampilkanDetailKalender(ep) {
    var box = document.getElementById('calEventDetailBox');
    if (!box || !ep) return;
    if (ep.kode) {
        box.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">' +
                '<div>' +
                    '<div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">' +
                        '<span style="display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:22px;padding:0 6px;border-radius:5px;font-weight:800;font-size:11px;color:#0f172a;background:' + escHtml(ep.warna || '#E5B8B7') + ';border:1px solid rgba(0,0,0,0.25);margin-right:8px;">Kode ' + escHtml(ep.kode) + '</span>' +
                        escHtml(ep.nama_kegiatan) +
                    '</div>' +
                    '<div style="color:var(--text-secondary);font-size:12px;">' +
                        '<strong>Tim Kerja:</strong> ' + escHtml(ep.tim_kerja || '-') + ' &nbsp;|&nbsp; ' +
                        '<strong>Peran:</strong> ' + escHtml(ep.peran || '-') + (ep.peran_detail && ep.peran_detail !== ep.peran ? ' (' + escHtml(ep.peran_detail) + ')' : '') +
                        (ep.tahap ? ' · ' + escHtml(ep.tahap) : '') +
                    '</div>' +
                    '<div style="color:var(--text-muted);font-size:12px;margin-top:2px;">' +
                        '<i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> ' + escHtml(ep.lokus || '-') +
                        (ep.zona && ep.zona !== 'NON_ZONA' ? ' [' + escHtml(ep.zona) + ']' : '') +
                        ' &nbsp;·&nbsp; <i class="fas fa-calendar-day" style="color:var(--accent);"></i> ' +
                        escHtml(ep.tanggal_mulai) + ' s.d. ' + escHtml(ep.tanggal_selesai) + ' (' + escHtml(ep.jumlah_hari) + ' hari)' +
                    '</div>' +
                '</div>' +
                '<button type="button" onclick="document.getElementById(\'calEventDetailBox\').style.display=\'none\'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">&times;</button>' +
            '</div>';
    } else {
        box.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div><strong>Status Kedinasan:</strong> ' + escHtml(ep.description || '-') + '</div>' +
                '<button type="button" onclick="document.getElementById(\'calEventDetailBox\').style.display=\'none\'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">&times;</button>' +
            '</div>';
    }
    box.style.display = 'block';
}

window.onSimpegCalendarEventClick = function(ev) {
    tampilkanDetailKalender(ev.extendedProps || {});
};

function fokusTanggalKalender(tgl, ep) {
    if (tgl && window.simpegCalendar) {
        window.simpegCalendar.gotoDate(tgl);
    }
    if (ep) {
        tampilkanDetailKalender(ep);
    }
}

<?php if ($role === 'pegawai'): ?>
function openUploadSk(fieldKey, fieldLabel, currentUrl) {
    document.getElementById('field_sk_input').value   = fieldKey;
    document.getElementById('sk_label_display').textContent = fieldLabel;
    document.getElementById('file_sk_input').value    = '';
    document.getElementById('file_preview').style.display = 'none';

    var currentBox  = document.getElementById('sk_current_file');
    var currentLink = document.getElementById('sk_current_link');

    if (currentUrl && currentUrl !== '') {
        currentBox.style.display  = 'block';
        currentLink.href          = currentUrl;
    } else {
        currentBox.style.display  = 'none';
    }

    $('#modalUploadSk').modal('show');
}

document.getElementById('file_sk_input')?.addEventListener('change', function() {
    var preview  = document.getElementById('file_preview');
    var nameSpan = document.getElementById('file_name_preview');
    if (this.files && this.files[0]) {
        nameSpan.textContent = this.files[0].name + ' (' + (this.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
<?php endif; ?>
</script>
<?php $this->endSection() ?>

