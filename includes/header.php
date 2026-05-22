<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' – Student System' : 'Student System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        /* Sidebar layout */
        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 240px;
            min-height: 100vh;
            background-color: #212529;
            flex-shrink: 0;
        }

        #content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        #main-content {
            flex: 1;
            padding: 24px;
        }

        /* Sidebar styles */
        #sidebar .sidebar-brand {
            padding: 18px 20px;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid #343a40;
            display: block;
            text-decoration: none;
        }

        #sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 0;
            transition: background 0.15s, color 0.15s;
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
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6c757d;
            padding: 16px 20px 6px;
            font-weight: 600;
        }

        #sidebar .submenu .nav-link {
            padding-left: 44px;
            font-size: 13px;
        }

        /* Footer */
        footer {
            background-color: #212529;
            color: #adb5bd;
            text-align: center;
            padding: 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div id="wrapper">