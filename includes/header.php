<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' – Student System' : 'Student System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Sidebar layout */
        #wrapper {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }

        #sidebar {
            width: 240px;
            min-height: 100vh;
            background-color: #212529;
            flex-shrink: 0;
            position: relative;
            transition: margin-left 0.3s ease;
            overflow-y: auto;
            z-index: 1000;
            padding-bottom: 30px;
        }

        #sidebar.hidden {
            margin-left: -240px;
        }

        #content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
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
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* ══════════════════════════════════════════════════════
           TABLET STYLES (768px and below)
        ══════════════════════════════════════════════════════ */
        @media (max-width: 991px) {
            /* Floating Header */
            .navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1001;
                width: 100%;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            #wrapper {
                margin-top: 48px;
            }

            #sidebar {
                position: fixed;
                width: 270px;
                height: calc(100vh - 48px);
                left: 0;
                top: 48px;
                margin-left: -270px;
                overflow-y: auto;
                overflow-x: hidden;
                padding-bottom: 40px;
                scroll-behavior: smooth;
            }

            #sidebar::-webkit-scrollbar {
                width: 6px;
            }

            #sidebar::-webkit-scrollbar-track {
                background: #343a40;
            }

            #sidebar::-webkit-scrollbar-thumb {
                background: #0d6efd;
                border-radius: 3px;
            }

            #sidebar::-webkit-scrollbar-thumb:hover {
                background: #0b5ed7;
            }

            #sidebar.show {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            #main-content {
                padding: 16px;
            }

            #sidebar .sidebar-brand {
                padding: 13px 15px;
                font-size: 14px;
            }

            #sidebar .brand-text {
                font-size: 13px;
            }

            #sidebar .brand-sub {
                font-size: 10px;
            }

            #sidebar .nav-link {
                padding: 8px 15px;
                font-size: 12px;
                gap: 9px;
            }

            #sidebar .nav-link i {
                font-size: 17px;
                flex-shrink: 0;
            }

            #sidebar .nav-section {
                padding: 10px 15px 4px;
                font-size: 9px;
            }

            #sidebar .submenu .nav-link {
                padding-left: 38px;
            }

            .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }
        }

        /* ══════════════════════════════════════════════════════
           MOBILE STYLES (576px and below)
        ══════════════════════════════════════════════════════ */
        @media (max-width: 575px) {
            #sidebar {
                width: 240px;
                margin-left: -240px;
                top: 48px;
                height: calc(100vh - 48px);
                overflow-y: auto;
                overflow-x: hidden;
                padding-bottom: 40px;
                scroll-behavior: smooth;
            }

            #sidebar::-webkit-scrollbar {
                width: 5px;
            }

            #sidebar::-webkit-scrollbar-track {
                background: #343a40;
            }

            #sidebar::-webkit-scrollbar-thumb {
                background: #0d6efd;
                border-radius: 2px;
            }

            #sidebar::-webkit-scrollbar-thumb:hover {
                background: #0b5ed7;
            }

            #sidebar.show {
                margin-left: 0;
            }

            #main-content {
                padding: 12px;
            }

            #sidebar .sidebar-brand {
                padding: 11px 13px;
                font-size: 13px;
            }

            #sidebar .brand-text {
                font-size: 11px;
            }

            #sidebar .brand-sub {
                font-size: 9px;
                margin-top: 1px;
            }

            #sidebar .nav-link {
                padding: 7px 13px;
                font-size: 11px;
                gap: 8px;
            }

            #sidebar .nav-link i {
                font-size: 15px;
                flex-shrink: 0;
                min-width: 15px;
            }

            #sidebar .nav-section {
                padding: 8px 13px 3px;
                font-size: 7px;
            }

            #sidebar .submenu .nav-link {
                padding-left: 36px;
            }

            .sidebar-toggle {
                padding: 6px 10px;
                font-size: 20px;
            }

            .container-fluid {
                padding-left: 8px;
                padding-right: 8px;
            }

            /* Adjust heading sizes */
            h2 {
                font-size: 20px !important;
            }

            h4 {
                font-size: 16px !important;
            }

            .btn-sm {
                padding: 0.375rem 0.75rem;
                font-size: 12px;
            }

            .table {
                font-size: 13px;
            }

            .card-header {
                padding: 12px !important;
            }

            .card-body {
                padding: 12px !important;
            }

            /* Make tables scrollable on mobile */
            .table-responsive {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            /* Stack form elements */
            .row {
                margin-left: -6px;
                margin-right: -6px;
            }

            .col-md-6, .col-lg-4, .col-lg-3 {
                padding-left: 6px;
                padding-right: 6px;
                margin-bottom: 12px;
            }

            /* Dashboard cards */
            .stat-card {
                margin-bottom: 12px;
            }
        }

        /* ══════════════════════════════════════════════════════
           EXTRA SMALL DEVICES (below 400px)
        ══════════════════════════════════════════════════════ */
        @media (max-width: 399px) {
            #sidebar {
                width: 200px;
                margin-left: -200px;
                top: 48px;
                height: calc(100vh - 48px);
                overflow-y: auto;
                overflow-x: hidden;
                padding-bottom: 40px;
                scroll-behavior: smooth;
            }

            #sidebar::-webkit-scrollbar {
                width: 4px;
            }

            #sidebar::-webkit-scrollbar-track {
                background: #343a40;
            }

            #sidebar::-webkit-scrollbar-thumb {
                background: #0d6efd;
                border-radius: 2px;
            }

            #sidebar::-webkit-scrollbar-thumb:hover {
                background: #0b5ed7;
            }

            #sidebar .sidebar-brand {
                padding: 10px 11px;
                font-size: 12px;
            }

            #sidebar .brand-text {
                font-size: 10px;
            }

            #sidebar .brand-sub {
                font-size: 8px;
            }

            #sidebar .nav-link {
                padding: 6px 11px;
                font-size: 10px;
                gap: 7px;
            }

            #sidebar .nav-link i {
                font-size: 14px;
                flex-shrink: 0;
            }

            #sidebar .nav-section {
                padding: 7px 11px 2px;
                font-size: 6px;
            }

            #sidebar .submenu .nav-link {
                padding-left: 33px;
            }

            #main-content {
                padding: 10px;
            }

            h2 {
                font-size: 18px !important;
            }

            h4 {
                font-size: 14px !important;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 11px;
            }

            .table {
                font-size: 11px;
            }
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