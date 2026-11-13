<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<?php
// --- Cek kelengkapan SK (hanya berlaku untuk role pegawai) ---
$__isPppkCheck = ($data['status_pegawai'] ?? '') === 'PPPK';
$__skRequired  = ['sk_cpns', 'sk_pns', 'sk_pak', 'sk_jabatan_terakhir', 'skp_terakhir'];
if (! $__isPppkCheck) {
    // PNS: sk_tukin wajib
    $__skRequired[] = 'sk_tukin';
}
// PPPK: sk_tukin opsional, tidak masuk daftar wajib

$__skLengkap = true;
$__skKurang  = [];
foreach ($__skRequired as $__skField) {
    if (empty($data[$__skField])) {
        $__skLengkap = false;
        $__skKurang[] = $__skField;
    }
}
// Hanya terapkan blur untuk role pegawai
$__blokKartuPenugasan = ($role === 'pegawai' && ! $__skLengkap);

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
<style>
    .rk-wrap { overflow:auto; max-height:560px; border:1px solid var(--border); border-radius:var(--radius-sm); }
    .tbl-rekap { border-collapse:separate; border-spacing:0; width:max-content; min-width:100%; font-size:12px; }
    .tbl-rekap th, .tbl-rekap td { border-right:1px solid var(--border); border-bottom:1px solid var(--border); padding:6px 8px; text-align:center; vertical-align:middle; }
    .tbl-rekap thead th { background:var(--bg-secondary); color:var(--text-secondary); font-weight:600; font-size:11px; line-height:1.3; position:sticky; z-index:4; }
    .tbl-rekap thead tr:nth-child(1) th { top:0;     height:34px; }
    .tbl-rekap thead tr:nth-child(2) th { top:34px;  height:32px; }
    .tbl-rekap thead tr:nth-child(3) th { top:66px;  height:44px; }
    .tbl-rekap thead tr:nth-child(4) th { top:110px; height:36px; }
    .tbl-rekap thead tr:nth-child(1) th { color:var(--text-primary); font-size:11.5px; letter-spacing:.8px; text-transform:uppercase; }
    .tbl-rekap thead .g-dl  { box-shadow:inset 0 -2px 0 var(--info); }
    .tbl-rekap thead .g-pan { box-shadow:inset 0 -2px 0 #a78bfa; }
    .tbl-rekap thead .g-fas { box-shadow:inset 0 -2px 0 var(--success); }
    .tbl-rekap thead .g-pes { box-shadow:inset 0 -2px 0 var(--warning); }
    .tbl-rekap thead .g-jml { box-shadow:inset 0 -2px 0 var(--accent); }
    .tbl-rekap thead .g-tot { box-shadow:inset 0 -2px 0 var(--text-muted); }
    .tbl-rekap thead .sub   { font-size:10.5px; color:var(--text-muted); font-weight:500; }
    .tbl-rekap thead .lvl4  { font-size:10px; color:var(--text-muted); font-weight:500; min-width:58px; }
    .tbl-rekap td { color:var(--text-secondary); }
    .tbl-rekap td.t { color:var(--text-primary); font-weight:600; }
    .tbl-rekap td.hr { color:var(--text-muted); }
    .tbl-rekap td.jml { background:rgba(99,102,241,0.06); }
    .tbl-rekap td.jml.t { color:var(--accent-light); }
    .tbl-rekap td.gt { color:var(--success); font-weight:800; background:rgba(16,185,129,0.06); }
    .tbl-rekap td.gh { color:var(--info); font-weight:800; background:rgba(59,130,246,0.06); }
    .tbl-rekap td.sep, .tbl-rekap th.sep { border-right:1px solid rgba(255,255,255,0.14); }
    [data-theme="light"] .tbl-rekap td.sep, [data-theme="light"] .tbl-rekap th.sep { border-right:1px solid #cbd5e1; }
    .tbl-rekap tbody tr:hover td { background-color:rgba(99,102,241,0.04); }
    .tbl-rekap .s-no, .tbl-rekap .s-nm { position:sticky; background:var(--bg-card); z-index:3; }
    .tbl-rekap .s-no { left:0; min-width:44px; width:44px; color:var(--text-muted); }
    .tbl-rekap .s-nm { left:44px; min-width:200px; max-width:230px; text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border-right:1px solid var(--border-accent); }
    .tbl-rekap thead .s-no, .tbl-rekap thead .s-nm { z-index:6; background:var(--bg-secondary); top:0; }
    .tbl-rekap tbody tr:hover td.s-no, .tbl-rekap tbody tr:hover td.s-nm { background:var(--bg-card-hover); }
    .tbl-rekap tbody tr.row-self td { background-color:rgba(99,102,241,0.12) !important; }
    .tbl-rekap tbody tr.row-self td.s-nm { font-weight:700; color:var(--accent-light); }
    .tbl-rekap tfoot td, .tbl-rekap tfoot th { position:sticky; bottom:0; background:var(--bg-secondary); color:var(--text-primary); font-weight:700; border-top:2px solid var(--accent); z-index:3; }
    .tbl-rekap tfoot .s-no { z-index:5; text-align:right; padding-right:14px; color:var(--accent-light); font-size:11px; letter-spacing:1px; text-transform:uppercase; }
    .ring-box { background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; font-size:12px; }
    .ring-box strong { color:var(--accent-light); margin-right:4px; }
    .btn-filter-rekap { padding:5px 12px; font-size:11.5px; border-radius:6px; border:1px solid var(--border); background:var(--bg-secondary); color:var(--text-secondary); cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .2s; }
    .btn-filter-rekap:hover { background:var(--bg-card-hover); color:var(--text-primary); }
    .btn-filter-rekap.active { background:var(--accent); color:#ffffff; border-color:var(--accent); }

    /* Blur overlay untuk card yang diblokir karena SK belum lengkap */
    .sk-locked-wrapper { position: relative; }
    .sk-locked-wrapper .sk-locked-content { filter: blur(6px); pointer-events: none; user-select: none; }
    .sk-locked-wrapper .sk-locked-overlay {
        position: absolute; inset: 0; z-index: 10;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;
        background: rgba(15, 23, 42, 0.55); border-radius: var(--radius-sm);
        padding: 32px 24px; text-align: center;
    }
    [data-theme="light"] .sk-locked-wrapper .sk-locked-overlay { background: rgba(255,255,255,0.70); }
    .sk-locked-overlay i.lock-icon { font-size: 36px; color: var(--warning); }
    .sk-locked-overlay .lock-text { font-size: 14px; font-weight: 600; color: var(--text-primary); line-height: 1.5; max-width: 360px; }
    .sk-locked-overlay .lock-sub { font-size: 12px; color: var(--text-muted); max-width: 340px; }
</style>
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
            <?php
                $isSuperAdmin = (session()->get('role') === 'super_admin');
                $isOwnProfile = (session()->get('nip') === $nip);
                $canEditUser = ($isSuperAdmin || $isOwnProfile);
            ?>
            <?php if ($can_edit): ?>
            <a href="<?= base_url('pegawai/edit/' . urlencode($nip)) ?>" class="btn-primary-dark" style="margin-right:6px;">
                <i class="fas fa-edit"></i> Edit Pegawai
            </a>
            <?php endif; ?>
            <?php if ($canEditUser): ?>
            <a href="<?= base_url('user/edit-password/' . urlencode($nip)) ?>" class="btn-warning-dark">
                <i class="fas fa-<?= $isSuperAdmin ? 'user-edit' : 'key' ?>"></i> <?= $isSuperAdmin ? 'Edit User & Password' : 'Ubah Password' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-2" style="align-items:start;">
    <!-- Kolom Kiri -->
    <div style="display:flex;flex-direction:column;gap:1px;min-width:0;">

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
                $isPppk = ($data['status_pegawai'] ?? '') === 'PPPK';
                $skList = [
                    'sk_cpns'             => $isPppk ? 'SMPT Terakhir' : 'SK CPNS',
                    'sk_pns'              => $isPppk ? 'SK PPPK' : 'SK PNS',
                    'sk_pak'              => $isPppk ? 'Dokumen Kontrak Terakhir' : 'SK PAK',
                    'sk_tukin'            => $isPppk ? 'SK TUKIN (Opsional)' : 'SK TUKIN',
                    'sk_jabatan_terakhir' => 'SK Jabatan Terakhir',
                    'skp_terakhir'        => 'SKP Terakhir',
                ];
                foreach ($skList as $fieldKey => $fieldLabel):
                    $skUrl = $data[$fieldKey] ?? null;
                    $fieldLabelPlain = strip_tags($fieldLabel);
                ?>
                <div class="info-row">
                    <span class="info-label"><?= $fieldLabel ?></span>
                    <span class="info-value" style="display:flex;align-items:center;gap:8px;">
                        <?= docLink($skUrl) ?>
                        <?php if ($role === 'pegawai'): ?>
                        <button type="button"
                            class="btn btn-sm" 
                            style="padding:2px 8px;font-size:11px;background:rgba(99,102,241,0.2);color:var(--accent-light);border:1px solid var(--accent);border-radius:4px;"
                            onclick="openUploadSk('<?= $fieldKey ?>', '<?= esc($fieldLabelPlain, 'attr') ?>', '<?= esc($skUrl ?? '', 'attr') ?>')"
                            title="<?= $skUrl ? 'Ganti' : 'Upload' ?> <?= esc($fieldLabelPlain, 'attr') ?>">
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

        <!-- Rekap Penugasan 2026 (Tabel Rekapitulasi Penugasan SDM 2026) -->
        <?php if ($__blokKartuPenugasan): ?>
        <div class="sk-locked-wrapper">
            <div class="sk-locked-overlay">
                <i class="fas fa-lock lock-icon"></i>
                <div class="lock-text">Anda harus mengunggah semua Dokumen SK untuk dapat melihat Rekap Penugasan</div>
                <div class="lock-sub">Silakan lengkapi dokumen SK di bagian Dokumen SK di atas</div>
            </div>
            <div class="sk-locked-content">
        <?php endif; ?>
        <?php
        $pairs = [
            ['z1_tugas', 'z1_hari', 'dl'], ['z2_tugas', 'z2_hari', 'dl'], ['z3_tugas', 'z3_hari', 'dl'], ['z4_tugas', 'z4_hari', 'dl'],
            ['pan_hotel_tugas', 'pan_hotel_hari', 'pan'], ['pan_upt_tugas', 'pan_upt_hari', 'pan'],
            ['fasil_tugas', 'fasil_hari', 'fas'],
            ['pes_hotel_tugas', 'pes_hotel_hari', 'pes'], ['pes_upt_tugas', 'pes_upt_hari', 'pes'],
            ['jml_fasil_tugas', 'jml_fasil_hari', 'jml'], ['jml_dl_tugas', 'jml_dl_hari', 'jml'],
            ['jml_panitia_tugas', 'jml_panitia_hari', 'jml'], ['jml_peserta_tugas', 'jml_peserta_hari', 'jml'],
        ];

        $matriksAll = $matriksRekap ?? [];
        $totalsAll  = ['total_kegiatan' => 0, 'total_hari' => 0];
        foreach ($pairs as [$t, $hr]) { $totalsAll[$t] = 0; $totalsAll[$hr] = 0; }
        foreach ($matriksAll as $r) {
            foreach ($totalsAll as $key => $_) {
                $totalsAll[$key] += (int) ($r[$key] ?? 0);
            }
        }
        $cellVal = static fn ($v, bool $zero = false): string => ((int) $v === 0) ? ($zero ? '0' : '') : (string) (int) $v;
        ?>
        <div class="card-dark">
            <div class="card-header-dark" style="flex-direction:column;align-items:stretch;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                    <h6>
                        <i class="fas fa-chart-bar header-icon"></i> Rekap Penugasan 2026
                        <span class="badge-custom badge-success" style="margin-left:6px;"><?= (int) $totalsAll['total_kegiatan'] ?> Kegiatan · <?= (int) $totalsAll['total_hari'] ?> Hari</span>
                    </h6>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <button type="button" class="btn-filter-rekap active" id="btnFilterSemua" onclick="filterSemuaPegawai()">
                            <i class="fas fa-users"></i> Semua Pegawai
                        </button>
                        <button type="button" class="btn-filter-rekap" id="btnFilterHanyaSaya" onclick="filterHanyaSaya()">
                            <i class="fas fa-user-check"></i> Hanya Saya
                        </button>
                    </div>
                </div>
                <div class="pg-search" style="width:100%;">
                    <i class="fas fa-search"></i>
                    <input type="text" id="inputCariRekap" class="form-control-dark sm" placeholder="Cari nama pegawai atau NIP...">
                </div>
            </div>
            <div class="card-body-dark" style="padding:0;">
                <div class="rk-wrap">
                    <table class="tbl-rekap" id="tblRekapMultiHeader">
                        <thead>
                            <tr>
                                <th rowspan="4" class="s-no">No.</th>
                                <th rowspan="4" class="s-nm">Nama Pegawai</th>
                                <th colspan="8" class="g-dl sep">Dinas Luar</th>
                                <th colspan="4" class="g-pan sep">Panitia</th>
                                <th colspan="2" rowspan="3" class="g-fas sep">Fasilitator</th>
                                <th colspan="4" class="g-pes sep">Peserta</th>
                                <th colspan="8" rowspan="2" class="g-jml sep">Jumlah</th>
                                <th colspan="3" rowspan="3" class="g-tot">Total</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="g-dl">Zona 1</th>
                                <th colspan="2" class="g-dl">Zona 2</th>
                                <th colspan="2" class="g-dl">Zona 3</th>
                                <th colspan="2" class="g-dl sep">Zona 4</th>
                                <th colspan="2" rowspan="2" class="g-pan">Hotel</th>
                                <th colspan="2" rowspan="2" class="g-pan sep">UPT Kemendikdasmen<br><span class="sub">(BGTK/BPMP/BBPL)</span></th>
                                <th colspan="2" rowspan="2" class="g-pes">Hotel</th>
                                <th colspan="2" rowspan="2" class="g-pes sep">UPT Kemendikdasmen<br><span class="sub">(BGTK/BPMP/BBPL)</span></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="sub">Bandar Lampung</th>
                                <th colspan="2" class="sub">Lamsel, Lamteng, Pringsewu,<br>Pesawaran, Metro</th>
                                <th colspan="2" class="sub">Lampura, Lamtim, Tanggamus,<br>Tuba, Tubaba</th>
                                <th colspan="2" class="sub sep">WK, Lambar, Pesbar,<br>Mesuji</th>
                                <th colspan="2" class="g-jml">Fasil</th>
                                <th colspan="2" class="g-jml">Dinas Luar</th>
                                <th colspan="2" class="g-jml">Panitia</th>
                                <th colspan="2" class="g-jml sep">Peserta</th>
                            </tr>
                            <tr>
                                <?php foreach ($pairs as $i => $_): ?>
                                    <th class="lvl4">Penugasan</th>
                                    <th class="lvl4 <?= in_array($i, [3, 5, 6, 8, 12], true) ? 'sep' : '' ?>">Jmlh. Hari<br>Tugas</th>
                                <?php endforeach; ?>
                                <th class="lvl4">Total<br>Kegiatan</th>
                                <th class="lvl4">Total<br>Hari</th>
                                <th class="lvl4">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($matriksAll as $idx => $r):
                            $no = (int) ($r['no_urut_rekap'] ?: $idx + 1);
                            $isSelf = ((string)($r['nip'] ?? '') === (string)($nip ?? ''));
                        ?>
                            <tr class="row-pegawai-rekap<?= $isSelf ? ' row-self' : '' ?>" 
                                data-nip="<?= esc($r['nip']) ?>" 
                                data-search="<?= esc(strtolower(($r['nama_pegawai'] ?? '') . ' ' . ($r['nip'] ?? ''))) ?>">
                                <td class="s-no"><?= $no ?></td>
                                <td class="s-nm" title="<?= esc($r['nama_pegawai']) ?>">
                                    <a href="javascript:void(0)" class="employee-link" data-audit="<?= esc($r['nip']) ?>">
                                        <?= esc($r['nama_pegawai']) ?>
                                    </a>
                                    <?php if ($isSelf): ?>
                                        <span class="badge-custom badge-primary" style="font-size:10px;padding:1px 6px;margin-left:4px;">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($pairs as $i => [$t, $hr, $grp]):
                                    $isJml = $grp === 'jml';
                                    $sep = in_array($i, [3, 5, 6, 8, 12], true) ? ' sep' : '';
                                ?>
                                    <td class="t<?= $isJml ? ' jml' : '' ?>"><?= $cellVal($r[$t], $isJml) ?></td>
                                    <td class="hr<?= $isJml ? ' jml' : '' ?><?= $sep ?>"><?= $cellVal($r[$hr], $isJml) ?></td>
                                <?php endforeach; ?>
                                <td class="gt"><?= (int) $r['total_kegiatan'] ?></td>
                                <td class="gh"><?= (int) $r['total_hari'] ?></td>
                                <td>
                                    <button type="button" class="btn-info-dark btn-sm-custom" data-audit="<?= esc($r['nip']) ?>" title="Lihat rincian penugasan">
                                        <i class="fas fa-search-plus"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="s-no">Total Keseluruhan</th>
                                <?php foreach ($pairs as $i => [$t, $hr]): ?>
                                    <td><?= $totalsAll[$t] ?></td>
                                    <td class="<?= in_array($i, [3, 5, 6, 8, 12], true) ? 'sep' : '' ?>"><?= $totalsAll[$hr] ?></td>
                                <?php endforeach; ?>
                                <td style="color:var(--success);"><?= $totalsAll['total_kegiatan'] ?></td>
                                <td style="color:var(--info);"><?= $totalsAll['total_hari'] ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="pg-empty" id="emptyRekap" style="display:none;padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">
                        <i class="fas fa-search"></i> Pegawai tidak ditemukan.
                    </div>
                </div>
            </div>
        </div>
        <?php if ($__blokKartuPenugasan): ?>
            </div><!-- /.sk-locked-content -->
        </div><!-- /.sk-locked-wrapper -->
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Kalender Kedinasan & Rincian Penugasan 2026 -->
    <?php
    $penList       = $penugasanList ?? [];
    $rk            = $rekapPegawai ?? [];
    $totalKegiatan = (int) ($rk['total_kegiatan'] ?? count($penList));
    $totalHariPen  = (int) ($rk['total_hari'] ?? array_sum(array_map(static fn ($x) => (int) ($x['jumlah_hari'] ?? 0), $penList)));
    ?>
    <?php if ($__blokKartuPenugasan): ?>
    <div class="sk-locked-wrapper">
        <div class="sk-locked-overlay">
            <i class="fas fa-lock lock-icon"></i>
            <div class="lock-text">Anda harus mengunggah semua Dokumen SK untuk dapat melihat ini</div>
            <div class="lock-sub">Silakan lengkapi dokumen SK di bagian Dokumen SK terlebih dahulu</div>
        </div>
        <div class="sk-locked-content">
    <?php endif; ?>
    <div style="display:flex;flex-direction:column;gap:1px;min-width:0;">
        <div class="card-dark">
            <div class="card-header-dark">
                <h6>
                    <i class="fas fa-calendar-alt header-icon"></i> Kalender Kedinasan &amp; Kode SDM 2026
                    <span class="badge-custom badge-primary" style="margin-left:6px;"><?= $totalKegiatan ?> Kegiatan · <?= $totalHariPen ?> Hari</span>
                </h6>
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
    <?php if ($__blokKartuPenugasan): ?>
        </div><!-- /.sk-locked-content -->
    </div><!-- /.sk-locked-wrapper -->
    <?php endif; ?>
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

<!-- MODAL AUDIT TRAIL / RINCIAN PENUGASAN REKAP -->
<div class="modal fade" id="modalAuditRekap" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check" style="color:var(--accent);margin-right:8px;"></i>
                    <span id="auditRekapNama">Rincian Penugasan SDM</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div style="font-size:12px;font-weight:700;color:var(--text-muted);letter-spacing:0.5px;text-transform:uppercase;margin-bottom:8px;">
                    Ringkasan Kode per Kategori
                </div>
                <div id="auditRingkasanBadges" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;"></div>

                <div class="pg-scroll" style="max-height:360px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <table class="table-dark-custom" style="font-size:12px;margin:0;">
                        <thead>
                            <tr>
                                <th style="width:60px;text-align:center;">Kode</th>
                                <th>Kegiatan</th>
                                <th>Peran</th>
                                <th>Lokus</th>
                                <th style="text-align:center;">Zona</th>
                                <th style="text-align:center;">Tahap</th>
                                <th style="text-align:center;">Tanggal</th>
                                <th style="text-align:center;">Hari</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAuditTrail"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary-dark" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

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

// Filter & Search Rekap SDM
var inputCari = document.getElementById('inputCariRekap');
if (inputCari) {
    inputCari.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#tblRekapMultiHeader tbody tr.row-pegawai-rekap');
        var shown = 0;
        rows.forEach(function(tr) {
            var match = !q || (tr.getAttribute('data-search') || '').includes(q);
            tr.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        var emptyEl = document.getElementById('emptyRekap');
        if (emptyEl) emptyEl.style.display = shown ? 'none' : 'block';
    });
}

function filterHanyaSaya() {
    var myNip = '<?= esc($nip, 'js') ?>';
    var rows = document.querySelectorAll('#tblRekapMultiHeader tbody tr.row-pegawai-rekap');
    var shown = 0;
    rows.forEach(function(tr) {
        var isMe = (tr.getAttribute('data-nip') || '') === myNip;
        tr.style.display = isMe ? '' : 'none';
        if (isMe) shown++;
    });
    var emptyEl = document.getElementById('emptyRekap');
    if (emptyEl) emptyEl.style.display = shown ? 'none' : 'block';
    
    var btnSaya = document.getElementById('btnFilterHanyaSaya');
    var btnSemua = document.getElementById('btnFilterSemua');
    if (btnSaya) btnSaya.classList.add('active');
    if (btnSemua) btnSemua.classList.remove('active');
}

function filterSemuaPegawai() {
    var rows = document.querySelectorAll('#tblRekapMultiHeader tbody tr.row-pegawai-rekap');
    rows.forEach(function(tr) { tr.style.display = ''; });
    var emptyEl = document.getElementById('emptyRekap');
    if (emptyEl) emptyEl.style.display = 'none';
    var inputCari = document.getElementById('inputCariRekap');
    if (inputCari) inputCari.value = '';

    var btnSaya = document.getElementById('btnFilterHanyaSaya');
    var btnSemua = document.getElementById('btnFilterSemua');
    if (btnSemua) btnSemua.classList.add('active');
    if (btnSaya) btnSaya.classList.remove('active');
}

document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-audit]');
    if (el) {
        e.preventDefault();
        bukaModalAuditTrail(el.getAttribute('data-audit'));
    }
});

function pgRoleBadge(peran) {
    var map = {
        'Dinas Luar': 'badge-info', 'Panitia Hotel': 'badge-primary', 'Panitia UPT': 'badge-primary',
        'Fasilitator': 'badge-success', 'Peserta Hotel': 'badge-warning', 'Peserta UPT': 'badge-warning'
    };
    return '<span class="badge-custom ' + (map[peran] || 'badge-secondary') + '">' + escHtml(peran) + '</span>';
}

function bukaModalAuditTrail(nip) {
    document.getElementById('auditRekapNama').textContent = 'Memuat...';
    document.getElementById('auditRingkasanBadges').innerHTML = '';
    document.getElementById('tbodyAuditTrail').innerHTML = '<tr><td colspan="8" class="text-center" style="padding:24px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin"></i> Memuat rincian penugasan...</td></tr>';
    $('#modalAuditRekap').modal('show');
    muatDataAuditTrail(nip);
}

function muatDataAuditTrail(nip) {
    fetch('<?= base_url('rekap-penugasan/detail') ?>/' + encodeURIComponent(nip))
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.status !== 'ok') {
                document.getElementById('tbodyAuditTrail').innerHTML = '<tr><td colspan="8" class="text-center" style="padding:20px;color:var(--danger);">' + escHtml(res.message || 'Gagal memuat data.') + '</td></tr>';
                return;
            }
            var peg = res.pegawai || {};
            document.getElementById('auditRekapNama').textContent = (peg.nama || nip) + ' · NIP ' + (peg.nip || nip);

            var ring = res.ringkasan || {};
            var keys = Object.keys(ring);
            document.getElementById('auditRingkasanBadges').innerHTML = keys.length
                ? keys.map(function(k) {
                    return '<div class="ring-box"><strong>' + escHtml(k) + ':</strong> ' + escHtml(ring[k].join(', ')) + '</div>';
                }).join('')
                : '<span style="color:var(--text-muted);font-size:12px;">Belum ada penugasan tercatat.</span>';

            var rows = res.rincian || [];
            var tbody = document.getElementById('tbodyAuditTrail');
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding:24px;color:var(--text-muted);">Belum ada penugasan tercatat untuk pegawai ini.</td></tr>';
                return;
            }
            tbody.innerHTML = rows.map(function(r) {
                return '<tr>' +
                    '<td style="text-align:center;"><span style="display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:24px;padding:0 6px;border-radius:5px;font-weight:800;font-size:11px;color:#0f172a;background:' + escHtml(r.warna_hex || '#E5B8B7') + ';border:1px solid rgba(0,0,0,0.25);">K' + escHtml(r.kode_kegiatan) + '</span></td>' +
                    '<td style="color:var(--text-primary);font-weight:600;">' + escHtml(r.nama_kegiatan) + '</td>' +
                    '<td>' + pgRoleBadge(r.kategori_peran) + (r.peran_detail ? '<br><small style="color:var(--text-muted);">' + escHtml(r.peran_detail) + '</small>' : '') + '</td>' +
                    '<td>' + escHtml(r.kab_kota_lokus || '-') + (r.detail_lokus && r.detail_lokus !== r.kab_kota_lokus ? '<br><small style="color:var(--text-muted);">' + escHtml(r.detail_lokus) + '</small>' : '') + '</td>' +
                    '<td style="text-align:center;"><span class="badge-custom ' + (r.zona === 'NON_ZONA' ? 'badge-secondary' : 'badge-info') + '">' + escHtml(r.zona) + '</span></td>' +
                    '<td style="text-align:center;">' + escHtml(r.tahap || '—') + '</td>' +
                    '<td style="text-align:center;font-size:11.5px;white-space:nowrap;">' + escHtml(r.tanggal_mulai_tugas) + '<br><small style="color:var(--text-muted);">s.d. ' + escHtml(r.tanggal_selesai_tugas) + '</small></td>' +
                    '<td style="text-align:center;color:var(--text-primary);font-weight:700;">' + escHtml(r.jumlah_hari) + '</td>' +
                '</tr>';
            }).join('');
        })
        .catch(function(err) {
            document.getElementById('tbodyAuditTrail').innerHTML = '<tr><td colspan="8" class="text-center" style="padding:20px;color:var(--danger);">Gagal menghubungi server.</td></tr>';
        });
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

