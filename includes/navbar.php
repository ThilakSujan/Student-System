<!-- ══════════════════════════════════════════════
   PREMIUM TOP NAVBAR
══════════════════════════════════════════════ -->
<style>
/* ── Navbar base ── */
.top-navbar {
    display: flex;
    align-items: center;
    min-height: 56px;
    padding: 0 22px;
    background: linear-gradient(100deg, #4f46e5 0%, #6d28d9 55%, #7c3aed 100%);
    border-bottom: none;
    box-shadow: 0 3px 18px rgba(79,70,229,0.35);
    gap: 12px;
    font-family: 'Inter', sans-serif;
    position: sticky;
    top: 0;
    z-index: 1001;       /* sidebar (1055) slides over this on mobile */
    flex-shrink: 0;
    transition:
        box-shadow 0.3s ease,
        background 0.3s ease;
}

/* ── Scrolled state — deeper + stronger shadow ── */
.top-navbar.scrolled {
    background: linear-gradient(100deg, #3730a3 0%, #5b21b6 55%, #6d28d9 100%);
    box-shadow:
        0 6px 30px rgba(79,70,229,0.45),
        0 2px 0 rgba(255,255,255,0.08) inset;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

/* Page title */
.navbar-page-title {
    font-size: 15px;
    font-weight: 600;
    color: #fff;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 9px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.15);
}

.navbar-page-title .title-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
}

/* Right cluster */
.navbar-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Role badge */
.navbar-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.03em;
    background: rgba(255,255,255,0.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
}

.role-admin   { background: rgba(255,237,180,0.22); color: #fef3c7; border-color: rgba(253,230,138,0.3); }
.role-staff   { background: rgba(186,230,253,0.18); color: #bae6fd;  border-color: rgba(186,230,253,0.28); }
.role-student { background: rgba(167,243,208,0.18); color: #a7f3d0; border-color: rgba(167,243,208,0.28); }

/* Icon buttons */
.navbar-icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.22);
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
    text-decoration: none;
}

.navbar-icon-btn:hover {
    background: rgba(255,255,255,0.28);
    color: #fff;
    border-color: rgba(255,255,255,0.4);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* User dropdown trigger */
.navbar-user-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 5px 12px 5px 6px;
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.15);
    cursor: pointer;
    transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    text-decoration: none;
    color: #fff;
}

.navbar-user-btn:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.45);
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
}

.navbar-user-btn[aria-expanded="true"] {
    background: rgba(255,255,255,0.28);
    border-color: rgba(255,255,255,0.5);
}

.navbar-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255,255,255,0.28);
    border: 2px solid rgba(255,255,255,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

.navbar-username {
    font-size: 13px;
    font-weight: 600;
    color: rgba(255,255,255,0.92);
    line-height: 1;
}

.navbar-user-btn .bi-chevron-down {
    font-size: 11px;
    color: rgba(255,255,255,0.6);
    transition: transform 0.2s ease;
}

.navbar-user-btn[aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

/* Dropdown menu */
.navbar-dropdown {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12);
    overflow: hidden;
    min-width: 200px;
    padding: 6px;
    margin-top: 6px !important;
    background: #fff;
}

.navbar-dropdown .dropdown-header {
    padding: 10px 12px 8px;
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.navbar-dropdown .dropdown-item {
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 9px;
    transition: background 0.15s ease, color 0.15s ease;
}

.navbar-dropdown .dropdown-item i {
    font-size: 15px;
    width: 18px;
    text-align: center;
}

.navbar-dropdown .dropdown-item:hover {
    background: #f0f4ff;
    color: #4f46e5;
}

.navbar-dropdown .dropdown-item.logout-item {
    color: #ef4444;
}

.navbar-dropdown .dropdown-item.logout-item:hover {
    background: #fef2f2;
    color: #dc2626;
}

.navbar-dropdown .dropdown-divider {
    margin: 4px 0;
    border-color: #f1f5f9;
}

/* Mobile toggle */
.sidebar-toggle {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.25);
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.85);
    font-size: 18px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.18s ease, color 0.18s ease;
}

.sidebar-toggle:hover {
    background: rgba(255,255,255,0.28);
    color: #fff;
    border-color: rgba(255,255,255,0.45);
}

/* Vertical divider */
.navbar-vr {
    width: 1px;
    height: 22px;
    background: rgba(255,255,255,0.22);
    border-radius: 1px;
}

/* Mobile responsive */
@media (max-width: 991px) {
    /* On mobile, sticky still works — wrapper offset not needed */
    .sidebar-toggle { display: inline-flex; }
    .navbar-page-title .title-icon { display: none; }
    .navbar-role-badge { display: none; }
}

@media (max-width: 480px) {
    .navbar-username { display: none; }
    .navbar-user-btn { padding: 5px 8px 5px 6px; }
}
</style>

<nav class="top-navbar" id="topNavbar">

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    <!-- Page title -->
    <div class="navbar-page-title">
        <div class="title-icon">
            <?php
            $icons = [
                'dashboard'   => 'bi-speedometer2',
                'students'    => 'bi-people',
                'student'     => 'bi-person-plus',
                'edit'        => 'bi-pencil-square',
                'marks'       => 'bi-list-ol',
                'attendance'  => 'bi-calendar2-check',
                'subjects'    => 'bi-book',
                'staff'       => 'bi-people-fill',
                'admin'       => 'bi-shield-lock',
                'institute'   => 'bi-building',
                'profile'     => 'bi-person-badge',
            ];
            $title_lc = strtolower($page_title ?? 'dashboard');
            $icon = 'bi-grid';
            foreach ($icons as $key => $ico) {
                if (str_contains($title_lc, $key)) { $icon = $ico; break; }
            }
            ?>
            <i class="bi <?= $icon ?>"></i>
        </div>
        <?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?>
    </div>

    <!-- Right cluster -->
    <div class="navbar-right">

        <!-- Role badge -->
        <?php
        $role_label = ucfirst($_SESSION['role'] ?? 'user');
        $role_class = 'role-' . ($_SESSION['role'] ?? 'student');
        $role_icon  = match($_SESSION['role'] ?? '') {
            'admin'   => 'bi-shield-fill',
            'staff'   => 'bi-people-fill',
            default   => 'bi-mortarboard-fill',
        };
        ?>
        <span class="navbar-role-badge <?= $role_class ?>">
            <i class="bi <?= $role_icon ?>"></i>
            <?= $role_label ?>
        </span>

        <div class="navbar-vr"></div>

        <!-- Profile shortcut -->
        <a href="/student_system/profile/view.php" class="navbar-icon-btn" title="My Profile">
            <i class="bi bi-person-badge"></i>
        </a>

        <!-- User dropdown -->
        <div class="dropdown">
            <button class="navbar-user-btn" type="button"
                    id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="navbar-avatar">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="navbar-username hide-mobile">
                    <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-end navbar-dropdown" aria-labelledby="userMenuBtn">
                <li>
                    <div class="dropdown-header">
                        <div style="font-size:13px;font-weight:600;color:#334155;margin-bottom:2px;">
                            <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                        </div>
                        <div><?= $role_label ?> Account</div>
                    </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="/student_system/profile/view.php">
                        <i class="bi bi-person-circle"></i> My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/student_system/dashboard/dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item logout-item" href="/student_system/auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Responsive JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    const overlay       = document.getElementById('sidebarOverlay');

    if (!sidebarToggle || !sidebar || !overlay) return;

    // ── Open / close helpers ───────────────────────────────────
    function openSidebar() {
        sidebar.classList.add('show');
        // Overlay only covers the CONTENT area (right of sidebar)
        // so it NEVER blocks sidebar link taps
        overlay.style.left  = sidebar.offsetWidth + 'px';
        overlay.style.right = '0';
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden'; // prevent background scroll
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        overlay.style.left  = '';
        overlay.style.right = '';
        document.body.style.overflow = '';
    }

    // ── Hamburger button ───────────────────────────────────────
    sidebarToggle.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
    });

    // ── Tap overlay (content area) to close ───────────────────
    // Use both click and touchstart for iOS reliability
    function handleOverlayClose(e) {
        e.preventDefault();
        closeSidebar();
    }
    overlay.addEventListener('click',      handleOverlayClose);
    overlay.addEventListener('touchstart', handleOverlayClose, { passive: false });

    // ── Nav link clicks: let them navigate normally ────────────
    // Just close the sidebar first, then the <a href> does its job
    sidebar.querySelectorAll('a.nav-link').forEach(function(link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 992) {
                // Close without preventing navigation
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                overlay.style.left  = '';
                document.body.style.overflow = '';
            }
        });
    });

    // ── On desktop resize: reset everything ──────────────────
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });

    // ── Scroll highlight ──────────────────────────────
    const navbar = document.getElementById('topNavbar');
    if (navbar) {
        let ticking = false;
        const onScroll = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    // Use the scrollable parent (#content or window)
                    const scrollY = document.getElementById('content')?.scrollTop
                                 ?? window.scrollY;
                    navbar.classList.toggle('scrolled', scrollY > 10);
                    ticking = false;
                });
                ticking = true;
            }
        };

        // Listen on both #content (desktop flex scroll) and window
        const contentEl = document.getElementById('content');
        if (contentEl) contentEl.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('scroll', onScroll, { passive: true });

        // Run once on load in case page is already scrolled
        onScroll();
    }
});
</script>