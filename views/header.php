<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers/escape.php';

// Pastikan display_name tersedia (backward-compat jika session lama)
if (!isset($_SESSION['display_name']) && isset($_SESSION['role'])) {
    $_SESSION['display_name'] = ($_SESSION['role'] === 'admin') ? 'Admin' : 'Staff';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory – Kios Sembako Azwa</title>
    <meta name="description" content="Sistem Manajemen Inventori Kios Sembako Azwa">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* --- Brand greens --- */
            --green-700: #15803d;
            --green-600: #16a34a;
            --green-500: #22c55e;
            --green-400: #4ade80;
            --green-100: #dcfce7;
            --green-50:  #f0fdf4;
            --emerald:   #10b981;

            /* --- Neutrals --- */
            --gray-50:  #f9fafb;
            --gray-100: #f3f4f6;
            --gray-150: #eef0f3;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;

            /* --- Semantic --- */
            --danger:  #ef4444;
            --danger-light: #fef2f2;
            --danger-border: #fecaca;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --info:    #3b82f6;
            --info-light: #eff6ff;

            /* --- Layout --- */
            --navbar-height: 64px;
            --content-max: 1320px;

            /* --- Surfaces --- */
            --bg-page:   #f4f6f9;
            --bg-card:   #ffffff;
            --bg-card-hover: #fafafa;
            --border:    #e5e7eb;
            --border-focus: var(--green-500);

            /* --- Text --- */
            --text-primary:   var(--gray-900);
            --text-secondary: var(--gray-600);
            --text-muted:     var(--gray-400);

            /* --- Shadows --- */
            --shadow-xs: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.07), 0 4px 6px -4px rgba(0,0,0,0.04);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─────────────────────────────────────────
           NAVBAR
        ───────────────────────────────────────── */
        .topnav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--navbar-height);
            z-index: 200;
            background: #fff;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-xs);
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 16px;
        }

        .topnav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--green-700), var(--green-500));
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.3px;
            line-height: 1.3;
        }

        .brand-name small {
            display: block;
            font-size: 0.62rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .nav-sep {
            width: 1px;
            height: 26px;
            background: var(--border);
            flex-shrink: 0;
        }

        .topnav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 13px;
            border-radius: 8px;
            font-size: 0.855rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }

        .nav-link:hover {
            background: var(--gray-100);
            color: var(--gray-900);
        }

        .nav-link.active {
            background: var(--green-50);
            color: var(--green-700);
            font-weight: 600;
        }


        .topnav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 9px;
            background: var(--gray-50);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 6px 13px 6px 8px;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--green-700), var(--emerald));
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #fff;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-details { line-height: 1.25; }

        .user-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--gray-900);
        }

        .user-role {
            font-size: 0.68rem;
            color: var(--green-600);
            font-weight: 500;
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 14px;
            background: var(--danger-light);
            border: 1px solid var(--danger-border);
            border-radius: 8px;
            color: var(--danger);
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s;
        }

        .btn-logout:hover {
            background: #fee2e2;
        }

        /* ─────────────────────────────────────────
           PAGE BODY
        ───────────────────────────────────────── */
        .page-body {
            margin-top: var(--navbar-height);
            flex: 1;
            padding: 28px 32px;
            max-width: var(--content-max);
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        /* ─────────────────────────────────────────
           CARDS
        ───────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gray-50);
        }

        .card-body-custom { padding: 20px; }

        /* ─────────────────────────────────────────
           TABLES
        ───────────────────────────────────────── */
        .table-modern {
            width: 100%;
            border-collapse: collapse;
        }

        .table-modern th {
            padding: 10px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: var(--text-muted);
            background: var(--gray-50);
            border-bottom: 1px solid var(--border);
            text-align: left;
            white-space: nowrap;
        }

        .table-modern td {
            padding: 12px 14px;
            font-size: 0.875rem;
            color: var(--text-primary);
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }

        .table-modern tr:last-child td { border-bottom: none; }

        .table-modern tbody tr:hover {
            background: var(--gray-50);
        }

        /* ─────────────────────────────────────────
           FORMS
        ───────────────────────────────────────── */
        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            width: 100%;
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            border-radius: 9px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            padding: 10px 12px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control::placeholder { color: var(--text-muted); }

        .form-control:focus, .form-select:focus {
            border-color: var(--green-500);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.12);
        }

        .form-group { margin-bottom: 20px; }

        /* Sub-page form cards */
        .card .form-group { margin-bottom: 22px; }
        .card .form-label { margin-bottom: 8px; }
        .card .form-control,
        .card .form-select { padding: 11px 14px; }
        .card .card-body-custom,
        .card > div:last-child { padding: 28px; }

        /* Bootstrap-compat shim used by sub-pages */
        .mb-3 { margin-bottom: 20px !important; }
        .mb-4 { margin-bottom: 28px !important; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--gray-700); margin-bottom: 8px; }
        .form-select { appearance: auto; }
        .row { display: flex; flex-wrap: wrap; margin: 0 -8px; }
        .col-md-8 { flex: 0 0 66.666%; max-width: 66.666%; padding: 0 8px; }
        .col-md-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 8px; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; padding: 0 8px; }
        .col-md-2 { flex: 0 0 16.666%; max-width: 16.666%; padding: 0 8px; }
        .row.g-3 { gap: 12px 0; }
        .justify-content-center { justify-content: center; }
        .pagination { display: flex; list-style: none; gap: 4px; padding: 0; }
        .page-item .page-link { display: flex; align-items: center; padding: 6px 12px; border: 1px solid var(--border); border-radius: 8px; color: var(--gray-700); text-decoration: none; font-size: 0.85rem; transition: all 0.15s; }
        .page-item.active .page-link { background: var(--green-600); border-color: var(--green-600); color: #fff; }
        .page-item .page-link:hover { background: var(--gray-100); }
        code { font-family: monospace; background: var(--gray-100); padding: 2px 6px; border-radius: 5px; font-size: 0.82rem; color: var(--gray-700); }

        /* ─────────────────────────────────────────
           BUTTONS
        ───────────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-family: 'Inter', sans-serif;
            font-size: 0.855rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn:hover { filter: brightness(0.96); }
        .btn:active { filter: brightness(0.92); }

        .btn-primary {
            background: linear-gradient(135deg, var(--green-700), var(--green-500));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 2px 8px rgba(22,163,74,0.25);
        }

        .btn-primary:hover {
            box-shadow: 0 4px 14px rgba(22,163,74,0.35);
            filter: none;
            opacity: 0.94;
        }

        .btn-secondary {
            background: #fff;
            border-color: var(--border);
            color: var(--gray-700);
        }

        .btn-danger {
            background: var(--danger-light);
            border-color: var(--danger-border);
            color: var(--danger);
        }

        .btn-warning {
            background: var(--warning-light);
            border-color: #fde68a;
            color: #92400e;
        }

        .btn-sm { padding: 5px 12px; font-size: 0.8rem; }
        .btn-full { width: 100%; justify-content: center; }

        /* ─────────────────────────────────────────
           BADGES
        ───────────────────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 9px;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .badge-green  { background: var(--green-100); color: var(--green-700); }
        .badge-blue   { background: var(--info-light); color: var(--info); }
        .badge-yellow { background: var(--warning-light); color: #92400e; }
        .badge-red    { background: var(--danger-light); color: var(--danger); }
        .badge-gray   { background: var(--gray-100); color: var(--gray-600); }

        /* ─────────────────────────────────────────
           ALERTS
        ───────────────────────────────────────── */
        .alert {
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left-width: 3px;
            border-left-style: solid;
        }

        .alert-success {
            background: var(--green-50);
            border-color: var(--green-500);
            color: var(--green-700);
        }

        .alert-danger {
            background: var(--danger-light);
            border-color: var(--danger);
            color: #b91c1c;
        }

        .alert-info {
            background: var(--info-light);
            border-color: var(--info);
            color: #1d4ed8;
        }

        .alert-warning {
            background: var(--warning-light);
            border-color: var(--warning);
            color: #92400e;
        }

        /* ─────────────────────────────────────────
           PAGE HEADER
        ───────────────────────────────────────── */
        .page-header {
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.4px;
        }

        .page-subtitle {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* ─────────────────────────────────────────
           GRID UTILS
        ───────────────────────────────────────── */
        .grid   { display: grid; gap: 18px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }

        /* ─────────────────────────────────────────
           MISC UTILS
        ───────────────────────────────────────── */
        .text-success { color: var(--green-600) !important; }
        .text-danger  { color: var(--danger) !important; }
        .text-muted   { color: var(--text-muted) !important; }
        .text-warning { color: var(--warning) !important; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 16px; }
        .mb-4 { margin-bottom: 24px; }
        .mt-3 { margin-top: 16px; }
        .mt-4 { margin-top: 24px; }
        .fw-bold { font-weight: 700; }
        .d-flex { display: flex; }
        .align-items-center { align-items: center; }
        .justify-content-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 16px; }
        .text-center { text-align: center; }

        /* ─────────────────────────────────────────
           SCROLLBAR
        ───────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--gray-400); }

        @media (max-width: 768px) {
            .page-body { padding: 16px; }
            .topnav-links { display: none; }
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <nav class="topnav">
        <a class="topnav-brand" href="/inventory-app/public/dashboard.php">
            <div class="brand-icon" style="font-size:0.9rem;font-weight:800;letter-spacing:-0.5px;">KSA</div>
            <div class="brand-name">
                Kios Sembako Azwa
                <small>Inventory System</small>
            </div>
        </a>

        <div class="nav-sep"></div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="topnav-links">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
               href="/inventory-app/public/dashboard.php">Dashboard</a>
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/product/') !== false ? 'active' : '' ?>"
               href="/inventory-app/public/product/index.php">Produk</a>
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/stock/') !== false ? 'active' : '' ?>"
               href="/inventory-app/public/stock/history.php">Histori Stok</a>
        </div>

        <div class="topnav-right">
            <div class="user-pill">
                <div class="user-avatar">
                    <?= strtoupper(substr(escape($_SESSION['display_name'] ?? $_SESSION['role']), 0, 1)) ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= escape($_SESSION['display_name'] ?? ucfirst($_SESSION['role'])) ?></div>
                    <div class="user-role"><?= escape(ucfirst($_SESSION['role'])) ?></div>
                </div>
            </div>
            <a class="btn-logout" href="/inventory-app/public/login.php?logout=true">
                Logout
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <div class="page-body">