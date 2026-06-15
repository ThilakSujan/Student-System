<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' – Student System' : 'Student System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <style>
        /* ══════════════════════════════════════════════════════
           RESPONSIVE LAYOUT STYLES
        ══════════════════════════════════════════════════════ */

        /* ── Page entrance transition ── */
        #main-content {
            animation: pageFadeIn 0.4s ease both;
        }

        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Card entrance ── */
        .card {
            animation: cardFadeIn 0.5s ease both;
            transition: box-shadow .25s ease, transform .25s ease;
        }
        .card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,.10) !important;
            transform: translateY(-2px);
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Table rows ── */
        tbody tr {
            animation: rowSlideIn 0.35s ease both;
        }
        tbody tr:nth-child(1)  { animation-delay: .03s }
        tbody tr:nth-child(2)  { animation-delay: .06s }
        tbody tr:nth-child(3)  { animation-delay: .09s }
        tbody tr:nth-child(4)  { animation-delay: .12s }
        tbody tr:nth-child(5)  { animation-delay: .15s }
        tbody tr:nth-child(6)  { animation-delay: .18s }
        tbody tr:nth-child(7)  { animation-delay: .21s }
        tbody tr:nth-child(8)  { animation-delay: .24s }
        tbody tr:nth-child(9)  { animation-delay: .27s }
        tbody tr:nth-child(10) { animation-delay: .30s }

        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ── Alerts ── */
        .alert {
            animation: alertDrop 0.4s ease both;
        }
        @keyframes alertDrop {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Buttons ── */
        .btn {
            transition: transform .15s ease, box-shadow .15s ease, background .2s ease;
        }
        .btn:hover  { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        .btn:active { transform: translateY(0); }

        /* ── Badges ── */
        .badge {
            transition: transform .15s ease;
        }
        .badge:hover { transform: scale(1.08); }

        /* ── Page link transitions (sidebar links) ── */
        #sidebar .nav-link {
            transition: background .2s ease, color .2s ease, border-left .2s ease, padding-left .2s ease;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            background-color: var(--dash-bg, #f8f9fa); /* Prevents white gaps at the bottom of the page */
        }

        /* Sidebar layout */
        #wrapper {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }

        #sidebar {
            width: 255px;
            height: 100vh; /* Fixed exactly to viewport height */
            background-color: #0f172a;
            flex-shrink: 0;
            position: sticky; /* FIX ISSUE 2: Sidebar stays fixed when page scrolls */
            top: 0;           /* Pins sidebar to the top */
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto; /* Internal scrolling if sidebar content is long */
            z-index: 1000;
        }

        #sidebar.hidden {
            margin-left: -255px;
        }

        #content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--dash-bg, transparent);
            min-width: 0;
        }

        #main-content {
            flex: 1;
            padding: 20px;
        }

        /* Toggle button for mobile */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            color: #ffffff;
            font-size: 24px;
            cursor: pointer;
            padding: 8px 12px;
            z-index: 999;
            transition: opacity 0.2s ease;
        }

        .sidebar-toggle:hover {
            opacity: 0.8;
        }

        /* Floating Header for Mobile/Tablet */
        .navbar {
            position: relative;
        }

        /* Top spacing for navbar on mobile */
        #content {
            padding-top: 0;
        }

        /* Sidebar styles */
        #sidebar .sidebar-brand {
            padding: 16px 18px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid #343a40;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            white-space: nowrap;
        }

        #sidebar .brand-icon {
            font-size: 24px;
            flex-shrink: 0;
        }

        #sidebar .brand-text {
            font-size: 14px;
            font-weight: 700;
        }

        #sidebar .brand-sub {
            font-size: 11px;
            color: #adb5bd;
            margin-top: 2px;
        }

        #sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 18px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 0;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
            white-space: nowrap;
        }

        #sidebar .nav-link:hover {
            background-color: #343a40;
            color: #fff;
        }

        #sidebar .nav-link.active {
            background-color: #0d6efd;
            color: #fff;
        }

        #sidebar .nav-section {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
            padding: 14px 18px 6px;
            font-weight: 600;
        }

        #sidebar .submenu .nav-link {
            padding-left: 40px;
            font-size: 13px;
        }

        /* Footer */
        footer {
            background-color: #212529;
            color: #adb5bd;
            text-align: center;
            padding: 12px;
            font-size: 13px;
            margin-top: auto;
        }

        /* Overlay for mobile when sidebar is open */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.45);
            z-index: 999;
            pointer-events: none;   /* inert when hidden */
        }

        .sidebar-overlay.show {
            display: block;
            pointer-events: auto;   /* active only when open */
        }

        /* ══════════════════════════════════════════════════════
           TABLET + MOBILE — sidebar off-canvas, content full-width
        ══════════════════════════════════════════════════════ */
        @media (max-width: 991px) {

            /* ── Wrapper: keep flex-row; sidebar is fixed so content fills width ── */
            #wrapper {
                flex-direction: row;
            }

            /* ── Content always fills remaining width ── */
            #content {
                width: 100%;
                min-width: 0;
                flex: 1;
                /* No top-margin needed — sticky navbar handles positioning */
            }

            /* ── Sidebar: full-height off-canvas drawer ── */
            #sidebar {
                position: fixed !important;
                top: 0;               /* covers from very top — no gap above navbar */
                left: 0;
                width: 270px;
                height: 100vh;        /* full viewport height */
                z-index: 1055;        /* above overlay (1050) and navbar (1001) */
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                will-change: transform;
                overflow-y: auto;
                overflow-x: hidden;
                padding-bottom: 60px;
                scroll-behavior: smooth;
            }

            #sidebar.show {
                transform: translateX(0) !important;
                box-shadow: 6px 0 30px rgba(0, 0, 0, 0.35);
            }

            /* ── Overlay: covers content behind open sidebar ── */
            .sidebar-overlay {
                z-index: 1050;       /* below sidebar, above everything else */
            }

            /* ── Scrollbar inside sidebar ── */
            #sidebar::-webkit-scrollbar       { width: 4px; }
            #sidebar::-webkit-scrollbar-track { background: transparent; }
            #sidebar::-webkit-scrollbar-thumb { background: rgba(99,102,241,.5); border-radius: 4px; }

            /* ── Show hamburger toggle ── */
            .sidebar-toggle {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
            }

            /* ── Content padding ── */
            #main-content    { padding: 16px; }
            .container-fluid { padding-left: 12px; padding-right: 12px; }

            /* ── Sidebar brand & nav tweaks ── */
            #sidebar .sidebar-brand  { padding: 14px 16px; font-size: 14px; }
            #sidebar .brand-text     { font-size: 13px; }
            #sidebar .brand-sub      { font-size: 10px; }
            #sidebar .nav-link       { padding: 9px 16px; font-size: 13px; gap: 10px; }
            #sidebar .nav-link i     { font-size: 17px; flex-shrink: 0; }
            #sidebar .nav-section    { padding: 10px 16px 4px; font-size: 9px; }
            #sidebar .submenu .nav-link { padding-left: 40px; }
        }

        /* ══════════════════════════════════════════════════════
           MOBILE (≤ 575px)
        ══════════════════════════════════════════════════════ */
        @media (max-width: 575px) {

            #sidebar {
                width: 260px;        /* slightly narrower on phones */
            }

            #main-content    { padding: 12px; }
            .container-fluid { padding-left: 8px; padding-right: 8px; }

            /* Typography */
            h2 { font-size: 19px !important; }
            h4 { font-size: 15px !important; }
            h5 { font-size: 14px !important; }

            /* Buttons */
            .btn-sm { padding: 0.3rem 0.65rem; font-size: 12px; }

            /* Tables */
            .table           { font-size: 13px; }
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Cards */
            .card-header { padding: 10px 12px !important; }
            .card-body   { padding: 12px !important; }

            /* Sidebar text tighter */
            #sidebar .sidebar-brand { padding: 12px 14px; font-size: 13px; }
            #sidebar .brand-text    { font-size: 12px; }
            #sidebar .brand-sub     { font-size: 9px; }
            #sidebar .nav-link      { padding: 8px 14px; font-size: 12px; gap: 9px; }
            #sidebar .nav-link i    { font-size: 15px; }
            #sidebar .nav-section   { padding: 9px 14px 3px; font-size: 8px; }
        }

        /* ══════════════════════════════════════════════════════
           EXTRA SMALL (≤ 400px)
        ══════════════════════════════════════════════════════ */
        @media (max-width: 400px) {

            #sidebar { width: 230px; }

            #main-content    { padding: 10px; }
            .container-fluid { padding-left: 6px; padding-right: 6px; }

            h4 { font-size: 14px !important; }
            .btn-sm { font-size: 11px; }

            #sidebar .nav-link   { padding: 7px 12px; font-size: 11px; gap: 8px; }
            #sidebar .nav-link i { font-size: 14px; }
        }




        /* Responsive utility classes */
        @media (max-width: 767px) {
            .hide-mobile {
                display: none !important;
            }
        }

        @media (min-width: 992px) {
            .hide-desktop {
                display: none !important;
            }
        }

        /* Responsive text truncation */
        .text-truncate-mobile {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 575px) {
            .text-truncate-mobile {
                max-width: 100px;
            }
        }

        /* Ensure DataTables is responsive */
        .dataTables_wrapper {
            width: 100%;
        }

        .dataTables_length, .dataTables_filter {
            margin-bottom: 10px;
        }

        @media (max-width: 575px) {
            .dataTables_length, .dataTables_filter {
                font-size: 12px;
            }

            .dataTables_length select, .dataTables_filter input {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<div id="wrapper">