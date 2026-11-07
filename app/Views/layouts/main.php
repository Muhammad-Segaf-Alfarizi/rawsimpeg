<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Manajemen Pegawai BGTK">
    <title><?= $page_title ?? 'SIMPEG' ?> &mdash; BGTK</title>

    <script>
    (function () {
        try {
            var saved = localStorage.getItem('simpeg_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        } catch (e) {}
    })();
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-primary:    #0f1117;
            --bg-secondary:  #1a1d27;
            --bg-card:       #1e2130;
            --bg-card-hover: #252840;
            --sidebar-width: 260px;
            --accent:        #6366f1;
            --accent-light:  #818cf8;
            --accent-dark:   #4f52cc;
            --accent-glow:   rgba(99,102,241,0.25);
            --success:       #10b981;
            --warning:       #f59e0b;
            --danger:        #ef4444;
            --info:          #3b82f6;
            --text-primary:  #f1f5f9;
            --text-secondary:#94a3b8;
            --text-muted:    #64748b;
            --border:        rgba(255,255,255,0.07);
            --border-accent: rgba(99,102,241,0.3);
            --shadow:        0 4px 24px rgba(0,0,0,0.4);
            --shadow-lg:     0 8px 48px rgba(0,0,0,0.5);
            --radius:        12px;
            --radius-sm:     8px;
            --transition:    all 0.2s ease;
        }

        [data-theme="light"] {
            --bg-primary:    #f1f5f9;
            --bg-secondary:  #ffffff;
            --bg-card:       #ffffff;
            --bg-card-hover: #f8fafc;
            --accent:        #4f46e5;
            --accent-light:  #4338ca;
            --accent-dark:   #3730a3;
            --accent-glow:   rgba(79,70,229,0.18);
            --success:       #059669;
            --warning:       #d97706;
            --danger:        #dc2626;
            --info:          #2563eb;
            --text-primary:  #0f172a;
            --text-secondary:#334155;
            --text-muted:    #64748b;
            --border:        #e2e8f0;
            --border-accent: rgba(79,70,229,0.35);
            --shadow:        0 4px 20px rgba(15,23,42,0.06);
            --shadow-lg:     0 10px 32px rgba(15,23,42,0.12);
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif !important; background: var(--bg-primary) !important; color: var(--text-primary) !important; min-height: 100vh; overflow-x: hidden; margin: 0; padding: 0; transition: background-color 0.2s ease, color 0.2s ease; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 3px; }
        a { color: var(--accent-light); }
        a:hover { color: var(--accent); text-decoration: none; }

        /* LAYOUT */
        #wrapper { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        #sidebar { width: var(--sidebar-width); background: var(--bg-secondary); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 1060; transition: transform 0.3s ease, background-color 0.2s ease; overflow-y: auto; }
        #sidebar .sidebar-brand { padding: 24px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; }
        .sidebar-brand .brand-logo { width: 40px; height: 40px; background: linear-gradient(135deg, var(--accent), var(--accent-dark)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; box-shadow: 0 0 20px var(--accent-glow); flex-shrink: 0; }
        .sidebar-brand .brand-title { font-weight: 700; font-size: 16px; color: var(--text-primary); }
        .sidebar-brand .brand-sub   { font-size: 11px; color: var(--text-muted); letter-spacing: 0.5px; }
        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .nav-label { font-size: 10px; font-weight: 600; color: var(--text-muted); letter-spacing: 1.5px; text-transform: uppercase; padding: 8px 10px 6px; }
        .nav-item { margin-bottom: 4px; }
        .nav-link-custom { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius-sm); color: var(--text-secondary); font-size: 14px; font-weight: 500; text-decoration: none; transition: var(--transition); }
        .nav-link-custom:hover, .nav-link-custom.active { background: rgba(99,102,241,0.14); color: var(--accent-light); text-decoration: none; }
        .nav-link-custom.active { box-shadow: inset 3px 0 0 var(--accent); font-weight: 600; }
        .nav-link-custom .nav-icon { width: 20px; text-align: center; font-size: 14px; color: var(--text-muted); transition: var(--transition); }
        .nav-link-custom:hover .nav-icon, .nav-link-custom.active .nav-icon { color: var(--accent); }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: var(--radius-sm); background: rgba(255,255,255,0.04); border: 1px solid transparent; }
        [data-theme="light"] .sidebar-user { background: #f8fafc; border-color: var(--border); }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: white; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-info .user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-info .user-role { font-size: 11px; color: var(--text-muted); text-transform: capitalize; }
        #sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1059; }
        #sidebar-overlay.show { display: block; }

        /* CONTENT WRAPPER */
        #content-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; min-width: 0; }

        /* TOPBAR */
        #topbar { height: 64px; background: var(--bg-secondary); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 900; backdrop-filter: blur(10px); transition: background-color 0.2s ease; }
        .topbar-left  { display: flex; align-items: center; gap: 16px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .page-title   { font-size: 16px; font-weight: 600; color: var(--text-primary); }
        #sidebar-toggle { background: none; border: none; color: var(--text-secondary); font-size: 18px; cursor: pointer; padding: 6px; border-radius: var(--radius-sm); transition: var(--transition); display: none; }
        #sidebar-toggle:hover { background: rgba(99,102,241,0.1); color: var(--text-primary); }
        .btn-back { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-secondary); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 13px; text-decoration: none; transition: var(--transition); }
        .btn-back:hover { background: rgba(99,102,241,0.12); color: var(--text-primary); text-decoration: none; }
        [data-theme="light"] .btn-back { background: #f8fafc; }
        [data-theme="light"] .btn-back:hover { background: #e2e8f0; }

        .btn-theme-toggle {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(99,102,241,0.10); border: 1px solid var(--border-accent);
            color: var(--accent-light); padding: 7px 13px; border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 600; cursor: pointer; transition: var(--transition);
        }
        .btn-theme-toggle:hover { background: var(--accent); color: #fff; box-shadow: 0 2px 12px var(--accent-glow); }

        .btn-logout { display: flex; align-items: center; gap: 8px; background: rgba(239,68,68,0.10); border: 1px solid rgba(239,68,68,0.20); color: var(--danger); padding: 7px 14px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 500; text-decoration: none; transition: var(--transition); }
        .btn-logout:hover { background: rgba(239,68,68,0.20); color: #ef4444; text-decoration: none; }
        #main-content { padding: 24px; flex: 1; }

        /* GRID */
        .grid   { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        /* STAT CARDS */
        .stat-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; display: flex; align-items: center; gap: 16px; transition: var(--transition); box-shadow: var(--shadow); }
        .stat-card:hover { transform: translateY(-2px); border-color: var(--border-accent); }
        .stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .stat-icon.accent  { background: rgba(99,102,241,0.15); color: var(--accent); }
        .stat-icon.success { background: rgba(16,185,129,0.15);  color: var(--success); }
        .stat-icon.warning { background: rgba(245,158,11,0.15);  color: var(--warning); }
        .stat-icon.danger  { background: rgba(239,68,68,0.15);   color: var(--danger); }
        .stat-icon.info    { background: rgba(59,130,246,0.15);  color: var(--info); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1; }
        .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* CARDS */
        .card-dark { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow); }
        .card-header-dark { padding: 16px 20px; background: var(--bg-secondary); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        [data-theme="light"] .card-header-dark { background: #f8fafc; }
        .card-header-dark h5, .card-header-dark h6 { margin: 0; font-weight: 600; font-size: 15px; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
        .card-header-dark h5 .header-icon, .card-header-dark h6 .header-icon { color: var(--accent); font-size: 16px; }
        .card-body-dark { padding: 20px; }
        .section-title { font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: var(--accent-light); margin-bottom: 14px; }
        hr.divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

        /* FORMS */
        .form-group { margin-bottom: 16px; }
        .form-label-dark { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.3px; }
        .form-control-dark { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid var(--border); color: var(--text-primary); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 14px; font-family: 'Inter', sans-serif; transition: var(--transition); outline: none; -webkit-appearance: none; }
        [data-theme="light"] .form-control-dark { background: #f8fafc; border-color: #cbd5e1; color: var(--text-primary); }
        .form-control-dark:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); background: rgba(255,255,255,0.06); }
        [data-theme="light"] .form-control-dark:focus { background: #ffffff; border-color: var(--accent); }
        .form-control-dark::placeholder { color: var(--text-muted); }
        .form-control-dark option { background: var(--bg-card); color: var(--text-primary); }

        /* BUTTONS */
        .btn-primary-dark { background: linear-gradient(135deg, var(--accent), var(--accent-dark)); border: none; color: white; padding: 9px 18px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: var(--transition); cursor: pointer; white-space: nowrap; }
        .btn-primary-dark:hover { background: linear-gradient(135deg, var(--accent-light), var(--accent)); box-shadow: 0 4px 16px var(--accent-glow); color: white; text-decoration: none; transform: translateY(-1px); }
        .btn-warning-dark { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3); color: #f59e0b; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: var(--transition); cursor: pointer; }
        .btn-warning-dark:hover { background: rgba(245,158,11,0.25); color: #fbbf24; text-decoration: none; transform: translateY(-1px); }
        [data-theme="light"] .btn-warning-dark { color: #b45309; border-color: rgba(217,119,6,0.35); }
        [data-theme="light"] .btn-warning-dark:hover { color: #92400e; }
        .btn-danger-dark { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: var(--transition); cursor: pointer; }
        .btn-danger-dark:hover { background: rgba(239,68,68,0.25); color: #f87171; text-decoration: none; transform: translateY(-1px); }
        [data-theme="light"] .btn-danger-dark { color: #dc2626; }
        .btn-success-dark { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: var(--transition); cursor: pointer; }
        .btn-success-dark:hover { background: rgba(16,185,129,0.25); color: #34d399; text-decoration: none; transform: translateY(-1px); }
        [data-theme="light"] .btn-success-dark { color: #047857; }
        .btn-info-dark { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: #3b82f6; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: var(--transition); cursor: pointer; }
        .btn-info-dark:hover { background: rgba(59,130,246,0.25); color: #60a5fa; text-decoration: none; transform: translateY(-1px); }
        [data-theme="light"] .btn-info-dark { color: #1d4ed8; }
        .btn-sm-custom { padding: 5px 10px !important; font-size: 11px !important; }

        /* TABLE */
        .table-dark-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-dark-custom thead tr { background: var(--bg-secondary); border-bottom: 1px solid var(--border); }
        [data-theme="light"] .table-dark-custom thead tr { background: #f8fafc; }
        .table-dark-custom thead th { padding: 12px 16px; font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); white-space: nowrap; }
        .table-dark-custom tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        .table-dark-custom tbody tr:hover { background: rgba(99,102,241,0.04); }
        .table-dark-custom tbody td { padding: 12px 16px; color: var(--text-secondary); vertical-align: middle; }
        .employee-link { color: var(--text-primary); font-weight: 500; text-decoration: none; }
        .employee-link:hover { color: var(--accent-light); }

        /* BADGES */
        .badge-custom { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .badge-info      { background: rgba(59,130,246,0.15);  color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }
        .badge-warning   { background: rgba(245,158,11,0.15);  color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .badge-success   { background: rgba(16,185,129,0.15);  color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .badge-danger    { background: rgba(239,68,68,0.15);   color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge-secondary { background: rgba(148,163,184,0.10); color: #94a3b8; border: 1px solid rgba(148,163,184,0.2); }
        .badge-primary   { background: rgba(99,102,241,0.15);  color: #818cf8; border: 1px solid rgba(99,102,241,0.3); }

        [data-theme="light"] .badge-info      { background: rgba(37,99,235,0.12);  color: #1d4ed8; border-color: rgba(37,99,235,0.28); }
        [data-theme="light"] .badge-warning   { background: rgba(217,119,6,0.12);  color: #b45309; border-color: rgba(217,119,6,0.28); }
        [data-theme="light"] .badge-success   { background: rgba(5,150,105,0.12);  color: #047857; border-color: rgba(5,150,105,0.28); }
        [data-theme="light"] .badge-danger    { background: rgba(220,38,38,0.12);  color: #b91c1c; border-color: rgba(220,38,38,0.28); }
        [data-theme="light"] .badge-secondary { background: #f1f5f9;               color: #475569; border-color: #cbd5e1; }
        [data-theme="light"] .badge-primary   { background: rgba(79,70,229,0.12);  color: #4338ca; border-color: rgba(79,70,229,0.28); }

        /* ALERTS */
        .alert-dark { padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .alert-success-dark { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
        .alert-danger-dark  { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }
        .alert-warning-dark { background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3); color: #fbbf24; }
        .alert-info-dark    { background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); color: #60a5fa; }
        [data-theme="light"] .alert-success-dark { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
        [data-theme="light"] .alert-danger-dark  { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }
        [data-theme="light"] .alert-warning-dark { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
        [data-theme="light"] .alert-info-dark    { background: #eff6ff; border-color: #93c5fd; color: #1e40af; }

        /* INFO ROWS */
        .info-row { display: flex; align-items: flex-start; margin-bottom: 12px; }
        .info-row .info-label { flex: 0 0 180px; font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .info-row .info-value { flex: 1; font-size: 13px; color: var(--text-primary); }

        /* DATATABLES */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select { background: rgba(255,255,255,0.05) !important; border: 1px solid var(--border) !important; color: var(--text-primary) !important; border-radius: 6px !important; padding: 5px 10px !important; outline: none !important; }
        [data-theme="light"] .dataTables_wrapper .dataTables_filter input,
        [data-theme="light"] .dataTables_wrapper .dataTables_length select { background: #f8fafc !important; border-color: #cbd5e1 !important; color: var(--text-primary) !important; }
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label { color: var(--text-muted) !important; font-size: 13px; }
        .dt-bottom { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; flex-wrap:wrap; gap:8px; }
        .dt-top    { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; flex-wrap:wrap; gap:8px; }
        .dataTables_wrapper .pagination .page-item .page-link { background: var(--bg-secondary) !important; border: 1px solid var(--border) !important; color: var(--text-secondary) !important; border-radius: 6px !important; margin: 0 2px !important; }
        .dataTables_wrapper .pagination .page-item.active .page-link { background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important; border-color: var(--accent) !important; color: #fff !important; }

        /* FULLCALENDAR */
        #calendar { background: var(--bg-card); border-radius: var(--radius); padding: 12px; }
        .fc { --fc-border-color: var(--border); --fc-page-bg-color: transparent; }
        .fc .fc-toolbar-title { color: var(--text-primary) !important; font-size: 16px !important; }
        .fc .fc-col-header-cell-cushion, .fc .fc-daygrid-day-number { color: var(--text-secondary) !important; text-decoration: none !important; }
        .fc .fc-button { background: rgba(99,102,241,0.15) !important; border: 1px solid var(--border-accent) !important; color: var(--accent-light) !important; }
        .fc .fc-button:hover { background: var(--accent) !important; color: white !important; }
        .fc .fc-daygrid-day.fc-day-today { background: rgba(99,102,241,0.08) !important; }

        /* MODAL (ADAPTIVE DARK / LIGHT) */
        .modal-backdrop { z-index: 1070 !important; opacity: 0.65 !important; }
        .modal          { z-index: 1080 !important; }
        .modal-content  { background: var(--bg-card) !important; border: 1px solid var(--border-accent) !important; border-radius: 12px !important; color: var(--text-primary) !important; box-shadow: var(--shadow-lg) !important; }
        .modal-header   { background: var(--bg-card-hover) !important; border-bottom: 1px solid var(--border) !important; border-radius: 12px 12px 0 0 !important; }
        .modal-header .modal-title { color: var(--text-primary) !important; font-weight: 600 !important; }
        .modal-header .close { color: var(--text-secondary) !important; opacity: 1 !important; text-shadow: none !important; }
        .modal-body   { background: var(--bg-card) !important; color: var(--text-primary) !important; }
        .modal-footer { background: var(--bg-secondary) !important; border-top: 1px solid var(--border) !important; border-radius: 0 0 12px 12px !important; }
        .modal .form-control { background: rgba(255,255,255,0.06) !important; border: 1px solid var(--border) !important; color: var(--text-primary) !important; border-radius: 8px !important; }
        [data-theme="light"] .modal .form-control { background: #f8fafc !important; border-color: #cbd5e1 !important; color: var(--text-primary) !important; }
        .modal .form-control option { background-color: var(--bg-card) !important; color: var(--text-primary) !important; }
        .modal label { color: var(--text-secondary) !important; font-size: 13px !important; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.sidebar-open { transform: translateX(0); }
            #sidebar-toggle { display: flex !important; }
            #content-wrapper { margin-left: 0; }
            .grid-4, .grid-3 { grid-template-columns: repeat(2, 1fr); }
            .grid-2 { grid-template-columns: 1fr; }
            #main-content { padding: 16px; }
        }
        @media (max-width: 480px) {
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php $__role = session()->get('role'); $__nip = session()->get('nip'); $__cur = service('uri')->getSegments(); ?>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<div id="wrapper">
    <!-- SIDEBAR -->
    <nav id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo"><i class="fas fa-users-cog"></i></div>
            <div class="brand-text">
                <div class="brand-title">SIMPEG</div>
                <div class="brand-sub">BGTK</div>
            </div>
        </div>
        <div class="sidebar-nav">
            <div class="nav-label">Navigasi</div>
            <?php if ($__role === 'super_admin'): ?>
                <div class="nav-item">
                    <a href="<?= base_url('dashboard/super-admin') ?>" class="nav-link-custom <?= (implode('/', $__cur) === 'dashboard/super-admin') ? 'active' : '' ?>">
                        <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('pegawai/tambah') ?>" class="nav-link-custom <?= (implode('/', $__cur) === 'pegawai/tambah') ? 'active' : '' ?>">
                        <span class="nav-icon"><i class="fas fa-user-plus"></i></span> Tambah Pegawai
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('user/tambah') ?>" class="nav-link-custom <?= (implode('/', $__cur) === 'user/tambah') ? 'active' : '' ?>">
                        <span class="nav-icon"><i class="fas fa-user-shield"></i></span> Tambah User
                    </a>
                </div>
            <?php elseif ($__role === 'admin'): ?>
                <div class="nav-item">
                    <a href="<?= base_url('dashboard/admin') ?>" class="nav-link-custom <?= (implode('/', $__cur) === 'dashboard/admin') ? 'active' : '' ?>">
                        <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                    </a>
                </div>
            <?php elseif ($__role === 'kepala'): ?>
                <div class="nav-item">
                    <a href="<?= base_url('dashboard/kepala') ?>" class="nav-link-custom <?= (implode('/', $__cur) === 'dashboard/kepala') ? 'active' : '' ?>">
                        <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                    </a>
                </div>
            <?php elseif ($__role === 'pegawai'): ?>
                <div class="nav-item">
                    <a href="<?= base_url('pegawai/profil/' . $__nip) ?>" class="nav-link-custom">
                        <span class="nav-icon"><i class="fas fa-id-card"></i></span> Profil Saya
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('diklat/' . $__nip) ?>" class="nav-link-custom">
                        <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span> Diklat Saya
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('penghargaan/' . $__nip) ?>" class="nav-link-custom">
                        <span class="nav-icon"><i class="fas fa-award"></i></span> Penghargaan
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('abk/' . $__nip) ?>" class="nav-link-custom">
                        <span class="nav-icon"><i class="fas fa-briefcase"></i></span> ABK Saya
                    </a>
                </div>
                <div class="nav-item">
                    <a href="<?= base_url('tna/' . $__nip) ?>" class="nav-link-custom">
                        <span class="nav-icon"><i class="fas fa-brain"></i></span> TNA Saya
                    </a>
                </div>
            <?php endif; ?>

            <!-- MENU MONITORING 2026 -->
            <div class="nav-label" style="margin-top: 15px;">Monitoring 2026</div>
            <div class="nav-item">
                <a href="<?= base_url('kegiatan') ?>" class="nav-link-custom <?= (in_array('kegiatan', $__cur)) ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-clipboard-list"></i></span> Kode dan SDM
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('jadwal-penugasan') ?>" class="nav-link-custom <?= (in_array('jadwal-penugasan', $__cur)) ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-calendar-check"></i></span> Jadwal Penugasan
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('rekap-penugasan') ?>" class="nav-link-custom <?= (in_array('rekap-penugasan', $__cur)) ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-chart-bar"></i></span> Rekap Penugasan
                </a>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= strtoupper(mb_substr(session()->get('nama') ?? 'U', 0, 1)) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= esc(session()->get('nama') ?? '') ?></div>
                    <div class="user-role"><?= match($__role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', 'kepala' => 'Pimpinan', default => 'Pegawai' } ?></div>
                </div>
                <?php if ($__nip): ?>
                <a href="<?= base_url('user/edit-password/' . urlencode($__nip)) ?>" title="Ubah Password" style="color:var(--text-muted);font-size:13px;padding:4px 6px;border-radius:6px;">
                    <i class="fas fa-key"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- CONTENT WRAPPER -->
    <div id="content-wrapper">
        <!-- TOPBAR -->
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <?php if (isset($back_url)): ?>
                <a href="<?= base_url($back_url) ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <?php endif; ?>
                <span class="page-title">
                    <i class="<?= $title_icon ?? 'fas fa-circle' ?>" style="color:var(--accent);margin-right:8px;"></i>
                    <?= $page_title ?? 'SIMPEG' ?>
                </span>
            </div>
            <div class="topbar-right">
                <button type="button" id="theme-toggle-btn" class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti Mode Terang / Gelap">
                    <i class="fas fa-sun" id="theme-toggle-icon"></i>
                    <span id="theme-toggle-text">Light Mode</span>
                </button>
                <a href="<?= base_url('logout') ?>" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div id="main-content">
            <?php if (session()->getFlashdata('success')): ?>
            <div class="alert-dark alert-success-dark"><i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?></div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </div>
    </div>
</div>
<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
function syncThemeButton() {
    var cur = document.documentElement.getAttribute('data-theme') || 'dark';
    var ico = document.getElementById('theme-toggle-icon');
    var txt = document.getElementById('theme-toggle-text');
    if (ico) ico.className = cur === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    if (txt) txt.textContent = cur === 'light' ? 'Dark Mode' : 'Light Mode';
}
function toggleTheme() {
    var cur  = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = cur === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('simpeg_theme', next); } catch (e) {}
    syncThemeButton();
}
syncThemeButton();

function openSidebar()  { document.getElementById('sidebar')?.classList.add('sidebar-open'); document.getElementById('sidebar-overlay')?.classList.add('show'); }
function closeSidebar() { document.getElementById('sidebar')?.classList.remove('sidebar-open'); document.getElementById('sidebar-overlay')?.classList.remove('show'); }
document.getElementById('sidebar-toggle')?.addEventListener('click', openSidebar);

$(document).ready(function () {
    // DataTables auto-init
    $('table.auto-datatable').each(function () {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable({
                language: { url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Indonesian.json' },
                columnDefs: [{ targets: -1, orderable: false }],
                dom: '<"dt-top"lf>rt<"dt-bottom"ip>',
                pageLength: 10,
            });
        }
    });

    // Confirm delete
    $(document).on('click', '.confirm-delete', function (e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) e.preventDefault();
    });

    // Status field toggle
    var statusSelect = document.getElementById('statusSelect');
    var extraFields  = document.getElementById('extraFields');
    if (statusSelect && extraFields) {
        function toggleStatus() { extraFields.style.display = (['DL','Cuti'].includes(statusSelect.value)) ? 'block' : 'none'; }
        statusSelect.addEventListener('change', toggleStatus);
        toggleStatus();
    }

    // FullCalendar (terintegrasi dengan Kode dan SDM 2026)
    var calEl = document.getElementById('calendar');
    if (calEl) {
        var calEvents = typeof calendarEvents !== 'undefined' ? calendarEvents : [];
        window.simpegCalendar = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            height: 460,
            events: calEvents,
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listYear' },
            buttonText: { today: 'Hari Ini', month: 'Bulan', list: 'Daftar 2026' },
            eventDidMount: function (info) {
                var ep = info.event.extendedProps || {};
                if (ep.description) {
                    info.el.title = ep.description;
                }
                if (ep.source === 'kode_sdm') {
                    info.el.style.fontWeight = '600';
                    info.el.style.fontSize = '11px';
                    info.el.style.cursor = 'pointer';
                    var mainEl = info.el.querySelector('.fc-event-main');
                    if (mainEl) mainEl.style.color = '#0f172a';
                }
            },
            eventClick: function (info) {
                if (typeof window.onSimpegCalendarEventClick === 'function') {
                    window.onSimpegCalendarEventClick(info.event);
                }
            }
        });
        window.simpegCalendar.render();
    }
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
