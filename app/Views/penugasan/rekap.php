<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$canEdit = in_array($role, ['super_admin', 'admin'], true);

// 13 pasang kolom sesuai urutan sheet Rekap
$pairs = [
    ['z1_tugas', 'z1_hari', 'dl'], ['z2_tugas', 'z2_hari', 'dl'], ['z3_tugas', 'z3_hari', 'dl'], ['z4_tugas', 'z4_hari', 'dl'],
    ['pan_hotel_tugas', 'pan_hotel_hari', 'pan'], ['pan_upt_tugas', 'pan_upt_hari', 'pan'],
    ['fasil_tugas', 'fasil_hari', 'fas'],
    ['pes_hotel_tugas', 'pes_hotel_hari', 'pes'], ['pes_upt_tugas', 'pes_upt_hari', 'pes'],
    ['jml_fasil_tugas', 'jml_fasil_hari', 'jml'], ['jml_dl_tugas', 'jml_dl_hari', 'jml'],
    ['jml_panitia_tugas', 'jml_panitia_hari', 'jml'], ['jml_peserta_tugas', 'jml_peserta_hari', 'jml'],
];

$totals = ['total_kegiatan' => 0, 'total_hari' => 0];
foreach ($pairs as [$t, $hr]) { $totals[$t] = 0; $totals[$hr] = 0; }
foreach ($matriks as $r) {
    foreach ($totals as $key => $_) $totals[$key] += (int) ($r[$key] ?? 0);
}

// Kolom kategori: kosong jika 0 (seperti Excel); kolom Jumlah: tampilkan 0
$cell = static fn ($v, bool $zero = false): string => ((int) $v === 0) ? ($zero ? '0' : '') : (string) (int) $v;
?>

<?= $this->include('penugasan/_partials/tabs') ?>

<style>
    .rk-wrap { overflow:auto; max-height:72vh; }
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
    .tbl-rekap .s-nm { left:44px; min-width:240px; max-width:240px; text-align:left; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; border-right:1px solid var(--border-accent); }
    .tbl-rekap thead .s-no, .tbl-rekap thead .s-nm { z-index:6; background:var(--bg-secondary); top:0; }
    .tbl-rekap tbody tr:hover td.s-no, .tbl-rekap tbody tr:hover td.s-nm { background:var(--bg-card-hover); }
    .tbl-rekap tbody tr.flag td.s-nm, .tbl-rekap tbody tr.flag td.s-no { box-shadow:inset 3px 0 0 var(--danger); }
    .tbl-rekap tbody tr.flag td.s-nm .employee-link { color:#f87171; }
    .tbl-rekap tfoot td, .tbl-rekap tfoot th { position:sticky; bottom:0; background:var(--bg-secondary); color:var(--text-primary); font-weight:700; border-top:2px solid var(--accent); z-index:3; }
    .tbl-rekap tfoot .s-no { z-index:5; text-align:right; padding-right:14px; color:var(--accent-light); font-size:11px; letter-spacing:1px; text-transform:uppercase; }
    .ring-box { background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; font-size:12px; }
    .ring-box strong { color:var(--accent-light); margin-right:4px; }
</style>

<!-- STATISTIK -->
<div class="grid grid-4" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon accent"><i class="fas fa-users"></i></div>
        <div><div class="stat-value"><?= count($matriks) ?></div><div class="stat-label">Pegawai</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-route"></i></div>
        <div><div class="stat-value"><?= $totals['jml_dl_tugas'] ?></div><div class="stat-label">Dinas Luar · <?= $totals['jml_dl_hari'] ?> hari</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-user-tie"></i></div>
        <div><div class="stat-value"><?= $totals['jml_panitia_tugas'] ?></div><div class="stat-label">Panitia · <?= $totals['jml_panitia_hari'] ?> hari</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-double"></i></div>
        <div><div class="stat-value"><?= $totals['total_kegiatan'] ?></div><div class="stat-label">Total Penugasan · <?= $totals['total_hari'] ?> hari</div></div>
    </div>
</div>

<!-- TABEL REKAP -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-chart-bar header-icon"></i> Rekapitulasi Penugasan SDM 2026</h5>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div class="pg-search" style="min-width:260px;">
                <i class="fas fa-search"></i>
                <input type="text" id="inputCariRekap" class="form-control-dark sm" placeholder="Cari nama atau NIP...">
            </div>
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
                <?php foreach ($matriks as $idx => $r):
                    $no = (int) ($r['no_urut_rekap'] ?: $idx + 1);
                ?>
                    <tr class="row-pegawai-rekap" data-search="<?= esc(strtolower($r['nama_pegawai'] . ' ' . $r['nip'])) ?>">
                        <td class="s-no"><?= $no ?></td>
                        <td class="s-nm" title="<?= esc($r['nama_pegawai']) ?>">
                            <a href="javascript:void(0)" class="employee-link" data-audit="<?= esc($r['nip']) ?>"><?= esc($r['nama_pegawai']) ?></a>
                        </td>
                        <?php foreach ($pairs as $i => [$t, $hr, $grp]):
                            $isJml = $grp === 'jml';
                            $sep = in_array($i, [3, 5, 6, 8, 12], true) ? ' sep' : '';
                        ?>
                            <td class="t<?= $isJml ? ' jml' : '' ?>"><?= $cell($r[$t], $isJml) ?></td>
                            <td class="hr<?= $isJml ? ' jml' : '' ?><?= $sep ?>"><?= $cell($r[$hr], $isJml) ?></td>
                        <?php endforeach; ?>
                        <td class="gt"><?= (int) $r['total_kegiatan'] ?></td>
                        <td class="gh"><?= (int) $r['total_hari'] ?></td>
                        <td>
                            <button type="button" class="btn-info-dark btn-sm-custom" data-audit="<?= esc($r['nip']) ?>"><i class="fas fa-search-plus"></i> Detail</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="s-no">Total Keseluruhan</th>
                        <?php foreach ($pairs as $i => [$t, $hr]): ?>
                            <td><?= $totals[$t] ?></td>
                            <td class="<?= in_array($i, [3, 5, 6, 8, 12], true) ? 'sep' : '' ?>"><?= $totals[$hr] ?></td>
                        <?php endforeach; ?>
                        <td style="color:var(--success);"><?= $totals['total_kegiatan'] ?></td>
                        <td style="color:var(--info);"><?= $totals['total_hari'] ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div class="pg-empty" id="emptyRekap" style="display:none;"><i class="fas fa-search"></i> Pegawai tidak ditemukan.</div>
        </div>
    </div>
</div>

<!-- MODAL AUDIT TRAIL -->
<div class="modal fade" id="modalAuditRekap" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-clipboard-check" style="color:var(--accent);margin-right:8px;"></i><span id="auditRekapNama"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="section-title">Ringkasan kode per kategori</div>
                <div id="auditRingkasanBadges" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;"></div>

                <?php if ($canEdit): ?>
                <div class="pg-panel" style="margin-bottom:16px;">
                    <div class="pg-panel-title"><i class="fas fa-plus-circle"></i> Tambah Penugasan untuk Pegawai Ini</div>
                    <form id="formAuditTambah" onsubmit="submitAuditTambah(event)">
                        <input type="hidden" name="nip" id="auditInputNip">
                        <div class="row" style="align-items:flex-end;">
                            <div class="col-lg-4 form-group" style="margin-bottom:8px;">
                                <label class="form-label-dark">Kegiatan *</label>
                                <select name="id_kegiatan" id="auditKegiatan" class="form-control-dark sm" required>
                                    <option value="">— Pilih kegiatan —</option>
                                    <?php foreach ($kegiatanList as $k): ?>
                                        <option value="<?= (int) $k['id_kegiatan'] ?>" data-m="<?= esc($k['tanggal_mulai'] ?? '') ?>" data-s="<?= esc($k['tanggal_selesai'] ?? '') ?>">Kode <?= esc($k['kode_kegiatan']) ?> — <?= esc($k['nama_kegiatan']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 form-group" style="margin-bottom:8px;">
                                <label class="form-label-dark">Peran *</label>
                                <select name="kategori_peran" class="form-control-dark sm">
                                    <option value="Dinas Luar">Dinas Luar</option>
                                    <option value="Panitia Hotel">Panitia Hotel</option>
                                    <option value="Panitia UPT">Panitia UPT</option>
                                    <option value="Fasilitator">Fasilitator</option>
                                    <option value="Peserta Hotel">Peserta Hotel</option>
                                    <option value="Peserta UPT">Peserta UPT</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 form-group" style="margin-bottom:8px;">
                                <label class="form-label-dark">Kab/Kota</label>
                                <select name="kab_kota_lokus" class="form-control-dark sm">
                                    <?php foreach ($kabKotaMap as $kab => $z): ?>
                                        <option value="<?= esc($kab) ?>"><?= esc($kab) ?> (<?= esc($z) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 form-group" style="margin-bottom:8px;">
                                <label class="form-label-dark">Mulai</label>
                                <input type="date" name="tanggal_mulai_tugas" id="auditMulai" class="form-control-dark sm">
                            </div>
                            <div class="col-lg-2 col-md-4 form-group" style="margin-bottom:8px;">
                                <label class="form-label-dark">Selesai</label>
                                <input type="date" name="tanggal_selesai_tugas" id="auditSelesai" class="form-control-dark sm">
                            </div>
                        </div>
                        <button type="submit" class="btn-success-dark"><i class="fas fa-plus"></i> Tambahkan</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="pg-scroll" style="max-height:340px;border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th style="width:60px;">Kode</th>
                                <th>Kegiatan</th>
                                <th>Peran</th>
                                <th>Lokus</th>
                                <th style="text-align:center;">Zona</th>
                                <th style="text-align:center;">Tahap</th>
                                <th style="text-align:center;">Tanggal</th>
                                <th style="text-align:center;">Hari</th>
                                <?php if ($canEdit): ?><th style="width:60px;"></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tbodyAuditTrail"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary-dark" onclick="window.location.reload()"><i class="fas fa-sync-alt"></i> Tutup &amp; Segarkan Rekap</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('penugasan/_partials/js_helpers') ?>
<script>
const CAN_EDIT_REKAP = <?= $canEdit ? 'true' : 'false' ?>;
let currentAuditNip = '';

document.getElementById('inputCariRekap').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let shown = 0;
    document.querySelectorAll('#tblRekapMultiHeader tbody tr.row-pegawai-rekap').forEach(tr => {
        const ok = !q || tr.dataset.search.includes(q);
        tr.style.display = ok ? '' : 'none';
        if (ok) shown++;
    });
    document.getElementById('emptyRekap').style.display = shown ? 'none' : 'block';
});

document.getElementById('tblRekapMultiHeader').addEventListener('click', e => {
    const el = e.target.closest('[data-audit]');
    if (el) bukaModalAuditTrail(el.dataset.audit);
});

function bukaModalAuditTrail(nip) {
    currentAuditNip = nip;
    const inp = document.getElementById('auditInputNip');
    if (inp) inp.value = nip;
    document.getElementById('auditRekapNama').textContent = 'Memuat...';
    document.getElementById('auditRingkasanBadges').innerHTML = '';
    document.getElementById('tbodyAuditTrail').innerHTML = '<tr><td colspan="9" class="pg-empty"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>';
    $('#modalAuditRekap').modal('show');
    muatDataAuditTrail(nip);
}

function muatDataAuditTrail(nip) {
    fetch('<?= base_url('rekap-penugasan/detail') ?>/' + encodeURIComponent(nip)).then(r => r.json()).then(res => {
        if (res.status !== 'ok') { pgToast(res.message || 'Gagal memuat', 'danger'); return; }
        const peg = res.pegawai || {};
        document.getElementById('auditRekapNama').textContent = `${peg.nama || nip} · NIP ${peg.nip || nip}`;

        const ring = res.ringkasan || {};
        const keys = Object.keys(ring);
        document.getElementById('auditRingkasanBadges').innerHTML = keys.length
            ? keys.map(k => `<div class="ring-box"><strong>${h(k)}:</strong>${h(ring[k].join(', '))}</div>`).join('')
            : '<span class="pg-hint">Belum ada penugasan tercatat.</span>';

        const rows = res.rincian || [];
        const tbody = document.getElementById('tbodyAuditTrail');
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="9" class="pg-empty">Belum ada penugasan.</td></tr>'; return; }
        tbody.innerHTML = rows.map(r => `<tr>
            <td><span class="kode-badge sm" style="background:${h(r.warna_hex || '#E5B8B7')}">${h(r.kode_kegiatan)}</span></td>
            <td style="color:var(--text-primary);">${h(r.nama_kegiatan)}</td>
            <td>${pgRoleBadge(r.kategori_peran)}<br><small style="color:var(--text-muted);">${h(r.peran_detail || '')}</small></td>
            <td>${h(r.kab_kota_lokus || '-')}<br><small style="color:var(--text-muted);">${h(r.detail_lokus || '')}</small></td>
            <td style="text-align:center;"><span class="badge-custom ${r.zona === 'NON_ZONA' ? 'badge-secondary' : 'badge-info'}">${h(r.zona)}</span></td>
            <td style="text-align:center;">${h(r.tahap || '—')}</td>
            <td style="text-align:center;font-size:12px;">${h(r.tanggal_mulai_tugas)}<br>${h(r.tanggal_selesai_tugas)}</td>
            <td style="text-align:center;color:var(--text-primary);font-weight:700;">${h(r.jumlah_hari)}</td>
            ${CAN_EDIT_REKAP ? `<td style="text-align:center;"><button type="button" class="btn-danger-dark btn-sm-custom" onclick="hapusDariAudit(${Number(r.id_penugasan)})"><i class="fas fa-trash"></i></button></td>` : ''}
        </tr>`).join('');
    });
}

<?php if ($canEdit): ?>
document.getElementById('auditKegiatan').addEventListener('change', function () {
    const o = this.selectedOptions[0];
    if (!o) return;
    document.getElementById('auditMulai').value = o.dataset.m || '';
    document.getElementById('auditSelesai').value = o.dataset.s || '';
});

function submitAuditTambah(e) {
    e.preventDefault();
    pgPost('<?= base_url('kegiatan/penugasan/simpan') ?>', new FormData(e.target)).then(res => {
        pgToast(res.message || 'Selesai', res.status === 'ok' ? 'success' : 'danger');
        if (res.status === 'ok') muatDataAuditTrail(currentAuditNip);
    });
}

function hapusDariAudit(id) {
    pgConfirm('Hapus penugasan ini dari rekap pegawai?', { title: 'Hapus Penugasan', okText: 'Hapus', danger: true }).then(ok => {
        if (!ok) return;
        pgPost('<?= base_url('kegiatan/penugasan/hapus') ?>/' + id).then(res => {
            pgToast(res.message || 'Selesai', res.status === 'ok' ? 'success' : 'danger');
            if (res.status === 'ok') muatDataAuditTrail(currentAuditNip);
        });
    });
}
<?php endif; ?>
</script>
<?= $this->endSection() ?>
