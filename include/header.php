<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Informasi Manajemen Pegawai BGTK">
    <title>SIMPEG &mdash; BGTK</title>

    <!-- Google Fonts -->
    <script>
    (function () {
        try {
            var saved = localStorage.getItem('simpeg_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        } catch (e) {}
    })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 4 (WAJIB ada agar modal Bootstrap berfungsi) -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap4 -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS
        ============================================================ */
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

        .btn-theme-toggle {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(99,102,241,0.10); border: 1px solid var(--border-accent);
            color: var(--accent-light); padding: 7px 13px; border-radius: var(--radius-sm);
            font-size: 12.5px; font-weight: 600; cursor: pointer; transition: var(--transition);
        }
        .btn-theme-toggle:hover { background: var(--accent); color: #fff; box-shadow: 0 2px 12px var(--accent-glow); }

        [data-theme="light"] .sidebar-user { background: #f8fafc; border: 1px solid var(--border); }
        [data-theme="light"] .btn-back { background: #f8fafc; }
        [data-theme="light"] .btn-back:hover { background: #e2e8f0; }
        [data-theme="light"] .card-header-dark { background: #f8fafc; }
        [data-theme="light"] .form-control-dark { background: #f8fafc; border-color: #cbd5e1; color: var(--text-primary); }
        [data-theme="light"] .form-control-dark:focus { background: #ffffff; border-color: var(--accent); }
        [data-theme="light"] .table-dark-custom thead tr { background: #f8fafc; }
        [data-theme="light"] .table-dark-custom tbody tr:hover { background: rgba(79,70,229,0.04); }
        [data-theme="light"] .badge-info      { background: rgba(37,99,235,0.12);  color: #1d4ed8; border-color: rgba(37,99,235,0.28); }
        [data-theme="light"] .badge-warning   { background: rgba(217,119,6,0.12);  color: #b45309; border-color: rgba(217,119,6,0.28); }
        [data-theme="light"] .badge-success   { background: rgba(5,150,105,0.12);  color: #047857; border-color: rgba(5,150,105,0.28); }
        [data-theme="light"] .badge-danger    { background: rgba(220,38,38,0.12);  color: #b91c1c; border-color: rgba(220,38,38,0.28); }
        [data-theme="light"] .badge-secondary { background: #f1f5f9;               color: #475569; border-color: #cbd5e1; }
        [data-theme="light"] .badge-primary   { background: rgba(79,70,229,0.12);  color: #4338ca; border-color: rgba(79,70,229,0.28); }
        [data-theme="light"] .dataTables_wrapper .dataTables_filter input,
        [data-theme="light"] .dataTables_wrapper .dataTables_length select { background: #f8fafc !important; border-color: #cbd5e1 !important; color: var(--text-primary) !important; }
        [data-theme="light"] .modal-content { background: var(--bg-card) !important; color: var(--text-primary) !important; border-color: var(--border-accent) !important; box-shadow: var(--shadow-lg) !important; }
        [data-theme="light"] .modal-header { background: #f8fafc !important; border-bottom-color: var(--border) !important; }
        [data-theme="light"] .modal-header .modal-title, [data-theme="light"] .modal-header .close { color: var(--text-primary) !important; }
        [data-theme="light"] .modal-body { background: var(--bg-card) !important; color: var(--text-primary) !important; }
        [data-theme="light"] .modal-footer { background: #f8fafc !important; border-top-color: var(--border) !important; }
        [data-theme="light"] .modal .form-control, [data-theme="light"] .modal select.form-control, [data-theme="light"] .modal textarea.form-control { background: #f8fafc !important; border-color: #cbd5e1 !important; color: var(--text-primary) !important; }
        [data-theme="light"] .modal select.form-control option { background: #ffffff !important; color: var(--text-primary) !important; }
        [data-theme="light"] .modal label, [data-theme="light"] .modal .table th, [data-theme="light"] .modal .table td { color: var(--text-secondary) !important; }

        /* ============================================================
           BASE RESET
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif !important;
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            min-height: 100vh;
            overflow-x: hidden;
            margin: 0; padding: 0;
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 3px; }

        a { color: var(--accent-light); }
        a:hover { color: var(--accent); text-decoration: none; }

        /* ============================================================
           LAYOUT
           Z-INDEX chain: backdrop(1040) < modal(1050) < sidebar(1060)
           so modal always appears ABOVE page but BELOW sidebar on mobile
        ============================================================ */
        #wrapper { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 1060;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        #sidebar .sidebar-brand {
            padding: 24px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand .brand-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; color: white;
            box-shadow: 0 0 20px var(--accent-glow); flex-shrink: 0;
        }
        .sidebar-brand .brand-text { line-height: 1.2; }
        .sidebar-brand .brand-title { font-weight: 700; font-size: 16px; color: var(--text-primary); }
        .sidebar-brand .brand-sub   { font-size: 11px; color: var(--text-muted); letter-spacing: 0.5px; }

        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .nav-label {
            font-size: 10px; font-weight: 600; color: var(--text-muted);
            letter-spacing: 1.5px; text-transform: uppercase; padding: 8px 10px 6px;
        }
        .nav-item { margin-bottom: 4px; }
        .nav-link-custom {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: var(--radius-sm);
            color: var(--text-secondary); font-size: 14px; font-weight: 500;
            text-decoration: none; transition: var(--transition);
        }
        .nav-link-custom:hover, .nav-link-custom.active {
            background: rgba(99,102,241,0.15); color: var(--accent-light); text-decoration: none;
        }
        .nav-link-custom.active { box-shadow: inset 3px 0 0 var(--accent); }
        .nav-link-custom .nav-icon {
            width: 20px; text-align: center; font-size: 14px;
            color: var(--text-muted); transition: var(--transition);
        }
        .nav-link-custom:hover .nav-icon,
        .nav-link-custom.active .nav-icon { color: var(--accent); }

        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px;
            border-radius: var(--radius-sm); background: rgba(255,255,255,0.04);
        }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: white; flex-shrink: 0;
        }
        .user-info .user-name { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .user-info .user-role { font-size: 11px; color: var(--text-muted); text-transform: capitalize; }

        /* Mobile sidebar overlay */
        #sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 1059;
        }
        #sidebar-overlay.show { display: block; }

        /* CONTENT WRAPPER */
        #content-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1; display: flex; flex-direction: column; min-width: 0;
        }

        /* TOPBAR */
        #topbar {
            height: 64px; background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; position: sticky; top: 0; z-index: 900;
            backdrop-filter: blur(10px);
        }
        .topbar-left  { display: flex; align-items: center; gap: 16px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .page-title   { font-size: 16px; font-weight: 600; color: var(--text-primary); }

        #sidebar-toggle {
            background: none; border: none; color: var(--text-secondary);
            font-size: 18px; cursor: pointer; padding: 6px;
            border-radius: var(--radius-sm); transition: var(--transition);
            display: none;
        }
        #sidebar-toggle:hover { background: rgba(255,255,255,0.05); color: var(--text-primary); }

        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: var(--text-secondary); padding: 6px 12px;
            border-radius: var(--radius-sm); font-size: 13px;
            text-decoration: none; transition: var(--transition);
        }
        .btn-back:hover { background: rgba(255,255,255,0.10); color: var(--text-primary); text-decoration: none; }

        .btn-logout {
            display: flex; align-items: center; gap: 8px;
            background: rgba(239,68,68,0.10); border: 1px solid rgba(239,68,68,0.20);
            color: #ef4444; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 500; text-decoration: none; transition: var(--transition);
        }
        .btn-logout:hover { background: rgba(239,68,68,0.20); color: #f87171; text-decoration: none; }

        #main-content { padding: 24px; flex: 1; }

        /* ============================================================
           GRID
        ============================================================ */
        .grid   { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        /* ============================================================
           STAT CARDS
        ============================================================ */
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            display: flex; align-items: center; gap: 16px;
            transition: var(--transition);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--border-accent); }
        .stat-icon {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .stat-icon.accent  { background: rgba(99,102,241,0.15); color: var(--accent); }
        .stat-icon.success { background: rgba(16,185,129,0.15);  color: var(--success); }
        .stat-icon.warning { background: rgba(245,158,11,0.15);  color: var(--warning); }
        .stat-icon.danger  { background: rgba(239,68,68,0.15);   color: var(--danger); }
        .stat-icon.info    { background: rgba(59,130,246,0.15);  color: var(--info); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1; }
        .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* ============================================================
           CARDS
        ============================================================ */
        .card-dark {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden; margin-bottom: 20px;
        }
        .card-header-dark {
            padding: 16px 20px; background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
        }
        .card-header-dark h5, .card-header-dark h6 {
            margin: 0; font-weight: 600; font-size: 15px; color: var(--text-primary);
            display: flex; align-items: center; gap: 8px;
        }
        .card-header-dark h5 .header-icon,
        .card-header-dark h6 .header-icon { color: var(--accent); font-size: 16px; }
        .card-body-dark { padding: 20px; }
        .section-title {
            font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; color: var(--accent-light); margin-bottom: 14px;
        }
        hr.divider { border: none; border-top: 1px solid var(--border); margin: 20px 0; }

        /* ============================================================
           FORM CONTROLS (dark)
        ============================================================ */
        .form-group { margin-bottom: 16px; }
        .form-label-dark {
            display: block; font-size: 12px; font-weight: 600;
            color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.3px;
        }
        .form-control-dark {
            width: 100%; background: rgba(255,255,255,0.04);
            border: 1px solid var(--border); color: var(--text-primary);
            border-radius: var(--radius-sm); padding: 10px 14px; font-size: 14px;
            font-family: 'Inter', sans-serif; transition: var(--transition);
            outline: none; -webkit-appearance: none;
        }
        .form-control-dark:focus {
            border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow);
            background: rgba(255,255,255,0.06);
        }
        .form-control-dark::placeholder { color: var(--text-muted); }
        .form-control-dark[readonly]    { opacity: 0.6; cursor: not-allowed; }
        .form-control-dark option       { background: var(--bg-card); color: var(--text-primary); }

        /* ============================================================
           BUTTONS
        ============================================================ */
        .btn-primary-dark {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border: none; color: white; padding: 9px 18px;
            border-radius: var(--radius-sm); font-size: 13px; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            transition: var(--transition); cursor: pointer; white-space: nowrap;
        }
        .btn-primary-dark:hover {
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            box-shadow: 0 4px 16px var(--accent-glow); color: white; text-decoration: none; transform: translateY(-1px);
        }
        .btn-warning-dark {
            background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3);
            color: #f59e0b; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; transition: var(--transition); cursor: pointer;
        }
        .btn-warning-dark:hover { background: rgba(245,158,11,0.25); color: #fbbf24; text-decoration: none; transform: translateY(-1px); }
        .btn-danger-dark {
            background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; transition: var(--transition); cursor: pointer;
        }
        .btn-danger-dark:hover { background: rgba(239,68,68,0.25); color: #f87171; text-decoration: none; transform: translateY(-1px); }
        .btn-success-dark {
            background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3);
            color: #10b981; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; transition: var(--transition); cursor: pointer;
        }
        .btn-success-dark:hover { background: rgba(16,185,129,0.25); color: #34d399; text-decoration: none; transform: translateY(-1px); }
        .btn-info-dark {
            background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
            color: #3b82f6; padding: 7px 14px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;
            text-decoration: none; transition: var(--transition); cursor: pointer;
        }
        .btn-info-dark:hover { background: rgba(59,130,246,0.25); color: #60a5fa; text-decoration: none; transform: translateY(-1px); }
        .btn-sm-custom { padding: 5px 10px !important; font-size: 11px !important; }

        /* Bootstrap btn overrides */
        .btn { border-radius: var(--radius-sm) !important; font-family: 'Inter', sans-serif !important; font-size: 13px !important; }
        .btn.btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important; border: none !important; color: white !important; }
        .btn.btn-success { background: var(--success) !important; border: none !important; color: white !important; }
        .btn.btn-danger  { background: var(--danger)  !important; border: none !important; color: white !important; }
        .btn.btn-warning { background: var(--warning) !important; border: none !important; color: #0f1117 !important; }
        .btn.btn-info    { background: var(--info)    !important; border: none !important; color: white !important; }
        .btn.btn-secondary { background: rgba(255,255,255,0.08) !important; border: 1px solid var(--border) !important; color: var(--text-secondary) !important; }
        .btn.btn-secondary:hover { background: rgba(255,255,255,0.15) !important; color: var(--text-primary) !important; }

        /* ============================================================
           TABLE (dark custom)
        ============================================================ */
        .table-dark-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-dark-custom thead tr { background: var(--bg-secondary); border-bottom: 1px solid var(--border); }
        .table-dark-custom thead th {
            padding: 12px 16px; font-size: 11px; font-weight: 600;
            letter-spacing: 1px; text-transform: uppercase; color: var(--text-muted); white-space: nowrap;
        }
        .table-dark-custom tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        .table-dark-custom tbody tr:hover { background: rgba(255,255,255,0.02); }
        .table-dark-custom tbody td { padding: 12px 16px; color: var(--text-secondary); vertical-align: middle; }
        .employee-link { color: var(--text-primary); font-weight: 500; text-decoration: none; }
        .employee-link:hover { color: var(--accent-light); }

        /* ============================================================
           BADGES
        ============================================================ */
        .badge-custom {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .badge-info      { background: rgba(59,130,246,0.15);   color: #60a5fa;  border: 1px solid rgba(59,130,246,0.3); }
        .badge-warning   { background: rgba(245,158,11,0.15);   color: #fbbf24;  border: 1px solid rgba(245,158,11,0.3); }
        .badge-success   { background: rgba(16,185,129,0.15);   color: #34d399;  border: 1px solid rgba(16,185,129,0.3); }
        .badge-danger    { background: rgba(239,68,68,0.15);    color: #f87171;  border: 1px solid rgba(239,68,68,0.3); }
        .badge-secondary { background: rgba(148,163,184,0.10);  color: #94a3b8;  border: 1px solid rgba(148,163,184,0.2); }
        .badge-primary   { background: rgba(99,102,241,0.15);   color: #818cf8;  border: 1px solid rgba(99,102,241,0.3); }

        /* ============================================================
           DATATABLES (dark overrides — Bootstrap4 variant)
        ============================================================ */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background: rgba(255,255,255,0.05) !important; border: 1px solid var(--border) !important;
            color: var(--text-primary) !important; border-radius: 6px !important;
            padding: 5px 10px !important; outline: none !important;
        }
        .dataTables_wrapper .dataTables_length select option {
            background-color: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--accent) !important; box-shadow: 0 0 0 3px var(--accent-glow) !important;
        }
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label { color: var(--text-muted) !important; font-size: 13px; }
        .dt-bottom { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; flex-wrap:wrap; gap:8px; }
        .dt-top    { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; flex-wrap:wrap; gap:8px; }
        .dataTables_wrapper .dataTables_paginate { padding: 4px 0 !important; }
        .dataTables_wrapper .pagination .page-item .page-link {
            background: rgba(255,255,255,0.05) !important; border: 1px solid var(--border) !important;
            color: var(--text-secondary) !important; border-radius: 6px !important;
            margin: 0 2px !important; font-size: 13px !important; padding: 5px 11px !important;
        }
        .dataTables_wrapper .pagination .page-item .page-link:hover {
            background: rgba(99,102,241,0.2) !important; border-color: var(--accent) !important; color: var(--text-primary) !important;
        }
        .dataTables_wrapper .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark)) !important;
            border-color: var(--accent) !important; color: #fff !important;
            font-weight: 700 !important; box-shadow: 0 2px 8px var(--accent-glow) !important;
        }
        .dataTables_wrapper .pagination .page-item.disabled .page-link {
            background: rgba(255,255,255,0.02) !important; border-color: var(--border) !important;
            color: var(--text-muted) !important; opacity: 0.5 !important;
        }
        .dataTables_wrapper .dataTables_info { padding: 8px 0 !important; color: var(--text-muted) !important; font-size: 13px !important; }

        /* ============================================================
           BOOTSTRAP MODAL — DARK THEME OVERRIDE
           z-index: backdrop 1040 < modal 1050 < sidebar 1060
        ============================================================ */
        .modal-backdrop { z-index: 1070 !important; opacity: 0.7 !important; background-color: #000 !important; }
        .modal          { z-index: 1080 !important; }

        .modal-content {
            background: #1e2130 !important;
            border: 1px solid rgba(99,102,241,0.2) !important;
            border-radius: 12px !important;
            color: #f1f5f9 !important;
            box-shadow: 0 25px 80px rgba(0,0,0,0.8) !important;
        }
        .modal-header {
            background: #252840 !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 20px !important;
        }
        .modal-header .modal-title { color: #f1f5f9 !important; font-weight: 600 !important; font-size: 15px !important; }
        .modal-header .close {
            color: #94a3b8 !important; opacity: 1 !important;
            text-shadow: none !important; font-size: 22px !important;
        }
        .modal-header .close:hover { color: #f1f5f9 !important; }

        /* Keep colored header backgrounds but ensure text is readable */
        .modal-header.bg-primary { background: rgba(99,102,241,0.3) !important; }
        .modal-header.bg-warning { background: rgba(245,158,11,0.25) !important; }
        .modal-header.bg-info    { background: rgba(59,130,246,0.25) !important; }
        .modal-header.bg-success { background: rgba(16,185,129,0.25) !important; }
        .modal-header.bg-danger  { background: rgba(239,68,68,0.25)  !important; }
        .modal-header.bg-primary .modal-title,
        .modal-header.bg-warning .modal-title,
        .modal-header.bg-info    .modal-title,
        .modal-header.bg-success .modal-title,
        .modal-header.bg-danger  .modal-title { color: #f1f5f9 !important; }
        .modal-header.bg-primary .close,
        .modal-header.bg-warning .close,
        .modal-header.bg-info    .close,
        .modal-header.bg-success .close,
        .modal-header.bg-danger  .close { color: #f1f5f9 !important; }

        .modal-body {
            background: #1e2130 !important;
            color: #f1f5f9 !important;
            padding: 20px !important;
        }
        .modal-footer {
            background: #1a1d27 !important;
            border-top: 1px solid rgba(255,255,255,0.08) !important;
            border-radius: 0 0 12px 12px !important;
            padding: 12px 20px !important;
        }

        /* Form controls INSIDE modal */
        .modal .form-control,
        .modal select.form-control,
        .modal textarea.form-control {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            color: #f1f5f9 !important;
            border-radius: 8px !important;
        }
        .modal select.form-control option {
            background-color: #1e2130 !important; /* Warna gelap kartu */
            color: #f1f5f9 !important; /* Warna teks putih/terang */
        }
        .modal .form-control:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.25) !important;
            background: rgba(255,255,255,0.09) !important;
        }
        .modal .form-control::placeholder { color: #64748b !important; }
        .modal label { color: #94a3b8 !important; font-size: 13px !important; font-weight: 500 !important; }
        .modal h6    { color: #818cf8 !important; border-color: rgba(255,255,255,0.1) !important; }
        .modal .table         { color: #f1f5f9 !important; }
        .modal .table th      { color: #94a3b8 !important; font-size: 13px !important; }
        .modal .table td      { color: #f1f5f9 !important; }
        .modal .table-bordered,
        .modal .table-bordered th,
        .modal .table-bordered td { border-color: rgba(255,255,255,0.1) !important; }
        .modal .form-control-file { color: #94a3b8 !important; }

        /* bg-light sections inside modal */
        .modal .bg-light,
        .modal [style*="background-color: #f8f9fa"],
        .modal [style*="background-color:#f8f9fa"] {
            background: rgba(255,255,255,0.04) !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        /* Override any inline text-dark inside modals */
        .modal .text-dark { color: #f1f5f9 !important; }
        .modal .bg-white  { background: rgba(255,255,255,0.06) !important; }
        .modal .border-right { border-color: rgba(255,255,255,0.08) !important; }

        /* ============================================================
           FULLCALENDAR (dark)
        ============================================================ */
        #calendar { background: var(--bg-card); border-radius: var(--radius); padding: 12px; }
        .fc { --fc-border-color: var(--border); --fc-page-bg-color: transparent; --fc-neutral-bg-color: var(--bg-card); }
        .fc .fc-toolbar-title  { color: var(--text-primary) !important; font-size: 16px !important; }
        .fc .fc-col-header-cell-cushion,
        .fc .fc-daygrid-day-number { color: var(--text-secondary) !important; text-decoration: none !important; }
        .fc .fc-button {
            background: rgba(99,102,241,0.15) !important; border: 1px solid var(--border-accent) !important;
            color: var(--accent-light) !important; font-size: 12px !important;
        }
        .fc .fc-button:hover, .fc .fc-button-active { background: var(--accent) !important; color: white !important; }
        .fc .fc-daygrid-day.fc-day-today { background: rgba(99,102,241,0.08) !important; }

        /* ============================================================
           UTILITY
        ============================================================ */
        .info-grid  { display: grid; grid-template-columns: 180px 1fr; gap: 10px 16px; }
        .info-label { font-size: 12px; font-weight: 600; color: var(--text-muted); }
        .info-value { font-size: 13px; color: var(--text-primary); }

        /* KODE INI UNTUK MELURUSKAN DATA POKOK */
        .info-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .info-row .info-label {
            flex: 0 0 180px; /* Menetapkan lebar baku untuk label agar sejajar */
        }
        .info-row .info-value {
            flex: 1; /* Mengisi sisa ruang kosong di sebelah kanan */
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.sidebar-open { transform: translateX(0); }
            #sidebar-toggle { display: flex !important; }
            #content-wrapper { margin-left: 0; }
            .grid-4, .grid-3 { grid-template-columns: repeat(2, 1fr); }
            .grid-2 { grid-template-columns: 1fr; }
            #main-content { padding: 16px; }
            .info-grid { grid-template-columns: 1fr; }
            .modal-dialog { margin: 8px !important; max-width: calc(100vw - 16px) !important; }
        }
        @media (max-width: 480px) {
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            #topbar { padding: 0 14px; }
            .page-title { font-size: 13px; }
        }
    </style>
</head>
<body>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>