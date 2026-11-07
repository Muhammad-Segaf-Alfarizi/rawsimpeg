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
        <div class="month-chips" style="margin-bottom:16px;">
            <a href="<?= $qs(['bulan' => 'all']) ?>" class="pg-chip <?= $bulanFilter === null ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Jan – Des</a>
            <?php foreach ($namaBulan as $mNum => $mNama): ?>
                <a href="<?= $qs(['bulan' => $mNum]) ?>" class="pg-chip <?= $bulanFilter === $mNum ? 'active' : '' ?>"><?= substr($mNama, 0, 3) ?></a>
            <?php endforeach; ?>
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
                <div id="boxTugasEksisting" style="display:none;margin-bottom:16px;">
                    <div class="section-title">Penugasan pada tanggal ini</div>
                    <div id="listTugasEksisting"></div>
                </div>
                <div class="pg-panel">
                    <div class="pg-panel-title" id="judulFormSelJadwal"><i class="fas fa-plus-circle"></i> Tambah Penugasan</div>
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
                                <input type="date" name="tanggal_mulai_tugas" id="jselTglMulai" class="form-control-dark sm" required>
                            </div>
                            <div class="col-md-4 form-group" style="margin-bottom:0;">
                                <label class="form-label-dark">Selesai *</label>
                                <input type="date" name="tanggal_selesai_tugas" id="jselTglSelesai" class="form-control-dark sm" required>
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

// ── Peta tugas per baris & hari ─────────────────────────────────────
JW_ROWS.forEach(r => {
    r.byDay = {};
    r.tasks.forEach(t => {
        for (let ts = new Date(t.m + 'T00:00:00'); ts <= new Date(t.s + 'T00:00:00'); ts.setDate(ts.getDate() + 1)) {
            const key = `${ts.getFullYear()}-${String(ts.getMonth()+1).padStart(2,'0')}-${String(ts.getDate()).padStart(2,'0')}`;
            if (key in dayIndex) (r.byDay[key] = r.byDay[key] || []).push(t);
        }
    });
});

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
            if (ts) {
                const k = JW_KEG[ts[0].idk] || { kode: '?', warna: '#E5B8B7' };
                html += `<td class="c f" data-di="${di}" style="background:${k.warna}">${h(k.kode)}${ts.length > 1 ? '<span class="x"></span>' : ''}</td>`;
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
    return { r, day, tasks: r.byDay[day.key] || [] };
}
function fmtTgl(key) { const [y, m, d] = key.split('-'); return `${+d} ${NAMA_BULAN[+m]} ${y}`; }

const wrap = document.getElementById('jwWrap');
wrap.addEventListener('mouseover', e => {
    const td = e.target.closest('td.c.f');
    if (!td) { tip.style.display = 'none'; return; }
    const info = cellInfo(td);
    if (!info) return;
    tip.innerHTML = `<div class="tip-date">${h(info.r.nama)} · ${fmtTgl(info.day.key)}</div>` + info.tasks.map(t => {
        const k = JW_KEG[t.idk] || {};
        const lokus = [t.kab, t.det && t.det !== t.kab && t.det !== '-' ? t.det : ''].filter(Boolean).join(' — ');
        return `<div class="tip-item">
            <div class="tip-title"><span class="kode-badge sm" style="background:${k.warna};min-width:24px;height:18px;font-size:10px;margin-right:6px;">${h(k.kode)}</span>${h(k.nama)}</div>
            <div>${h(t.peran)}${t.tahap ? ' · ' + h(t.tahap) : ''}${t.zona !== 'NON_ZONA' ? ' · ' + h(t.zona) : ''}</div>
            ${lokus ? `<div>${h(lokus)}</div>` : ''}
            <div>${h(t.m)} s.d. ${h(t.s)} (${t.hari} hari)</div>
        </div>`;
    }).join('') + (info.tasks.length > 1 ? `<div class="tip-item" style="color:#f87171;"><i class="fas fa-exclamation-triangle"></i> Bentrok ${info.tasks.length} kegiatan</div>` : '');
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
    if (info) { tip.style.display = 'none'; klikSelJadwal(info.r, info.day.key, info.tasks); }
});

<?php if ($canEdit): ?>
let selCtx = null;

function klikSelJadwal(r, dateKey, tasks) {
    selCtx = { r, dateKey };
    document.getElementById('jadwalModalNama').textContent = r.nama;
    document.getElementById('jadwalModalTanggal').textContent = fmtTgl(dateKey);
    document.getElementById('jselNip').value = r.nip;
    resetFormSel();

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
