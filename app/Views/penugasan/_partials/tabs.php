<?php
/**
 * Partial bersama modul Penugasan SDM BGTK 2026:
 * - CSS modul (memakai variabel tema dari layouts/main)
 * - Navigasi tab: Kode dan SDM | Jadwal | Rekap
 */
$__tab = $active_tab ?? 'kode_sdm';
$__canEdit = in_array(session()->get('role'), ['super_admin', 'admin'], true);
$__tabs = [
    'kode_sdm' => ['url' => 'kegiatan',         'icon' => 'fa-clipboard-list', 'label' => 'Kode dan SDM'],
    'jadwal'   => ['url' => 'jadwal-penugasan', 'icon' => 'fa-calendar-check', 'label' => 'Jadwal'],
    'rekap'    => ['url' => 'rekap-penugasan',  'icon' => 'fa-chart-bar',      'label' => 'Rekap'],
];
$__hints = [
    'kode_sdm' => $__canEdit ? 'Gunakan tombol Kelola SDM untuk menambah atau menghapus pegawai per kegiatan.' : 'Daftar kegiatan dan rincian SDM sesuai sheet Kode dan SDM.',
    'jadwal'   => $__canEdit ? 'Klik sel tanggal untuk menambah, mengubah, atau menghapus penugasan.' : 'Arahkan kursor ke sel berwarna untuk melihat detail kegiatan.',
    'rekap'    => 'Dihitung otomatis dari Kode dan SDM &amp; Jadwal. Klik nama pegawai untuk rincian kode.',
];
$tabs_hint = $__hints[$__tab] ?? '';
?>
<style>
    /* ── Tab navigasi modul ─────────────────────────────────────────── */
    .pg-tabs { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
    .pg-tab-group { display:inline-flex; gap:4px; padding:4px; background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius); }
    .pg-tab { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; border-radius:var(--radius-sm); font-size:13px; font-weight:600; color:var(--text-secondary); text-decoration:none !important; transition:var(--transition); }
    .pg-tab i { color:var(--text-muted); transition:var(--transition); }
    .pg-tab:hover { background:rgba(99,102,241,0.10); color:var(--text-primary); }
    .pg-tab.active { background:linear-gradient(135deg, var(--accent), var(--accent-dark)); color:#fff; box-shadow:0 4px 16px var(--accent-glow); }
    .pg-tab.active i { color:#fff; }
    .pg-tab .pg-tab-sheet { font-size:10px; font-weight:500; opacity:.7; }

    /* ── Toolbar / filter ───────────────────────────────────────────── */
    .pg-toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
    .pg-toolbar .pg-field { display:flex; flex-direction:column; min-width:180px; flex:1; }
    .pg-toolbar .pg-field.grow-2 { flex:2; }
    .form-control-dark.sm { padding:7px 10px; font-size:13px; }
    select.form-control-dark { cursor:pointer; }
    .pg-search { position:relative; }
    .pg-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:13px; pointer-events:none; }
    .pg-search .form-control-dark { padding-left:34px; }
    .pg-hint { font-size:12px; color:var(--text-muted); display:inline-flex; align-items:center; gap:6px; }
    .pg-hint i { color:var(--accent-light); }

    /* ── Chip / pill umum ───────────────────────────────────────────── */
    .pg-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:500; color:var(--text-secondary); background:rgba(255,255,255,0.04); border:1px solid var(--border); text-decoration:none !important; cursor:pointer; transition:var(--transition); white-space:nowrap; }
    [data-theme="light"] .pg-chip { background:#f8fafc; }
    .pg-chip:hover { color:var(--text-primary); border-color:var(--border-accent); background:rgba(99,102,241,0.08); }
    .pg-chip.active { color:#fff; background:rgba(99,102,241,0.25); border-color:var(--accent); }
    [data-theme="light"] .pg-chip.active { background:var(--accent); color:#fff; }
    .pg-swatch { width:12px; height:12px; border-radius:3px; border:1px solid rgba(0,0,0,0.35); flex-shrink:0; display:inline-block; }

    /* ── Badge kode kegiatan (warna sel asli Excel) ────────────────── */
    .kode-badge { display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:34px; padding:0 8px; border-radius:var(--radius-sm); font-weight:800; font-size:14px; color:#0f172a; border:1px solid rgba(0,0,0,0.25); box-shadow:0 2px 8px rgba(0,0,0,0.3); }
    .kode-badge.sm { min-width:32px; height:24px; font-size:11.5px; border-radius:6px; }

    /* ── Tabel modul ────────────────────────────────────────────────── */
    .pg-scroll { overflow:auto; position:relative; }
    .pg-empty { padding:28px; text-align:center; color:var(--text-muted); font-size:13px; }

    /* ── Toast bertema ──────────────────────────────────────────────── */
    #pgToastWrap { position:fixed; top:80px; right:24px; z-index:2000; display:flex; flex-direction:column; gap:8px; max-width:380px; }
    #pgToastWrap .alert-dark { margin:0; box-shadow:var(--shadow-lg); backdrop-filter:blur(6px); background-color:var(--bg-card); animation:pgToastIn .2s ease; }
    @keyframes pgToastIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

    /* ── Tooltip bertema (Jadwal) ───────────────────────────────────── */
    #pgTip { position:fixed; z-index:2000; pointer-events:none; display:none; max-width:340px; background:var(--bg-card-hover); border:1px solid var(--border-accent); border-radius:var(--radius-sm); box-shadow:var(--shadow-lg); padding:10px 12px; font-size:12px; color:var(--text-secondary); line-height:1.5; }
    #pgTip .tip-title { color:var(--text-primary); font-weight:600; margin-bottom:2px; }
    #pgTip .tip-item + .tip-item { margin-top:8px; padding-top:8px; border-top:1px solid var(--border); }
    #pgTip .tip-date { color:var(--accent-light); font-weight:600; font-size:11px; margin-bottom:6px; }

    /* ── Modal tweaks ───────────────────────────────────────────────── */
    .modal .form-label-dark { color:var(--text-secondary) !important; font-size:12px !important; }
    .pg-panel { background:var(--bg-secondary); border:1px solid var(--border); border-radius:var(--radius-sm); padding:16px; }
    .pg-panel-title { font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
    .pg-panel-title i { color:var(--accent); }
</style>

<div class="pg-tabs">
    <nav class="pg-tab-group" aria-label="Navigasi Penugasan SDM 2026">
        <?php foreach ($__tabs as $key => $t): ?>
            <a href="<?= base_url($t['url']) ?>" class="pg-tab <?= $__tab === $key ? 'active' : '' ?>">
                <i class="fas <?= $t['icon'] ?>"></i> <?= $t['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php if (!empty($tabs_hint)): ?>
        <span class="pg-hint"><i class="fas fa-info-circle"></i> <?= $tabs_hint ?></span>
    <?php endif; ?>
</div>

