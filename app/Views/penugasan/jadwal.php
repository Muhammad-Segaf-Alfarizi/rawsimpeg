<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$canEdit = in_array($role, ['super_admin', 'admin'], true);

$namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// Data ringkas untuk dirender di browser (hindari 20.000+ <td> dari server)
$calendarMap   = $jadwalData['calendar_map'] ?? [];
$totalKegByNip = $jadwalData['total_keg_nip'] ?? [];

$kegById = [];
foreach ($kegiatanList as $k) {
    $kegById[$k['id_kegiatan']] = [
        'kode'  => (string) $k['kode_kegiatan'],
        'nama'  => $k['nama_kegiatan'],
        'warna' => $k['warna_hex'] ?: '#E5B8B7',
    ];
}

$rows = [];
foreach ($jadwalData['pegawai'] ?? [] as $i => $peg) {
    $tasks = [];
    foreach ($calendarMap[$peg['nip']] ?? [] as $dayTasks) {
        foreach ($dayTasks as $t) {
            $tasks[$t['id_penugasan']] = [
                'id'    => (int) $t['id_penugasan'],
                'idk'   => (int) $t['id_kegiatan'],
                'peran' => $t['kategori_peran'],
                'kab'   => $t['kab_kota_lokus'],
                'det'   => $t['detail_lokus'],
                'zona'  => $t['zona'],
                'tahap' => $t['tahap'],
                'm'     => $t['tgl_mulai'],
                's'     => $t['tgl_selesai'],
                'hari'  => (int) $t['jumlah_hari'],
            ];
        }
    }
    $rows[] = [
        'no'    => (int) ($peg['no_urut_rekap'] ?: $i + 1),
        'nip'   => $peg['nip'],
        'nama'  => $peg['nama'],
        'total' => (int) ($totalKegByNip[$peg['nip']] ?? 0),
        'tasks' => array_values($tasks),
    ];
}

$baseQuery = ['bulan' => $bulanFilter ?? 'all', 'nip' => $nipFilter, 'kode' => $kodeFilter];
$qs = static fn (array $over): string => base_url('jadwal-penugasan?' . http_build_query(array_merge($baseQuery, $over)));
?>

<?= $this->include('penugasan/_partials/tabs') ?>

<style>
    .month-chips { display:flex; flex-wrap:wrap; gap:6px; }
    .jw-wrap { overflow:auto; max-height:72vh; }
    .tbl-jadwal { border-collapse:separate; border-spacing:0; width:max-content; min-width:100%; font-size:11px; }
    .tbl-jadwal th, .tbl-jadwal td { border-right:1px solid var(--border); border-bottom:1px solid var(--border); text-align:center; vertical-align:middle; padding:0; }
    .tbl-jadwal thead th { position:sticky; background:var(--bg-secondary); color:var(--text-muted); font-weight:600; z-index:4; }
    .tbl-jadwal thead tr.h-month th { top:0; height:34px; color:var(--text-primary); font-size:11px; letter-spacing:1px; text-transform:uppercase; border-bottom:2px solid var(--accent); }
    .tbl-jadwal thead tr.h-day th { top:34px; height:26px; min-width:26px; width:26px; font-size:10px; }
    .tbl-jadwal thead tr.h-day th.wkend { color:var(--danger); background:#1f1b28; }
    [data-theme="light"] .tbl-jadwal thead tr.h-day th.wkend { background:#fef2f2; color:var(--danger); }
    .tbl-jadwal td.c { min-width:26px; width:26px; height:28px; font-weight:800; position:relative; }
    .tbl-jadwal td.c.wkend { background:rgba(255,255,255,0.015); }
    [data-theme="light"] .tbl-jadwal td.c.wkend { background:rgba(239,68,68,0.04); }
    .tbl-jadwal td.c.f { color:#0f172a; box-shadow:inset 0 0 0 1px rgba(0,0,0,0.15); }
    .tbl-jadwal.editable td.c { cursor:pointer; }
    .tbl-jadwal.editable td.c:hover { outline:2px solid var(--accent); outline-offset:-2px; z-index:2; }
    .tbl-jadwal td.c .x { position:absolute; top:1px; right:1px; width:7px; height:7px; border-radius:50%; background:var(--danger); border:1px solid #fff; }
    .tbl-jadwal .s-no, .tbl-jadwal .s-nm, .tbl-jadwal .s-jm { position:sticky; background:var(--bg-card); z-index:3; }
    .tbl-jadwal .s-no { left:0; min-width:44px; width:44px; color:var(--text-muted); font-weight:600; }
    .tbl-jadwal .s-nm { left:44px; min-width:230px; max-width:230px; text-align:left; padding:0 12px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border-right:1px solid var(--border-accent); }
    .tbl-jadwal .s-nm a { color:var(--text-primary); font-weight:500; }
    .tbl-jadwal .s-nm a:hover { color:var(--accent-light); }
    .tbl-jadwal .s-jm { right:0; min-width:64px; width:64px; color:var(--accent-light); font-weight:800; font-size:12px; border-left:1px solid var(--border-accent); }
    .tbl-jadwal thead .s-no, .tbl-jadwal thead .s-nm, .tbl-jadwal thead .s-jm { z-index:6; background:var(--bg-secondary); top:0; }
    .tbl-jadwal tbody tr:hover td.s-no, .tbl-jadwal tbody tr:hover td.s-nm, .tbl-jadwal tbody tr:hover td.s-jm { background:var(--bg-card-hover); }
    .tbl-jadwal tbody tr.flag td.s-no, .tbl-jadwal tbody tr.flag td.s-nm { box-shadow:inset 3px 0 0 var(--danger); }
    .tbl-jadwal tbody tr.flag td.s-nm a { color:#f87171; }
    .jw-legend { display:flex; flex-wrap:wrap; gap:14px; font-size:12px; color:var(--text-muted); }
    .jw-legend span { display:inline-flex; align-items:center; gap:6px; }
    .task-item { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 12px; margin-bottom:8px; background:var(--bg-secondary); border:1px solid var(--border); border-left:4px solid var(--c, var(--accent)); border-radius:var(--radius-sm); }
    .task-item .meta { font-size:12px; color:var(--text-muted); margin-top:2px; }
</style>

<!-- FILTER -->
<div class="card-dark">
    <div class="card-body-dark">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <div class="month-chips">
                <a href="<?= $qs(['bulan' => 'all']) ?>" class="pg-chip <?= $bulanFilter === null ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Jan – Des</a>
                <?php foreach ($namaBulan as $mNum => $mNama): ?>
                    <a href="<?= $qs(['bulan' => $mNum]) ?>" class="pg-chip <?= $bulanFilter === $mNum ? 'active' : '' ?>"><?= substr($mNama, 0, 3) ?></a>
                <?php endforeach; ?>
            </div>
            <div style="margin-left:auto;">
                <button type="button" class="btn-warning-dark" id="btnBukaModalCuti" onclick="bukaModalCuti()" style="padding:7px 14px;font-weight:700;">
                    <i class="fas fa-umbrella-beach"></i> <?= $canEdit ? 'Atur Cuti & Tugas Lain' : 'Daftar Cuti & Tugas Lain' ?>
                    <span class="badge-custom" id="badgeCountCuti" style="background:rgba(0,0,0,0.25);color:#fff;margin-left:4px;"><?= count($cutiList ?? []) ?></span>
                </button>
            </div>
        </div>
        <form method="get" action="<?= base_url('jadwal-penugasan') ?>" class="pg-toolbar">
            <input type="hidden" name="bulan" value="<?= esc($bulanFilter ?? 'all') ?>">
            <div class="pg-field">
                <label class="form-label-dark">Pegawai</label>
                <select name="nip" class="form-control-dark sm" onchange="this.form.submit()">
                    <option value="">Semua pegawai (<?= count($pegawaiAll) ?>)</option>
                    <?php foreach ($pegawaiAll as $p): ?>
                        <option value="<?= esc($p['nip']) ?>" <?= $nipFilter === $p['nip'] ? 'selected' : '' ?>>#<?= (int) $p['no_urut_rekap'] ?> — <?= esc($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pg-field grow-2">
                <label class="form-label-dark">Kode Kegiatan</label>
                <select name="kode" class="form-control-dark sm" onchange="this.form.submit()">
                    <option value="">Semua kode kegiatan</option>
                    <?php foreach ($kegiatanList as $k): ?>
                        <option value="<?= esc($k['kode_kegiatan']) ?>" <?= $kodeFilter === (string) $k['kode_kegiatan'] ? 'selected' : '' ?>>Kode <?= esc($k['kode_kegiatan']) ?> — <?= esc($k['nama_kegiatan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pg-field">
                <label class="form-label-dark">Cari nama</label>
                <div class="pg-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="quickSearchNamaJadwal" class="form-control-dark sm" placeholder="Nama pegawai...">
                </div>
            </div>
            <?php if ($bulanFilter !== null || $nipFilter !== '' || $kodeFilter !== ''): ?>
                <div class="pg-field" style="flex:0;min-width:auto;">
                    <a href="<?= base_url('jadwal-penugasan') ?>" class="btn-danger-dark" style="padding:8px 14px;"><i class="fas fa-times"></i> Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- MATRIKS -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-calendar-check header-icon"></i> Matriks Jadwal Penugasan SDM 2026<?= $bulanFilter ? ' — ' . $namaBulan[$bulanFilter] : '' ?></h5>
        <div class="jw-legend">
            <span><span class="kode-badge sm" style="background:#B6D7A8;min-width:22px;height:18px;font-size:10px;">2</span> Kode kegiatan</span>
            <span><span class="kode-badge sm" style="background:#f59e0b;color:#fff;min-width:24px;height:18px;font-size:9.5px;">CT</span> Cuti / Tugas Lain</span>
            <span><span style="width:8px;height:8px;border-radius:50%;background:var(--danger);display:inline-block;"></span> Bentrok jadwal</span>
            <span style="color:var(--danger);">Min</span>
        </div>
    </div>
    <div class="card-body-dark" style="padding:0;">
        <div class="jw-wrap" id="jwWrap">
            <div class="pg-empty"><i class="fas fa-spinner fa-spin"></i> Menyusun matriks...</div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<!-- MODAL SEL JADWAL -->
<div class="modal fade" id="modalSelJadwal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-day" style="color:var(--accent);margin-right:8px;"></i><span id="jadwalModalNama"></span> <span style="color:var(--text-muted);font-weight:400;">·</span> <span id="jadwalModalTanggal" style="color:var(--accent-light);"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="boxCutiEksisting" style="display:none;margin-bottom:16px;">
                    <div class="section-title" style="color:#fbbf24;"><i class="fas fa-calendar-minus"></i> Cuti / Tugas Lain pada tanggal ini</div>
                    <div id="listCutiEksisting"></div>
                </div>
                <div id="boxTugasEksisting" style="display:none;margin-bottom:16px;">
                    <div class="section-title">Penugasan pada tanggal ini</div>
                    <div id="listTugasEksisting"></div>
                </div>
                <div class="pg-panel" id="panelFormSelJadwal">
                    <div class="pg-panel-title" id="judulFormSelJadwal"><i class="fas fa-plus-circle"></i> Tambah Penugasan</div>
                    <div id="alertBlokirCutiSel" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:var(--radius-sm);background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.35);color:#fbbf24;font-size:12.5px;">
                        <i class="fas fa-ban"></i> <span id="teksBlokirCutiSel">Pegawai sedang cuti pada rentang tanggal ini dan tidak dapat ditugaskan untuk kegiatan.</span>
                    </div>
                    <form id="formSelJadwal" onsubmit="submitFormSelJadwal(event)">
                        <input type="hidden" name="id_penugasan" id="jselIdPenugasan">
                        <input type="hidden" name="nip" id="jselNip">
                        <div class="row">
                            <div class="col-md-7 form-group">
                                <label class="form-label-dark">Kegiatan *</label>
                                <select name="id_kegiatan" id="jselIdKegiatan" class="form-control-dark sm" required>
                                    <option value="">— Pilih kegiatan —</option>
                                    <?php foreach ($kegiatanList as $k): ?>
                                        <option value="<?= (int) $k['id_kegiatan'] ?>" data-tempat="<?= esc($k['tempat_lokus'] ?? '') ?>">Kode <?= esc($k['kode_kegiatan']) ?> — <?= esc($k['nama_kegiatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5 form-group">
                                <label class="form-label-dark">Kategori Peran *</label>
                                <select name="kategori_peran" id="jselKategoriPeran" class="form-control-dark sm">
                                    <option value="Dinas Luar">Dinas Luar (Zona 1–4)</option>
                                    <option value="Panitia Hotel">Panitia Hotel</option>
                                    <option value="Panitia UPT">Panitia UPT</option>
                                    <option value="Fasilitator">Fasilitator</option>
                                    <option value="Peserta Hotel">Peserta Hotel</option>
                                    <option value="Peserta UPT">Peserta UPT</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="form-label-dark">Kab/Kota Lokus</label>
                                <select name="kab_kota_lokus" id="jselKabKota" class="form-control-dark sm">
                                    <?php foreach ($kabKotaMap as $kab => $z): ?>
                                        <option value="<?= esc($kab) ?>"><?= esc($kab) ?> (<?= esc($z) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5 form-group">
                                <label class="form-label-dark">Detail Lokus</label>
                                <input type="text" name="detail_lokus" id="jselDetailLokus" class="form-control-dark sm" placeholder="Sekolah / hotel">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="form-label-dark">Tahap</label>
                                <select name="tahap" id="jselTahap" class="form-control-dark sm">
                                    <option value="">—</option>
                                    <option value="Tahap 1">Tahap 1</option>
                                    <option value="Tahap 2">Tahap 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="row" style="align-items:flex-end;">
                            <div class="col-md-4 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Mulai *</label>
                                <input type="date" name="tanggal_mulai_tugas" id="jselTglMulai" class="form-control-dark sm" onchange="cekBlokirCutiFormSel()" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Selesai *</label>
                                <input type="date" name="tanggal_selesai_tugas" id="jselTglSelesai" class="form-control-dark sm" onchange="cekBlokirCutiFormSel()" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:0;display:flex;gap:6px;">
                                <button type="button" class="btn-info-dark" id="btnBatalEdit" style="display:none;padding:9px 12px;" onclick="resetFormSel()"><i class="fas fa-undo"></i></button>
                                <button type="submit" class="btn-primary-dark" id="btnSubmitSelJadwal" style="flex:1;justify-content:center;"><i class="fas fa-save"></i> Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL KELOLA CUTI & TUGAS KEDINASAN LAIN -->
<div class="modal fade" id="modalKelolaCuti" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-minus" style="color:var(--warning);margin-right:8px;"></i>Kelola Cuti &amp; Tugas Kedinasan Lain</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <?php if ($canEdit): ?>
                <div class="pg-panel" style="margin-bottom:16px;">
                    <div class="pg-panel-title" id="judulFormCuti"><i class="fas fa-plus-circle"></i> Tambah Cuti / Tugas Kedinasan Lain</div>
                    <form id="formKelolaCuti" onsubmit="submitFormCuti(event)">
                        <input type="hidden" name="id_cuti" id="cutiId">
                        <div class="row">
                            <div class="col-lg-5 form-group">
                                <label class="form-label-dark">Pegawai *</label>
                                <div class="pg-search" style="margin-bottom:6px;">
                                    <i class="fas fa-search"></i>
                                    <input type="text" id="filterCutiPegawai" class="form-control-dark sm" placeholder="Ketik nama pegawai untuk memfilter..." oninput="filterOpsiPegawaiCuti(this.value)">
                                </div>
                                <select name="nip" id="cutiSelectNip" class="form-control-dark sm" required>
                                    <option value="">— Pilih pegawai —</option>
                                    <?php foreach ($pegawaiAll as $p): ?>
                                        <option value="<?= esc($p['nip']) ?>" data-nama="<?= esc(strtolower($p['nama'])) ?>">#<?= (int) $p['no_urut_rekap'] ?> — <?= esc($p['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-4 form-group">
                                <label class="form-label-dark">Jenis Cuti / Tugas *</label>
                                <select name="jenis_cuti" id="cutiJenis" class="form-control-dark sm" required>
                                    <?php foreach ($jenisCutiOptions as $jOpt): ?>
                                        <option value="<?= esc($jOpt) ?>"><?= esc($jOpt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 form-group">
                                <label class="form-label-dark">Durasi</label>
                                <div style="padding-top:4px;">
                                    <span class="badge-custom badge-warning" style="padding:8px 12px;font-size:12px;"><i class="fas fa-calendar-day"></i> <span id="labelHariFormCuti">1 hari</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="row" style="align-items:flex-end;">
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Tanggal Mulai Cuti *</label>
                                <input type="date" name="tanggal_mulai" id="cutiTglMulai" class="form-control-dark sm" value="<?= date('Y-m-d') ?>" onchange="hitungHariFormCuti()" required>
                            </div>
                            <div class="col-md-3 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Tanggal Akhir Cuti *</label>
                                <input type="date" name="tanggal_selesai" id="cutiTglSelesai" class="form-control-dark sm" value="<?= date('Y-m-d') ?>" onchange="hitungHariFormCuti()" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Keterangan *</label>
                                <input type="text" name="keterangan" id="cutiKeterangan" class="form-control-dark sm" placeholder="Alasan cuti / nomor surat / detail tugas..." required>
                            </div>
                            <div class="col-md-2 form-group" style="margin-bottom:0;display:flex;gap:6px;">
                                <button type="button" class="btn-info-dark" id="btnBatalEditCuti" style="display:none;padding:9px 10px;" onclick="resetFormCuti()" title="Batal Edit"><i class="fas fa-undo"></i></button>
                                <button type="submit" class="btn-warning-dark" id="btnSubmitCuti" style="flex:1;justify-content:center;padding:9px 12px;"><i class="fas fa-save"></i> Simpan</button>
                            </div>
                        </div>
                    </form>
                    <div style="margin-top:10px;font-size:11.5px;color:var(--text-muted);">
                        <i class="fas fa-info-circle" style="color:var(--warning);"></i> Pegawai yang sedang dalam masa cuti / tugas kedinasan lain secara otomatis <strong>tidak dapat ditugaskan</strong> untuk kegiatan pada halaman Kode &amp; SDM maupun Matriks Jadwal.
                    </div>
                </div>
                <?php endif; ?>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
                    <div class="section-title" style="margin:0;">Daftar Cuti &amp; Tugas Kedinasan Lain <span id="badgeCountDaftarCuti" class="badge-custom badge-warning" style="margin-left:6px;">0</span></div>
                    <div class="pg-search" style="width:240px;">
                        <i class="fas fa-search"></i>
                        <input type="text" id="inputCariDaftarCuti" class="form-control-dark sm" placeholder="Cari nama / jenis / ket..." oninput="renderTabelDaftarCuti()">
                    </div>
                </div>
                <div class="pg-scroll" style="max-height:360px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th style="width:45px;text-align:center;">No</th>
                                <th>Pegawai</th>
                                <th>Jenis Cuti / Tugas</th>
                                <th>Keterangan</th>
                                <th style="text-align:center;">Tanggal Mulai</th>
                                <th style="text-align:center;">Tanggal Akhir</th>
                                <th style="text-align:center;">Hari</th>
                                <?php if ($canEdit): ?>
                                <th style="text-align:center;width:95px;">Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tbodyDaftarCuti"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL AUDIT PEGAWAI -->
<div class="modal fade" id="modalAuditJadwal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-check" style="color:var(--accent);margin-right:8px;"></i><span id="auditJadwalNama"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="auditJadwalBody" style="padding:0;"></div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('penugasan/_partials/js_helpers') ?>
<script>
const JW_ROWS    = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const JW_KEG     = <?= json_encode($kegById, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
let   JW_CUTI    = <?= json_encode($cutiList ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const JW_BULAN   = <?= json_encode($bulanFilter) ?>;
const JW_EDIT    = <?= $canEdit ? 'true' : 'false' ?>;
const JW_FLAG    = []; // baris bertanda merah di sheet Jadwal
const NAMA_BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

// ── Susun daftar hari yang ditampilkan ──────────────────────────────
const months = JW_BULAN ? [JW_BULAN] : [1,2,3,4,5,6,7,8,9,10,11,12];
const days = [];
months.forEach(m => {
    const n = new Date(2026, m, 0).getDate();
    for (let d = 1; d <= n; d++) {
        const dt = new Date(2026, m - 1, d);
        days.push({ m, d, key: `2026-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`, wk: dt.getDay() === 0 });
    }
});
const dayIndex = Object.fromEntries(days.map((x, i) => [x.key, i]));

// ── Peta tugas & cuti per baris & hari ──────────────────────────────
JW_ROWS.forEach(r => {
    r.byDay = {};
    r.tasks.forEach(t => {
        for (let ts = new Date(t.m + 'T00:00:00'); ts <= new Date(t.s + 'T00:00:00'); ts.setDate(ts.getDate() + 1)) {
            const key = `${ts.getFullYear()}-${String(ts.getMonth()+1).padStart(2,'0')}-${String(ts.getDate()).padStart(2,'0')}`;
            if (key in dayIndex) (r.byDay[key] = r.byDay[key] || []).push(t);
        }
    });
});

function rebuildCutiMap() {
    const rowByNip = Object.fromEntries(JW_ROWS.map(r => [r.nip, r]));
    JW_ROWS.forEach(r => { r.cutiByDay = {}; });
    JW_CUTI.forEach(c => {
        const r = rowByNip[c.nip];
        if (!r || !c.tanggal_mulai) return;
        const endStr = c.tanggal_selesai || c.tanggal_mulai;
        for (let ts = new Date(c.tanggal_mulai + 'T00:00:00'); ts <= new Date(endStr + 'T00:00:00'); ts.setDate(ts.getDate() + 1)) {
            const key = `${ts.getFullYear()}-${String(ts.getMonth()+1).padStart(2,'0')}-${String(ts.getDate()).padStart(2,'0')}`;
            if (key in dayIndex) (r.cutiByDay[key] = r.cutiByDay[key] || []).push(c);
        }
    });
}
rebuildCutiMap();

// ── Render tabel ────────────────────────────────────────────────────
function renderMatrix() {
    let html = `<table class="tbl-jadwal ${JW_EDIT ? 'editable' : ''}" id="tblJadwalMatriks"><thead><tr class="h-month">
        <th rowspan="2" class="s-no">No.</th><th rowspan="2" class="s-nm">Nama Pegawai</th>`;
    months.forEach(m => { html += `<th colspan="${new Date(2026, m, 0).getDate()}">${NAMA_BULAN[m]}</th>`; });
    html += `<th rowspan="2" class="s-jm">Jumlah</th></tr><tr class="h-day">`;
    days.forEach(x => { html += `<th class="${x.wk ? 'wkend' : ''}">${x.d}</th>`; });
    html += `</tr></thead><tbody>`;

    if (!JW_ROWS.length) {
        html += `<tr><td colspan="${days.length + 3}" class="pg-empty">Tidak ada data pegawai.</td></tr>`;
    }
    JW_ROWS.forEach((r, ri) => {
        html += `<tr data-ri="${ri}" data-nama="${h(r.nama.toLowerCase())}" class="${JW_FLAG.includes(r.no) ? 'flag' : ''}">
            <td class="s-no">${r.no}</td>
            <td class="s-nm" title="${h(r.nama)}"><a href="javascript:void(0)" data-audit="${h(r.nip)}">${h(r.nama)}</a></td>`;
        days.forEach((x, di) => {
            const ts = r.byDay[x.key];
            const cs = r.cutiByDay ? r.cutiByDay[x.key] : null;
            if (ts && ts.length) {
                const k = JW_KEG[ts[0].idk] || { kode: '?', warna: '#E5B8B7' };
                const hasConflict = ts.length > 1 || (cs && cs.length > 0);
                html += `<td class="c f" data-di="${di}" style="background:${k.warna}">${h(k.kode)}${hasConflict ? '<span class="x"></span>' : ''}</td>`;
            } else if (cs && cs.length) {
                html += `<td class="c f" data-di="${di}" style="background:#f59e0b;color:#fff;font-size:9.5px;">CT${cs.length > 1 ? '<span class="x"></span>' : ''}</td>`;
            } else {
                html += `<td class="c${x.wk ? ' wkend' : ''}" data-di="${di}"></td>`;
            }
        });
        html += `<td class="s-jm">${r.total}</td></tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('jwWrap').innerHTML = html;
}
renderMatrix();

// ── Pencarian nama ──────────────────────────────────────────────────
document.getElementById('quickSearchNamaJadwal').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tblJadwalMatriks tbody tr[data-ri]').forEach(tr => {
        tr.style.display = !q || tr.dataset.nama.includes(q) ? '' : 'none';
    });
});

// ── Tooltip bertema ─────────────────────────────────────────────────
const tip = document.getElementById('pgTip');
function cellInfo(td) {
    const tr = td.closest('tr[data-ri]');
    if (!tr || td.dataset.di === undefined) return null;
    const r = JW_ROWS[tr.dataset.ri];
    const day = days[td.dataset.di];
    return {
        r,
        day,
        tasks: r.byDay[day.key] || [],
        cutis: (r.cutiByDay && r.cutiByDay[day.key]) ? r.cutiByDay[day.key] : [],
    };
}
function fmtTgl(key) { const [y, m, d] = key.split('-'); return `${+d} ${NAMA_BULAN[+m]} ${y}`; }

const wrap = document.getElementById('jwWrap');
wrap.addEventListener('mouseover', e => {
    const td = e.target.closest('td.c.f');
    if (!td) { tip.style.display = 'none'; return; }
    const info = cellInfo(td);
    if (!info) return;
    const cutiHtml = info.cutis.map(c => {
        const jns = c.jenis_cuti || 'Cuti';
        const hari = pgHitungHari(c.tanggal_mulai, c.tanggal_selesai || c.tanggal_mulai);
        return `<div class="tip-item">
            <div class="tip-title"><span class="kode-badge sm" style="background:#f59e0b;color:#fff;min-width:24px;height:18px;font-size:9.5px;margin-right:6px;">CT</span>${h(jns)}</div>
            ${c.keterangan ? `<div>${h(c.keterangan)}</div>` : ''}
            <div>${h(c.tanggal_mulai)} s.d. ${h(c.tanggal_selesai || c.tanggal_mulai)} (${hari} hari)</div>
        </div>`;
    }).join('');
    const taskHtml = info.tasks.map(t => {
        const k = JW_KEG[t.idk] || {};
        const lokus = [t.kab, t.det && t.det !== t.kab && t.det !== '-' ? t.det : ''].filter(Boolean).join(' — ');
        return `<div class="tip-item">
            <div class="tip-title"><span class="kode-badge sm" style="background:${k.warna};min-width:24px;height:18px;font-size:10px;margin-right:6px;">${h(k.kode)}</span>${h(k.nama)}</div>
            <div>${h(t.peran)}${t.tahap ? ' · ' + h(t.tahap) : ''}${t.zona !== 'NON_ZONA' ? ' · ' + h(t.zona) : ''}</div>
            ${lokus ? `<div>${h(lokus)}</div>` : ''}
            <div>${h(t.m)} s.d. ${h(t.s)} (${t.hari} hari)</div>
        </div>`;
    }).join('');
    let warnHtml = '';
    if (info.tasks.length > 1) {
        warnHtml += `<div class="tip-item" style="color:#f87171;"><i class="fas fa-exclamation-triangle"></i> Bentrok ${info.tasks.length} kegiatan</div>`;
    }
    if (info.tasks.length > 0 && info.cutis.length > 0) {
        warnHtml += `<div class="tip-item" style="color:#fbbf24;"><i class="fas fa-exclamation-circle"></i> Bentrok dengan jadwal Cuti / Tugas Lain</div>`;
    }
    tip.innerHTML = `<div class="tip-date">${h(info.r.nama)} · ${fmtTgl(info.day.key)}</div>` + cutiHtml + taskHtml + warnHtml;
    tip.style.display = 'block';
});
wrap.addEventListener('mousemove', e => {
    if (tip.style.display !== 'block') return;
    const x = Math.min(e.clientX + 14, window.innerWidth - tip.offsetWidth - 10);
    const y = e.clientY + 16 + tip.offsetHeight > window.innerHeight ? e.clientY - tip.offsetHeight - 10 : e.clientY + 16;
    tip.style.left = x + 'px'; tip.style.top = y + 'px';
});
wrap.addEventListener('mouseleave', () => { tip.style.display = 'none'; });

// ── Klik: audit pegawai / sel tanggal ───────────────────────────────
wrap.addEventListener('click', e => {
    const a = e.target.closest('[data-audit]');
    if (a) { bukaAuditPegawai(a.dataset.audit); return; }
    if (!JW_EDIT) return;
    const td = e.target.closest('td.c');
    if (!td) return;
    const info = cellInfo(td);
    if (info) { tip.style.display = 'none'; klikSelJadwal(info.r, info.day.key, info.tasks, info.cutis); }
});

// ── Modal Cuti & Tugas Kedinasan Lain ───────────────────────────────
function bukaModalCuti(prefillNip = '', prefillDate = '') {
    if (JW_EDIT) {
        resetFormCuti();
        if (prefillNip) {
            document.getElementById('cutiSelectNip').value = prefillNip;
        }
        if (prefillDate) {
            document.getElementById('cutiTglMulai').value = prefillDate;
            document.getElementById('cutiTglSelesai').value = prefillDate;
            hitungHariFormCuti();
        }
    }
    renderTabelDaftarCuti();
    $('#modalKelolaCuti').modal('show');
}

function renderTabelDaftarCuti() {
    const badgeTop = document.getElementById('badgeCountCuti');
    if (badgeTop) badgeTop.textContent = JW_CUTI.length;
    const q = (document.getElementById('inputCariDaftarCuti')?.value || '').trim().toLowerCase();
    const filtered = JW_CUTI.filter(c => {
        if (!q) return true;
        const hay = `${c.nama_pegawai || ''} ${c.nip || ''} ${c.jenis_cuti || ''} ${c.keterangan || ''}`.toLowerCase();
        return hay.includes(q);
    });
    document.getElementById('badgeCountDaftarCuti').textContent = filtered.length;
    const tbody = document.getElementById('tbodyDaftarCuti');
    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="${JW_EDIT ? 8 : 7}" class="pg-empty">Belum ada data cuti / tugas kedinasan lain.</td></tr>`;
        return;
    }
    tbody.innerHTML = filtered.map((c, idx) => {
        const jns = c.jenis_cuti || 'Cuti';
        const tSelesai = c.tanggal_selesai || c.tanggal_mulai;
        const hari = pgHitungHari(c.tanggal_mulai, tSelesai);
        return `<tr>
            <td style="text-align:center;color:var(--text-muted);">${idx + 1}</td>
            <td><span style="color:var(--text-primary);font-weight:600;">${h(c.nama_pegawai || c.nip)}</span><br><small style="color:var(--text-muted);font-family:monospace;">${h(c.nip)}</small></td>
            <td><span class="badge-custom badge-warning">${h(jns)}</span></td>
            <td style="color:var(--text-secondary);">${h(c.keterangan || '-')}</td>
            <td style="text-align:center;font-size:12px;">${h(c.tanggal_mulai)}</td>
            <td style="text-align:center;font-size:12px;">${h(tSelesai)}</td>
            <td style="text-align:center;color:var(--text-primary);font-weight:700;">${hari}</td>
            ${JW_EDIT ? `<td style="text-align:center;">
                <div style="display:flex;gap:5px;justify-content:center;">
                    <button type="button" class="btn-warning-dark btn-sm-custom" title="Edit Cuti" onclick="isiFormEditCuti(${Number(c.id)})"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn-danger-dark btn-sm-custom" title="Hapus Cuti" onclick="hapusDataCuti(${Number(c.id)})"><i class="fas fa-trash"></i></button>
                </div>
            </td>` : ''}
        </tr>`;
    }).join('');
}

function cariCutiBentrokPegawai(nip, tglMulai, tglSelesai) {
    if (!nip || !tglMulai) return null;
    const m = tglMulai;
    const s = tglSelesai || tglMulai;
    return JW_CUTI.find(c => {
        if (c.nip !== nip || !c.tanggal_mulai) return false;
        const cm = c.tanggal_mulai;
        const cs = c.tanggal_selesai || c.tanggal_mulai;
        return cm <= s && cs >= m;
    }) || null;
}

<?php if ($canEdit): ?>
function filterOpsiPegawaiCuti(keyword) {
    const q = (keyword || '').trim().toLowerCase();
    const sel = document.getElementById('cutiSelectNip');
    let first = null;
    Array.from(sel.options).slice(1).forEach(opt => {
        const vis = !q || (opt.dataset.nama || '').includes(q);
        opt.hidden = !vis;
        if (vis && !first) first = opt.value;
    });
    if (q && first) sel.value = first;
}

function hitungHariFormCuti() {
    const n = pgHitungHari(document.getElementById('cutiTglMulai').value, document.getElementById('cutiTglSelesai').value);
    document.getElementById('labelHariFormCuti').textContent = n + ' hari';
}

function resetFormCuti() {
    const today = '<?= date('Y-m-d') ?>';
    document.getElementById('cutiId').value = '';
    document.getElementById('cutiSelectNip').value = '';
    document.getElementById('filterCutiPegawai').value = '';
    filterOpsiPegawaiCuti('');
    document.getElementById('cutiJenis').selectedIndex = 0;
    document.getElementById('cutiTglMulai').value = today;
    document.getElementById('cutiTglSelesai').value = today;
    document.getElementById('cutiKeterangan').value = '';
    hitungHariFormCuti();
    document.getElementById('judulFormCuti').innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Cuti / Tugas Kedinasan Lain';
    document.getElementById('btnSubmitCuti').innerHTML = '<i class="fas fa-save"></i> Simpan';
    document.getElementById('btnBatalEditCuti').style.display = 'none';
}

function isiFormEditCuti(id) {
    const c = JW_CUTI.find(x => Number(x.id) === Number(id));
    if (!c) return;
    document.getElementById('cutiId').value = c.id;
    document.getElementById('filterCutiPegawai').value = '';
    filterOpsiPegawaiCuti('');
    document.getElementById('cutiSelectNip').value = c.nip;
    document.getElementById('cutiJenis').value = c.jenis_cuti || 'Cuti Tahunan';
    document.getElementById('cutiTglMulai').value = c.tanggal_mulai || '';
    document.getElementById('cutiTglSelesai').value = c.tanggal_selesai || c.tanggal_mulai || '';
    document.getElementById('cutiKeterangan').value = c.keterangan || '';
    hitungHariFormCuti();
    document.getElementById('judulFormCuti').innerHTML = `<i class="fas fa-edit" style="color:var(--warning);"></i> Edit Cuti — ${h(c.nama_pegawai || c.nip)}`;
    document.getElementById('btnSubmitCuti').innerHTML = '<i class="fas fa-save"></i> Perbarui';
    document.getElementById('btnBatalEditCuti').style.display = '';
}

function submitFormCuti(e) {
    e.preventDefault();
    pgPost('<?= base_url('jadwal-penugasan/cuti/simpan') ?>', new FormData(e.target)).then(res => {
        if (res.status === 'ok') {
            pgToast(res.message || 'Data cuti berhasil disimpan');
            const list = res.cuti_list || res.data;
            if (Array.isArray(list)) {
                JW_CUTI = list;
                rebuildCutiMap();
                renderMatrix();
                renderTabelDaftarCuti();
                resetFormCuti();
            } else {
                setTimeout(() => location.reload(), 400);
            }
        } else {
            pgToast(res.message || 'Gagal menyimpan data cuti', 'danger');
        }
    });
}

function hapusDataCuti(id) {
    const c = JW_CUTI.find(x => Number(x.id) === Number(id));
    const label = c ? `${c.jenis_cuti || 'Cuti'} milik ${c.nama_pegawai || c.nip} (${c.tanggal_mulai} s.d. ${c.tanggal_selesai || c.tanggal_mulai})` : 'Data cuti ini';
    pgConfirm(`${label} akan dihapus. Setelah dihapus, pegawai dapat ditugaskan kembali pada tanggal tersebut.`, { title: 'Hapus Data Cuti', okText: 'Hapus', danger: true }).then(ok => {
        if (!ok) return;
        pgPost('<?= base_url('jadwal-penugasan/cuti/hapus') ?>/' + id).then(res => {
            if (res.status === 'ok') {
                pgToast(res.message || 'Data cuti dihapus');
                const list = res.cuti_list || res.data;
                if (Array.isArray(list)) {
                    JW_CUTI = list;
                    rebuildCutiMap();
                    renderMatrix();
                    renderTabelDaftarCuti();
                    if ($('#modalSelJadwal').hasClass('show') && selCtx) {
                        $('#modalSelJadwal').modal('hide');
                    }
                } else {
                    setTimeout(() => location.reload(), 400);
                }
            } else {
                pgToast(res.message || 'Gagal menghapus data cuti', 'danger');
            }
        });
    });
}

let selCtx = null;

function cekBlokirCutiFormSel() {
    if (!selCtx) return;
    const m = document.getElementById('jselTglMulai').value;
    const s = document.getElementById('jselTglSelesai').value || m;
    const cuti = cariCutiBentrokPegawai(selCtx.r.nip, m, s);
    const alertBox = document.getElementById('alertBlokirCutiSel');
    const alertTxt = document.getElementById('teksBlokirCutiSel');
    const btnSubmit = document.getElementById('btnSubmitSelJadwal');
    if (cuti) {
        const jns = cuti.jenis_cuti || 'Cuti';
        const ket = cuti.keterangan ? ` (${cuti.keterangan})` : '';
        alertTxt.textContent = `${selCtx.r.nama} sedang ${jns}${ket} pada tanggal ${cuti.tanggal_mulai} s.d. ${cuti.tanggal_selesai || cuti.tanggal_mulai}. Pegawai yang sedang cuti tidak dapat ditugaskan untuk kegiatan.`;
        alertBox.style.display = 'block';
        btnSubmit.disabled = true;
        btnSubmit.style.opacity = '0.5';
        btnSubmit.style.cursor = 'not-allowed';
    } else {
        alertBox.style.display = 'none';
        btnSubmit.disabled = false;
        btnSubmit.style.opacity = '';
        btnSubmit.style.cursor = '';
    }
}

function klikSelJadwal(r, dateKey, tasks, cutis = []) {
    selCtx = { r, dateKey };
    document.getElementById('jadwalModalNama').textContent = r.nama;
    document.getElementById('jadwalModalTanggal').textContent = fmtTgl(dateKey);
    document.getElementById('jselNip').value = r.nip;
    resetFormSel();

    const boxCuti = document.getElementById('boxCutiEksisting');
    boxCuti.style.display = cutis.length ? 'block' : 'none';
    document.getElementById('listCutiEksisting').innerHTML = cutis.map(c => {
        const jns = c.jenis_cuti || 'Cuti';
        const tSelesai = c.tanggal_selesai || c.tanggal_mulai;
        const hari = pgHitungHari(c.tanggal_mulai, tSelesai);
        return `<div class="task-item" style="--c:#f59e0b">
            <div>
                <div style="color:var(--text-primary);font-weight:600;"><span class="kode-badge sm" style="background:#f59e0b;color:#fff;margin-right:8px;">CT</span>${h(jns)}</div>
                <div class="meta">${c.keterangan ? h(c.keterangan) + ' · ' : ''}${h(c.tanggal_mulai)} s.d. ${h(tSelesai)} (${hari} hari)</div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button type="button" class="btn-warning-dark btn-sm-custom" onclick="$('#modalSelJadwal').modal('hide'); bukaModalCuti(); isiFormEditCuti(${Number(c.id)});"><i class="fas fa-edit"></i> Edit Cuti</button>
                <button type="button" class="btn-danger-dark btn-sm-custom" onclick="hapusDataCuti(${Number(c.id)})"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    }).join('');

    const box = document.getElementById('boxTugasEksisting');
    box.style.display = tasks.length ? 'block' : 'none';
    document.getElementById('listTugasEksisting').innerHTML = tasks.map(t => {
        const k = JW_KEG[t.idk] || {};
        return `<div class="task-item" style="--c:${k.warna}">
            <div>
                <div style="color:var(--text-primary);font-weight:600;"><span class="kode-badge sm" style="background:${k.warna};margin-right:8px;">${h(k.kode)}</span>${h(k.nama)}</div>
                <div class="meta">${pgRoleBadge(t.peran)} ${h(t.kab || '-')}${t.det && t.det !== '-' ? ' — ' + h(t.det) : ''} · ${h(t.zona)} · ${h(t.m)} s.d. ${h(t.s)} (${t.hari} hari)</div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;">
                <button type="button" class="btn-warning-dark btn-sm-custom" onclick="isiFormEditSel(${t.id})"><i class="fas fa-edit"></i> Edit</button>
                <button type="button" class="btn-danger-dark btn-sm-custom" onclick="hapusTugasDariJadwal(${t.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>`;
    }).join('');
    cekBlokirCutiFormSel();
    $('#modalSelJadwal').modal('show');
}

function resetFormSel() {
    const set = (id, v) => { document.getElementById(id).value = v; };
    set('jselIdPenugasan', '');
    set('jselIdKegiatan', '');
    set('jselKategoriPeran', 'Dinas Luar');
    set('jselDetailLokus', '');
    set('jselTahap', '');
    set('jselTglMulai', selCtx ? selCtx.dateKey : '');
    set('jselTglSelesai', selCtx ? selCtx.dateKey : '');
    document.getElementById('judulFormSelJadwal').innerHTML = '<i class="fas fa-plus-circle"></i> Tambah Penugasan';
    document.getElementById('btnSubmitSelJadwal').innerHTML = '<i class="fas fa-save"></i> Simpan';
    document.getElementById('btnBatalEdit').style.display = 'none';
    cekBlokirCutiFormSel();
}

function isiFormEditSel(id) {
    const t = selCtx.r.tasks.find(x => x.id === id);
    if (!t) return;
    const k = JW_KEG[t.idk] || {};
    const set = (el, v) => { document.getElementById(el).value = v ?? ''; };
    set('jselIdPenugasan', t.id);
    set('jselIdKegiatan', t.idk);
    set('jselKategoriPeran', t.peran);
    if (t.kab) set('jselKabKota', t.kab);
    set('jselDetailLokus', t.det);
    set('jselTahap', t.tahap);
    set('jselTglMulai', t.m);
    set('jselTglSelesai', t.s);
    document.getElementById('judulFormSelJadwal').innerHTML = `<i class="fas fa-edit" style="color:var(--warning);"></i> Edit Penugasan Kode ${h(k.kode)}`;
    document.getElementById('btnSubmitSelJadwal').innerHTML = '<i class="fas fa-save"></i> Perbarui';
    document.getElementById('btnBatalEdit').style.display = '';
    cekBlokirCutiFormSel();
}

function submitFormSelJadwal(e) {
    e.preventDefault();
    const isEdit = !!document.getElementById('jselIdPenugasan').value;
    const url = isEdit ? '<?= base_url('kegiatan/penugasan/update') ?>' : '<?= base_url('kegiatan/penugasan/simpan') ?>';
    pgPost(url, new FormData(e.target)).then(res => {
        if (res.status === 'ok') { pgToast(res.message || 'Tersimpan'); setTimeout(() => location.reload(), 500); }
        else pgToast(res.message || 'Gagal menyimpan', 'danger');
    });
}

function hapusTugasDariJadwal(id) {
    pgConfirm('Penugasan ini akan dihapus dari Jadwal dan Rekap pegawai.', { title: 'Hapus Penugasan', okText: 'Hapus', danger: true }).then(ok => {
        if (!ok) return;
        pgPost('<?= base_url('kegiatan/penugasan/hapus') ?>/' + id).then(res => {
            if (res.status === 'ok') { pgToast(res.message); setTimeout(() => location.reload(), 500); }
            else pgToast(res.message || 'Gagal menghapus', 'danger');
        });
    });
}
<?php endif; ?>

function bukaAuditPegawai(nip) {
    document.getElementById('auditJadwalNama').textContent = 'Memuat...';
    document.getElementById('auditJadwalBody').innerHTML = '<div class="pg-empty"><i class="fas fa-spinner fa-spin"></i> Memuat rincian...</div>';
    $('#modalAuditJadwal').modal('show');
    fetch('<?= base_url('rekap-penugasan/detail') ?>/' + encodeURIComponent(nip)).then(r => r.json()).then(res => {
        if (res.status !== 'ok') return;
        document.getElementById('auditJadwalNama').textContent = res.pegawai ? res.pegawai.nama : nip;
        const rows = res.rincian || [];
        if (!rows.length) { document.getElementById('auditJadwalBody').innerHTML = '<div class="pg-empty">Belum ada penugasan.</div>'; return; }
        document.getElementById('auditJadwalBody').innerHTML = `<div class="pg-scroll" style="max-height:60vh;"><table class="table-dark-custom">
            <thead><tr><th>Kode</th><th>Kegiatan</th><th>Peran</th><th>Lokus</th><th style="text-align:center;">Tanggal</th><th style="text-align:center;">Hari</th></tr></thead><tbody>` +
            rows.map(r => `<tr>
                <td><span class="kode-badge sm" style="background:${h(r.warna_hex || '#E5B8B7')}">${h(r.kode_kegiatan)}</span></td>
                <td style="color:var(--text-primary);">${h(r.nama_kegiatan)}</td>
                <td>${pgRoleBadge(r.kategori_peran)}</td>
                <td>${h(r.kab_kota_lokus || '-')}<br><small style="color:var(--text-muted);">${h(r.detail_lokus || '')} · ${h(r.zona)}</small></td>
                <td style="text-align:center;font-size:12px;">${h(r.tanggal_mulai_tugas)}<br>${h(r.tanggal_selesai_tugas)}</td>
                <td style="text-align:center;color:var(--text-primary);font-weight:700;">${h(r.jumlah_hari)}</td>
            </tr>`).join('') + '</tbody></table></div>';
    });
}
</script>
<?= $this->endSection() ?>
