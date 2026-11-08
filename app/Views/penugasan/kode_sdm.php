<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$canEdit = in_array($role, ['super_admin', 'admin'], true);

$jmlDone = 0; $jmlTerjadwal = 0; $jmlSdmTotal = 0;
foreach ($kegiatanList as $k) {
    if ($k['status_kegiatan'] === 'DONE') $jmlDone++; else $jmlTerjadwal++;
    $jmlSdmTotal += (int) ($k['jumlah_sdm'] ?? 0);
}

$statusBadge = static function (string $st): string {
    return match ($st) {
        'DONE'   => '<span class="badge-custom badge-success"><i class="fas fa-check-circle"></i> DONE</span>',
        'DARING' => '<span class="badge-custom badge-info"><i class="fas fa-video"></i> DARING</span>',
        default  => '<span class="badge-custom badge-warning"><i class="fas fa-clock"></i> TERJADWAL</span>',
    };
};
?>

<?= $this->include('penugasan/_partials/tabs') ?>

<style>
    .tbl-kode td { vertical-align:top !important; }
    .tbl-kode tbody tr { box-shadow: inset 3px 0 0 var(--row-color, transparent); }
    .keg-nama { font-weight:600; color:var(--text-primary); line-height:1.45; margin-bottom:6px; }
    .keg-meta { font-size:12px; color:var(--text-muted); white-space:pre-line; line-height:1.5; }
    .sdm-group { background:rgba(255,255,255,0.025); border:1px solid var(--border); border-left:3px solid var(--row-color, var(--accent)); border-radius:var(--radius-sm); padding:8px 10px; margin-bottom:6px; }
    .sdm-group:last-child { margin-bottom:0; }
    .sdm-group-head { display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:11.5px; font-weight:600; color:var(--accent-light); margin-bottom:5px; }
    .sdm-group-head .cnt { font-size:10.5px; font-weight:600; color:var(--text-muted); white-space:nowrap; }
    .sdm-name { display:inline-block; padding:2px 8px; margin:2px 3px 2px 0; border-radius:6px; background:rgba(255,255,255,0.05); border:1px solid var(--border); font-size:11.5px; color:var(--text-primary); }
    .sdm-name small { color:var(--text-muted); margin-left:2px; }
    .sdm-name .role { color:var(--warning); }
    .sdm-toggle { font-size:11.5px; color:var(--accent-light); cursor:pointer; background:none; border:none; padding:4px 0 0; }
    .sdm-collapsed .sdm-group:nth-child(n+4) { display:none; }
    .alias-chips { display:flex; flex-wrap:wrap; gap:6px; }
    .alias-chips .pg-chip { padding:3px 10px; font-size:11.5px; }
</style>

<!-- STATISTIK -->
<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-clipboard-list"></i></div>
        <div><div class="stat-value"><?= count($kegiatanList) ?></div><div class="stat-label">Kode Kegiatan</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-double"></i></div>
        <div><div class="stat-value"><?= $jmlDone ?></div><div class="stat-label">Sudah Terlaksana (DONE)</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-hourglass-half"></i></div>
        <div><div class="stat-value"><?= $jmlTerjadwal ?></div><div class="stat-label">Terjadwal</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-user-check"></i></div>
        <div><div class="stat-value"><?= $jmlSdmTotal ?></div><div class="stat-label">Total Penugasan SDM</div></div>
    </div>
</div>

<!-- FILTER & LEGENDA -->
<div class="card-dark">
    <div class="card-body-dark">
        <div class="pg-toolbar">
            <div class="pg-field grow-2">
                <label class="form-label-dark">Pencarian</label>
                <div class="pg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="inputCariKegiatan" class="form-control-dark sm" placeholder="Kode, nama kegiatan, nama pegawai, kabupaten, sekolah lokus...">
                </div>
            </div>
            <div class="pg-field">
                <label class="form-label-dark">Tim Kerja</label>
                <select id="selectFilterTim" class="form-control-dark sm">
                    <option value="">Semua Tim Kerja</option>
                    <?php foreach ($timKerjaOptions as $timLabel => $hex): ?>
                        <option value="<?= esc($timLabel) ?>"><?= esc($timLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pg-field">
                <label class="form-label-dark">Status (KET)</label>
                <select id="selectFilterStatus" class="form-control-dark sm">
                    <option value="">Semua Status</option>
                    <option value="DONE">DONE</option>
                    <option value="TERJADWAL">TERJADWAL</option>
                    <option value="DARING">DARING</option>
                </select>
            </div>
        </div>

        <hr class="divider" style="margin:16px 0 12px;">

        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
            <span class="section-title" style="margin:0 6px 0 0;">Keterangan Warna</span>
            <?php foreach ($timKerjaOptions as $timLabel => $hex): if ($timLabel === 'Lainnya') continue; ?>
                <span class="pg-chip js-legend" data-tim="<?= esc($timLabel) ?>">
                    <span class="pg-swatch" style="background:<?= esc($hex) ?>;"></span> <?= esc($timLabel) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- TABEL KODE DAN SDM -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-table header-icon"></i> Daftar Kegiatan BGTK Provinsi Lampung 2026</h5>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <span class="pg-hint" id="infoJumlahTampil"><?= count($kegiatanList) ?> kegiatan ditampilkan</span>
            <?php if ($canEdit): ?>
                <button type="button" class="btn-primary-dark" data-toggle="modal" data-target="#modalTambahKegiatan">
                    <i class="fas fa-plus"></i> Tambah Kegiatan
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div class="pg-scroll">
            <table class="table-dark-custom tbl-kode" id="tblKodeSdm">
                <thead>
                    <tr>
                        <th style="text-align:center;width:70px;">Kode</th>
                        <th style="min-width:220px;">Nama Kegiatan</th>
                        <th style="min-width:150px;">Jumlah Orang</th>
                        <th style="min-width:340px;">Nama (Rincian SDM per Peran / Kabupaten / Lokus)</th>
                        <th style="min-width:150px;">Tanggal Pelaksanaan</th>
                        <th style="min-width:150px;">Tempat Kegiatan</th>
                        <th style="text-align:center;">Ket</th>
                        <?php if ($canEdit): ?><th style="text-align:center;width:130px;">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($kegiatanList as $k):
                    $warna   = $k['warna_hex'] ?: '#E5B8B7';
                    $grouped = $k['grouped_sdm'] ?? [];
                    $jmlSdm  = (int) ($k['jumlah_sdm'] ?? 0);
                    $timHex  = $timKerjaOptions[$k['tim_kerja'] ?? ''] ?? '#D9D9D9';
                ?>
                    <tr class="row-kegiatan" style="--row-color:<?= esc($warna) ?>;"
                        data-tim="<?= esc($k['tim_kerja'] ?? '') ?>" data-status="<?= esc($k['status_kegiatan']) ?>">
                        <td style="text-align:center;">
                            <span class="kode-badge" style="background:<?= esc($warna) ?>;"><?= esc($k['kode_kegiatan']) ?></span>
                        </td>
                        <td>
                            <div class="keg-nama"><?= esc($k['nama_kegiatan']) ?></div>
                            <span class="badge-custom badge-secondary" title="Tim Kerja">
                                <span class="pg-swatch" style="background:<?= esc($timHex) ?>;width:9px;height:9px;"></span>
                                <?= esc($k['tim_kerja'] ?: 'Lainnya') ?>
                            </span>
                        </td>
                        <td>
                            <div class="keg-meta" style="color:var(--text-secondary);margin-bottom:6px;"><?= esc(str_replace(' | ', "\n", $k['kuota_keterangan'] ?: '-')) ?></div>
                            <span class="badge-custom <?= $jmlSdm > 0 ? 'badge-primary' : 'badge-secondary' ?>"><i class="fas fa-users"></i> <?= $jmlSdm ?> SDM</span>
                        </td>
                        <td>
                            <?php if (!empty($grouped)): ?>
                                <div class="sdm-groups <?= count($grouped) > 3 ? 'sdm-collapsed' : '' ?>">
                                    <?php foreach ($grouped as $groupTitle => $members): ?>
                                        <div class="sdm-group">
                                            <div class="sdm-group-head">
                                                <span><?= esc($groupTitle) ?></span>
                                                <span class="cnt"><?= count($members) ?> org</span>
                                            </div>
                                            <?php foreach ($members as $m):
                                                $showRole = !empty($m['peran_detail']) && preg_match('/\((PJ|Ketua|Sekretaris)\)/', $m['peran_detail'], $mm);
                                            ?>
                                                <span class="sdm-name" title="NIP <?= esc($m['nip']) ?> · <?= esc($m['tanggal_mulai_tugas']) ?> s.d. <?= esc($m['tanggal_selesai_tugas']) ?>">
                                                    <?= esc($m['nama_pegawai']) ?><?php if ($showRole): ?> <span class="role">(<?= esc($mm[1]) ?>)</span><?php endif; ?>
                                                    <small><?= (int) $m['jumlah_hari'] ?>h</small>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($grouped) > 3): ?>
                                    <button type="button" class="sdm-toggle js-toggle-sdm">
                                        <i class="fas fa-chevron-down"></i> Tampilkan semua (<?= count($grouped) ?> kelompok)
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:12px;color:var(--text-muted);font-style:italic;">
                                    <?php if (in_array($k['kode_kegiatan'], ['38', '43'], true)): ?>
                                        Seluruh pegawai — gunakan <strong>Kelola SDM → Plot Seluruh Pegawai</strong>.
                                    <?php elseif ($k['kode_kegiatan'] === '40'): ?>
                                        Fasilitator: Diana, Nuryulinda · Panitia: Rino, Santi, Nabila (magang).
                                    <?php elseif ($k['kode_kegiatan'] === '41'): ?>
                                        Fasilitator: Anang, Yusuf · Panitia: Ronaldo, Elva (magang, non-pegawai).
                                    <?php else: ?>
                                        Belum ada SDM terplot.
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--text-primary);font-size:12.5px;white-space:pre-line;"><?= esc(str_replace(' | ', "\n", $k['rentang_tanggal_teks'] ?: '-')) ?></div>
                            <?php if (!empty($k['tanggal_mulai'])): ?>
                                <div class="keg-meta" style="margin-top:4px;"><i class="far fa-calendar-alt"></i> <?= esc($k['tanggal_mulai']) ?> → <?= esc($k['tanggal_selesai']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="keg-meta" style="color:var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> <?= esc(str_replace(' | ', "\n", $k['tempat_lokus'] ?: '-')) ?></div>
                        </td>
                        <td style="text-align:center;">
                            <?= $statusBadge($k['status_kegiatan']) ?>
                            <?php if (!empty($k['catatan_ket']) && $k['catatan_ket'] !== $k['status_kegiatan'] && $k['catatan_ket'] !== '-'): ?>
                                <div class="keg-meta" style="margin-top:4px;font-size:11px;"><?= esc($k['catatan_ket']) ?></div>
                            <?php endif; ?>
                        </td>
                        <?php if ($canEdit):
                            $kMeta = array_intersect_key($k, array_flip([
                                'id_kegiatan', 'kode_kegiatan', 'nama_kegiatan', 'tim_kerja', 'warna_hex', 'kuota_keterangan',
                                'tanggal_mulai', 'tanggal_selesai', 'rentang_tanggal_teks', 'tempat_lokus', 'status_kegiatan', 'catatan_ket',
                            ]));
                        ?>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                <button type="button" class="btn-primary-dark btn-sm-custom" style="justify-content:center;" onclick="bukaModalKelolaSdm(<?= (int) $k['id_kegiatan'] ?>)">
                                    <i class="fas fa-users-cog"></i> Kelola SDM
                                </button>
                                <div style="display:flex;gap:6px;">
                                    <button type="button" class="btn-warning-dark btn-sm-custom" style="flex:1;justify-content:center;"
                                        onclick='bukaModalEditKegiatan(<?= json_encode($kMeta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="<?= base_url('kegiatan/hapus/' . $k['id_kegiatan']) ?>" class="btn-danger-dark btn-sm-custom js-hapus-kegiatan"
                                       data-label="Kode <?= esc($k['kode_kegiatan']) ?> — <?= esc($k['nama_kegiatan']) ?>" data-jml="<?= $jmlSdm ?>" title="Hapus kegiatan beserta seluruh penugasannya">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pg-empty" id="emptyKegiatan" style="display:none;"><i class="fas fa-search"></i> Tidak ada kegiatan yang cocok dengan filter.</div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<?php
// Field form kegiatan dipakai bersama oleh modal Tambah & Edit
$kegForm = static function (string $p, array $timOpts): string {
    ob_start(); ?>
    <div class="row">
        <div class="col-md-3 form-group">
            <label class="form-label-dark">Kode Kegiatan *</label>
            <input type="text" name="kode_kegiatan" id="<?= $p ?>Kode" class="form-control-dark sm" placeholder="44" required>
        </div>
        <div class="col-md-9 form-group">
            <label class="form-label-dark">Nama Kegiatan *</label>
            <input type="text" name="nama_kegiatan" id="<?= $p ?>Nama" class="form-control-dark sm" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 form-group">
            <label class="form-label-dark">Tim Kerja</label>
            <select name="tim_kerja" id="<?= $p ?>Tim" class="form-control-dark sm" onchange="syncWarnaTim(this.value, '<?= $p ?>Warna')">
                <?php foreach ($timOpts as $label => $hex): ?>
                    <option value="<?= esc($label) ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 form-group">
            <label class="form-label-dark">Warna Sel Jadwal</label>
            <input type="color" name="warna_hex" id="<?= $p ?>Warna" class="form-control-dark sm" value="#E5B8B7" style="height:36px;padding:3px;">
        </div>
        <div class="col-md-3 form-group">
            <label class="form-label-dark">Status (KET)</label>
            <select name="status_kegiatan" id="<?= $p ?>Status" class="form-control-dark sm">
                <option value="TERJADWAL">TERJADWAL</option>
                <option value="DONE">DONE</option>
                <option value="DARING">DARING</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 form-group">
            <label class="form-label-dark">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" id="<?= $p ?>Mulai" class="form-control-dark sm" onchange="autoSyncRentangTeks('<?= $p ?>')">
        </div>
        <div class="col-md-4 form-group">
            <label class="form-label-dark">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" id="<?= $p ?>Selesai" class="form-control-dark sm" onchange="autoSyncRentangTeks('<?= $p ?>')">
        </div>
        <div class="col-md-4 form-group">
            <label class="form-label-dark">Teks Tanggal (opsional)</label>
            <input type="text" name="rentang_tanggal_teks" id="<?= $p ?>Rentang" class="form-control-dark sm" placeholder="TAHAP 1 : 5-7 Nov | TAHAP 2 : ...">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 form-group">
            <label class="form-label-dark">Jumlah Orang / Kuota</label>
            <textarea name="kuota_keterangan" id="<?= $p ?>Kuota" class="form-control-dark sm" rows="2" placeholder="Panitia : 4 | Peserta : 30"></textarea>
        </div>
        <div class="col-md-6 form-group">
            <label class="form-label-dark">Tempat Kegiatan</label>
            <textarea name="tempat_lokus" id="<?= $p ?>Tempat" class="form-control-dark sm" rows="2" placeholder="Azana Hotel (Bandar Lampung)"></textarea>
        </div>
    </div>
    <div class="form-group" style="margin-bottom:0;">
        <label class="form-label-dark">Catatan Keterangan</label>
        <input type="text" name="catatan_ket" id="<?= $p ?>Catatan" class="form-control-dark sm" placeholder="7 Kelas 6 lokus / DONE">
    </div>
    <?php return ob_get_clean();
};
?>

<!-- MODAL TAMBAH KEGIATAN -->
<div class="modal fade" id="modalTambahKegiatan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= base_url('kegiatan/simpan') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle" style="color:var(--accent);margin-right:8px;"></i>Tambah Kegiatan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body"><?= $kegForm('add', $timKerjaOptions) ?></div>
            <div class="modal-footer">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KEGIATAN -->
<div class="modal fade" id="modalEditKegiatan" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="<?= base_url('kegiatan/update') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id_kegiatan" id="editId">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit" style="color:var(--warning);margin-right:8px;"></i>Edit Kegiatan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <?= $kegForm('edit', $timKerjaOptions) ?>
                <div style="margin-top:12px;padding:10px 12px;border-radius:var(--radius-sm);background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.28);">
                    <input type="hidden" name="sync_sdm_dates" value="0">
                    <label style="display:flex;align-items:center;gap:8px;margin:0;cursor:pointer;font-size:12.5px;color:var(--text-primary);font-weight:600;">
                        <input type="checkbox" name="sync_sdm_dates" id="editSyncSdmDates" value="1" checked style="width:16px;height:16px;accent-color:var(--accent);">
                        <span>Sinkronkan otomatis Tanggal Mulai, Tanggal Selesai &amp; Jumlah Hari ke seluruh SDM pada kegiatan ini</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL KELOLA SDM -->
<div class="modal fade" id="modalKelolaSdm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" style="display:flex;align-items:center;gap:10px;">
                    <span class="kode-badge sm" id="sdmModalKode">-</span>
                    <span id="sdmModalTitle">Kelola SDM</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="pg-panel" style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                        <div class="pg-panel-title" style="margin:0;"><i class="fas fa-user-plus"></i> Tambah Penugasan Pegawai</div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn-info-dark btn-sm-custom" onclick="jalankanBulkPlot('peserta_selain_panitia')">
                                <i class="fas fa-bolt"></i> Plot Semua selain Panitia (Peserta Hotel)
                            </button>
                            <button type="button" class="btn-warning-dark btn-sm-custom" onclick="jalankanBulkPlot('seluruh_dinas_luar')">
                                <i class="fas fa-globe-asia"></i> Plot Seluruh Pegawai (Dinas Luar)
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label class="form-label-dark">Pilih cepat nama panggilan</label>
                        <div class="alias-chips">
                            <?php
                            $quickAliases = [
                                'Kepala' => 'Hendra Apriawan', 'Linda' => 'Nuryulinda', 'Feris' => 'Ferismayanti',
                                'Bu Wati' => 'Tentrem', 'Cecil' => 'Cecilia', 'Emil' => 'Emilia', 'Wibi' => 'Tri Wibawanto',
                                'Nurning' => 'Nurningsih', 'Orin' => 'Lilis', 'Echi' => 'Destri', 'Indah' => 'Nur Indah',
                                'Sohar' => 'Agus Sohar', 'Yatmi' => 'Suyatmi', 'Rahman' => 'Ursida',
                                'Tika' => 'Kartika', 'Pipit' => 'Vidhya', 'Awid' => 'Awid', 'Nisa' => 'Anisa Nurul',
                                'Ial' => 'Syahrial', 'Rina' => 'Febrina', 'Sasa' => 'Raisha', 'Danur' => 'Bursadanur',
                                'Fadil' => 'Fadhillah', 'Zul' => 'Zulkarnain', 'Ali' => 'Alie', 'Santi' => 'Santi', 'Yuli' => 'Yuliana',
                            ];
                            foreach ($quickAliases as $aliasLabel => $target): ?>
                                <span class="pg-chip" onclick="pilihDariAlias(<?= esc(json_encode($target), 'attr') ?>)"><?= esc($aliasLabel) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <form id="formTambahSdmKegiatan" onsubmit="submitTambahSdm(event)">
                        <input type="hidden" name="id_kegiatan" id="sdmIdKegiatan">
                        <div class="row">
                            <div class="col-lg-4 form-group">
                                <label class="form-label-dark">Pegawai *</label>
                                <div class="pg-search" style="margin-bottom:6px;">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="filterDropdownPegawai" class="form-control-dark sm" placeholder="Ketik nama / alias (Bu Wati, Orin, Danur)..." oninput="filterOpsiPegawai(this.value)">
                                </div>
                                <select name="nip" id="sdmSelectNip" class="form-control-dark sm" required>
                                    <option value="">— Pilih pegawai —</option>
                                    <?php foreach ($pegawaiList as $p): ?>
                                        <option value="<?= esc($p['nip']) ?>" data-nama="<?= esc(strtolower($p['nama'])) ?>">#<?= (int) $p['no_urut_rekap'] ?> — <?= esc($p['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 form-group">
                                <label class="form-label-dark">Kategori Peran *</label>
                                <select name="kategori_peran" id="sdmKategoriPeran" class="form-control-dark sm" onchange="updateAutoZona()" style="margin-bottom:6px;">
                                    <option value="Dinas Luar">Dinas Luar (Zona 1–4)</option>
                                    <option value="Panitia Hotel">Panitia Hotel</option>
                                    <option value="Panitia UPT">Panitia UPT</option>
                                    <option value="Fasilitator">Fasilitator</option>
                                    <option value="Peserta Hotel">Peserta Hotel</option>
                                    <option value="Peserta UPT">Peserta UPT</option>
                                </select>
                                <input type="text" name="peran_detail" id="sdmPeranDetail" class="form-control-dark sm" placeholder="Jabatan: PJ / Ketua / Pemantau">
                            </div>
                            <div class="col-lg-3 form-group">
                                <label class="form-label-dark">Kab/Kota Lokus <span id="badgeAutoZona" class="badge-custom badge-info" style="margin-left:4px;">Z1</span></label>
                                <select name="kab_kota_lokus" id="sdmKabKota" class="form-control-dark sm" onchange="updateAutoZona()" style="margin-bottom:6px;">
                                    <?php foreach ($kabKotaMap as $kab => $z): ?>
                                        <option value="<?= esc($kab) ?>" data-zona="<?= esc($z) ?>"><?= esc($kab) ?> (<?= esc($z) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="detail_lokus" id="sdmDetailLokus" class="form-control-dark sm" placeholder="Sekolah / hotel lokus">
                            </div>
                            <div class="col-lg-2 form-group">
                                <label class="form-label-dark">Tahap</label>
                                <select name="tahap" id="sdmTahap" class="form-control-dark sm">
                                    <option value="">—</option>
                                    <option value="Tahap 1">Tahap 1</option>
                                    <option value="Tahap 2">Tahap 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="align-items:flex-end;">
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Tanggal Mulai Tugas</label>
                                <input type="date" name="tanggal_mulai_tugas" id="sdmTglMulai" class="form-control-dark sm" onchange="hitungHariModal()">
                            </div>
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Tanggal Selesai Tugas</label>
                                <input type="date" name="tanggal_selesai_tugas" id="sdmTglSelesai" class="form-control-dark sm" onchange="hitungHariModal()">
                            </div>
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <span class="badge-custom badge-warning" style="padding:8px 12px;"><i class="fas fa-calendar-day"></i> <span id="labelAutoHari">1 hari</span></span>
                            </div>
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <button type="submit" class="btn-success-dark" style="width:100%;justify-content:center;padding:9px 14px;">
                                    <i class="fas fa-plus"></i> Tambahkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div class="section-title" style="margin:0;">SDM Terplot <span id="sdmCount" class="badge-custom badge-primary" style="margin-left:6px;">0</span></div>
                </div>
                <div class="pg-scroll" style="max-height:360px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Pegawai</th>
                                <th>Peran</th>
                                <th>Lokus</th>
                                <th style="text-align:center;">Zona</th>
                                <th style="text-align:center;">Tahap</th>
                                <th style="text-align:center;">Tanggal</th>
                                <th style="text-align:center;">Hari</th>
                                <th style="text-align:center;width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyRincianSdmKegiatan"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary-dark" onclick="window.location.reload()"><i class="fas fa-sync-alt"></i> Selesai &amp; Muat Ulang</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('penugasan/_partials/js_helpers') ?>
<script>
const ALIAS_MAP = <?= json_encode($aliasMap) ?>;
const TIM_WARNA = <?= json_encode($timKerjaOptions) ?>;

// ── Filter tabel ────────────────────────────────────────────────────
function applyTableFilter() {
    const q   = document.getElementById('inputCariKegiatan').value.toLowerCase();
    const tim = document.getElementById('selectFilterTim').value;
    const st  = document.getElementById('selectFilterStatus').value;
    let shown = 0;
    document.querySelectorAll('#tblKodeSdm tbody tr.row-kegiatan').forEach(tr => {
        const ok = (!q || tr.innerText.toLowerCase().includes(q))
                && (!tim || tr.dataset.tim === tim)
                && (!st || tr.dataset.status === st);
        tr.style.display = ok ? '' : 'none';
        if (ok) shown++;
    });
    document.getElementById('infoJumlahTampil').textContent = shown + ' kegiatan ditampilkan';
    document.getElementById('emptyKegiatan').style.display = shown ? 'none' : 'block';
    document.querySelectorAll('.js-legend').forEach(c => c.classList.toggle('active', c.dataset.tim === tim));
}
document.getElementById('inputCariKegiatan').addEventListener('input', applyTableFilter);
document.getElementById('selectFilterTim').addEventListener('change', applyTableFilter);
document.getElementById('selectFilterStatus').addEventListener('change', applyTableFilter);
document.querySelectorAll('.js-legend').forEach(chip => chip.addEventListener('click', () => {
    const sel = document.getElementById('selectFilterTim');
    sel.value = sel.value === chip.dataset.tim ? '' : chip.dataset.tim;
    applyTableFilter();
}));
document.querySelectorAll('.js-toggle-sdm').forEach(btn => btn.addEventListener('click', () => {
    const box = btn.previousElementSibling;
    const collapsed = box.classList.toggle('sdm-collapsed');
    btn.innerHTML = collapsed
        ? `<i class="fas fa-chevron-down"></i> Tampilkan semua (${box.children.length} kelompok)`
        : '<i class="fas fa-chevron-up"></i> Ringkas';
}));

<?php if ($canEdit): ?>
// ── Kegiatan ────────────────────────────────────────────────────────
document.querySelectorAll('.js-hapus-kegiatan').forEach(a => a.addEventListener('click', e => {
    e.preventDefault();
    const jml = Number(a.dataset.jml || 0);
    pgConfirm(`${a.dataset.label} akan dihapus${jml ? ` beserta ${jml} penugasan SDM-nya` : ''}. Tindakan ini tidak bisa dibatalkan.`,
        { title: 'Hapus Kegiatan', okText: 'Hapus', danger: true })
        .then(ok => { if (ok) window.location.href = a.href; });
}));

function syncWarnaTim(timVal, targetId) {
    if (TIM_WARNA[timVal]) document.getElementById(targetId).value = TIM_WARNA[timVal];
}

function autoSyncRentangTeks(prefix) {
    const m = document.getElementById(prefix + 'Mulai').value;
    const s = document.getElementById(prefix + 'Selesai').value;
    if (!m || !s) return;
    const d1 = new Date(m + 'T00:00:00');
    const d2 = new Date(s + 'T00:00:00');
    if (isNaN(d1) || isNaN(d2)) return;
    const bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    let txt = '';
    if (d1.getTime() === d2.getTime()) {
        txt = `${d1.getDate()} ${bln[d1.getMonth()]} ${d1.getFullYear()}`;
    } else if (d1.getMonth() === d2.getMonth() && d1.getFullYear() === d2.getFullYear()) {
        txt = `${d1.getDate()} - ${d2.getDate()} ${bln[d1.getMonth()]} ${d1.getFullYear()}`;
    } else if (d1.getFullYear() === d2.getFullYear()) {
        txt = `${d1.getDate()} ${bln[d1.getMonth()]} - ${d2.getDate()} ${bln[d2.getMonth()]} ${d1.getFullYear()}`;
    } else {
        txt = `${d1.getDate()} ${bln[d1.getMonth()]} ${d1.getFullYear()} - ${d2.getDate()} ${bln[d2.getMonth()]} ${d2.getFullYear()}`;
    }
    document.getElementById(prefix + 'Rentang').value = txt;
}

function bukaModalEditKegiatan(k) {
    const set = (id, v) => { document.getElementById(id).value = v ?? ''; };
    set('editId', k.id_kegiatan);
    set('editKode', k.kode_kegiatan);
    set('editNama', k.nama_kegiatan);
    set('editTim', k.tim_kerja || 'Lainnya');
    set('editWarna', k.warna_hex || '#E5B8B7');
    set('editStatus', k.status_kegiatan || 'TERJADWAL');
    set('editMulai', k.tanggal_mulai);
    set('editSelesai', k.tanggal_selesai);
    set('editRentang', k.rentang_tanggal_teks);
    set('editKuota', k.kuota_keterangan);
    set('editTempat', k.tempat_lokus);
    set('editCatatan', k.catatan_ket);
    // Default centang sinkronisasi SDM kecuali untuk kegiatan multi-tahap (Kode 8 & 34)
    const syncCb = document.getElementById('editSyncSdmDates');
    if (syncCb) syncCb.checked = !['8', '34'].includes(String(k.kode_kegiatan));
    $('#modalEditKegiatan').modal('show');
}

// ── Kelola SDM ──────────────────────────────────────────────────────
let currentModalIdKegiatan = 0;

function updateAutoZona() {
    const peran = document.getElementById('sdmKategoriPeran').value;
    const opt = document.getElementById('sdmKabKota').selectedOptions[0];
    const badge = document.getElementById('badgeAutoZona');
    const isDL = peran === 'Dinas Luar';
    badge.textContent = isDL ? (opt ? opt.dataset.zona : 'Z1') : 'NON_ZONA';
    badge.className = 'badge-custom ' + (isDL ? 'badge-info' : 'badge-secondary');
}

function hitungHariModal() {
    const n = pgHitungHari(document.getElementById('sdmTglMulai').value, document.getElementById('sdmTglSelesai').value);
    document.getElementById('labelAutoHari').textContent = n + ' hari';
}

function filterOpsiPegawai(keyword) {
    let q = (keyword || '').trim().toLowerCase();
    if (ALIAS_MAP[q]) q = ALIAS_MAP[q].toLowerCase();
    const sel = document.getElementById('sdmSelectNip');
    let first = null;
    Array.from(sel.options).slice(1).forEach(opt => {
        const vis = !q || (opt.dataset.nama || '').includes(q);
        opt.hidden = !vis;
        if (vis && !first) first = opt.value;
    });
    if (q && first) sel.value = first;
}

function pilihDariAlias(name) {
    document.getElementById('filterDropdownPegawai').value = name;
    filterOpsiPegawai(name);
}

function bukaModalKelolaSdm(idKegiatan) {
    currentModalIdKegiatan = idKegiatan;
    document.getElementById('sdmIdKegiatan').value = idKegiatan;
    document.getElementById('tbodyRincianSdmKegiatan').innerHTML = '<tr><td colspan="9" class="pg-empty"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>';
    $('#modalKelolaSdm').modal('show');
    muatRincianKegiatan(idKegiatan, true);
}

function muatRincianKegiatan(idKegiatan, resetForm = false) {
    fetch('<?= base_url('kegiatan/rincian') ?>/' + idKegiatan)
        .then(r => r.json())
        .then(res => {
            if (res.status !== 'ok') { pgToast(res.message || 'Gagal memuat data', 'danger'); return; }
            const k = res.kegiatan;
            const kb = document.getElementById('sdmModalKode');
            kb.textContent = k.kode_kegiatan;
            kb.style.background = k.warna_hex || '#E5B8B7';
            document.getElementById('sdmModalTitle').textContent = k.nama_kegiatan;
            if (resetForm) {
                document.getElementById('sdmTglMulai').value = k.tanggal_mulai || '';
                document.getElementById('sdmTglSelesai').value = k.tanggal_selesai || k.tanggal_mulai || '';
                document.getElementById('sdmDetailLokus').value = '';
                hitungHariModal();
                updateAutoZona();
            }

            const rows = res.penugasan || [];
            document.getElementById('sdmCount').textContent = rows.length;
            const tbody = document.getElementById('tbodyRincianSdmKegiatan');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="pg-empty">Belum ada pegawai yang di-plot pada kegiatan ini.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(p => `
                <tr>
                    <td style="text-align:center;color:var(--text-muted);">${h(p.no_urut_rekap)}</td>
                    <td><span style="color:var(--text-primary);font-weight:500;">${h(p.nama_pegawai)}</span><br><small style="color:var(--text-muted);font-family:monospace;">${h(p.nip)}</small></td>
                    <td>${pgRoleBadge(p.kategori_peran)}<br><small style="color:var(--text-muted);">${h(p.peran_detail)}</small></td>
                    <td><span style="color:var(--text-primary);">${h(p.kab_kota_lokus || '-')}</span><br><small style="color:var(--text-muted);">${h(p.detail_lokus || '-')}</small></td>
                    <td style="text-align:center;"><span class="badge-custom ${p.zona === 'NON_ZONA' ? 'badge-secondary' : 'badge-info'}">${h(p.zona)}</span></td>
                    <td style="text-align:center;">${h(p.tahap || '—')}</td>
                    <td style="text-align:center;font-size:12px;">${h(p.tanggal_mulai_tugas)}<br>${h(p.tanggal_selesai_tugas)}</td>
                    <td style="text-align:center;color:var(--text-primary);font-weight:700;">${h(p.jumlah_hari)}</td>
                    <td style="text-align:center;">
                        <button type="button" class="btn-danger-dark btn-sm-custom" title="Hapus" onclick="hapusSdmAjax(${Number(p.id_penugasan)}, '${h(p.nama_pegawai).replace(/'/g, '&#39;')}')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`).join('');
        });
}

function submitTambahSdm(e) {
    e.preventDefault();
    pgPost('<?= base_url('kegiatan/penugasan/simpan') ?>', new FormData(e.target)).then(res => {
        if (res.status === 'ok') {
            pgToast(res.message);
            document.getElementById('sdmSelectNip').value = '';
            document.getElementById('filterDropdownPegawai').value = '';
            filterOpsiPegawai('');
            muatRincianKegiatan(currentModalIdKegiatan);
        } else {
            pgToast(res.message || 'Gagal menyimpan penugasan', 'danger');
        }
    });
}

function hapusSdmAjax(id, nama) {
    pgConfirm(`Hapus penugasan ${nama} dari kegiatan ini?`, { title: 'Hapus Penugasan', okText: 'Hapus', danger: true }).then(ok => {
        if (!ok) return;
        pgPost('<?= base_url('kegiatan/penugasan/hapus') ?>/' + id).then(res => {
            pgToast(res.message || 'Selesai', res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') muatRincianKegiatan(currentModalIdKegiatan);
        });
    });
}

function jalankanBulkPlot(mode) {
    const msg = mode === 'seluruh_dinas_luar'
        ? 'Plot seluruh pegawai yang belum terdaftar sebagai Dinas Luar pada kegiatan ini?'
        : 'Plot seluruh pegawai yang belum terdaftar (selain panitia) sebagai Peserta Hotel?';
    pgConfirm(msg, { title: 'Plot Seluruh Pegawai', okText: 'Plot Sekarang' }).then(ok => {
        if (!ok) return;
        pgPost('<?= base_url('kegiatan/penugasan/bulk') ?>', {
            id_kegiatan: currentModalIdKegiatan,
            mode,
            kab_kota_lokus: document.getElementById('sdmKabKota').value,
            detail_lokus: document.getElementById('sdmDetailLokus').value,
            tanggal_mulai_tugas: document.getElementById('sdmTglMulai').value,
            tanggal_selesai_tugas: document.getElementById('sdmTglSelesai').value,
        }).then(res => {
            pgToast(res.message || 'Selesai', res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') muatRincianKegiatan(currentModalIdKegiatan);
        });
    });
}
<?php endif; ?>
</script>
<?= $this->endSection() ?>
