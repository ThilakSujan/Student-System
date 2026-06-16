<!-- ═══════════════ GLOBAL TOAST SYSTEM ═══════════════ -->
<style>
    /* Toast container */
    #toast-container {
        position: fixed;
        top: 1.1rem;
        right: 1.2rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    .sys-toast {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 290px;
        max-width: 380px;
        padding: 14px 16px;
        border-radius: 10px;
        box-shadow: 0 8px 28px rgba(0,0,0,.18);
        font-size: 14px;
        font-weight: 500;
        color: #fff;
        pointer-events: all;
        opacity: 0;
        transform: translateX(60px);
        animation: toastSlideIn 0.38s cubic-bezier(.22,1,.36,1) forwards;
        position: relative;
        overflow: hidden;
    }

    @keyframes toastSlideIn {
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes toastSlideOut {
        from { opacity: 1; transform: translateX(0); }
        to   { opacity: 0; transform: translateX(60px); }
    }

    .sys-toast.hiding {
        animation: toastSlideOut 0.3s ease forwards;
    }

    .sys-toast.toast-success { background: linear-gradient(135deg,#1db954,#16a34a); }
    .sys-toast.toast-danger  { background: linear-gradient(135deg,#ef4444,#b91c1c); }
    .sys-toast.toast-warning { background: linear-gradient(135deg,#f59e0b,#d97706); color:#1a1a1a; }
    .sys-toast.toast-info    { background: linear-gradient(135deg,#3b82f6,#1d4ed8); }

    .sys-toast .toast-icon {
        font-size: 20px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .sys-toast .toast-body {
        flex: 1;
        line-height: 1.4;
    }

    .sys-toast .toast-close {
        background: none;
        border: none;
        color: inherit;
        opacity: .75;
        cursor: pointer;
        padding: 0 2px;
        font-size: 16px;
        flex-shrink: 0;
        transition: opacity .15s;
    }
    .sys-toast .toast-close:hover { opacity: 1; }

    /* Progress bar */
    .sys-toast::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(255,255,255,.45);
        width: 100%;
        animation: toastProgress 4.2s linear forwards;
    }
    @keyframes toastProgress {
        from { width: 100%; }
        to   { width: 0%; }
    }
</style>

<div id="toast-container"></div>

<script>
(function () {
    const ICONS = {
        success : 'bi bi-check-circle-fill',
        danger  : 'bi bi-x-circle-fill',
        warning : 'bi bi-exclamation-triangle-fill',
        info    : 'bi bi-info-circle-fill'
    };

    window.showToast = function (message, type) {
        type = type || 'success';
        const container = document.getElementById('toast-container');
        if (!container || !message) return;

        const toast = document.createElement('div');
        toast.className = 'sys-toast toast-' + type;
        toast.innerHTML =
            '<span class="toast-icon"><i class="' + (ICONS[type] || ICONS.info) + '"></i></span>' +
            '<span class="toast-body">' + message + '</span>' +
            '<button class="toast-close" aria-label="Close">&times;</button>';

        // Close button
        toast.querySelector('.toast-close').addEventListener('click', function () {
            dismissToast(toast);
        });

        container.appendChild(toast);

        // Auto-dismiss after 4.5 s
        const timer = setTimeout(function () { dismissToast(toast); }, 4500);
        toast._timer = timer;
    };

    function dismissToast(toast) {
        if (toast._dismissed) return;
        toast._dismissed = true;
        clearTimeout(toast._timer);
        toast.classList.add('hiding');
        toast.addEventListener('animationend', function () { toast.remove(); }, { once: true });
    }

    // Auto-show if a page set window._toastMsg before this script ran
    document.addEventListener('DOMContentLoaded', function () {
        if (window._toastMsg) {
            window.showToast(window._toastMsg, window._toastType || 'success');
        }
    });
})();
</script>
<!-- ═══════════════ END TOAST SYSTEM ══════════════════ -->

<footer>
    &copy; <?php echo date('Y'); ?> Student Information Management System. All rights reserved.
</footer>

</div><!-- /#content -->
</div><!-- /#wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Additional Responsive Scripts -->
<script>
// Ensure responsive behavior on all devices
document.addEventListener('DOMContentLoaded', function() {
    // Fix for responsive tables
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Improve form responsiveness
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.classList.add('was-validated');
    });
});
</script>
<script src="/student_system/assets/js/export.js"></script>
</body>
</html>
<!-- Global Dark Mode Toggle System -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Select all potential dark mode toggle buttons (could be multiple if in header and sidebar)
    const darkModeBtns = document.querySelectorAll('#darkModeBtn, .theme-switch');
    const htmlTag = document.documentElement;

    // The userKey is set globally in header.php (window.dmUserKey)
    const userKey = window.dmUserKey || 'dark_mode_guest_0';

    // Set initial icons if they exist
    const updateIcons = (isDark) => {
        document.querySelectorAll('#darkModeIcon, .bi-moon-stars-fill, .bi-sun-fill').forEach(icon => {
            if (icon.closest('.theme-switch')) {
                if (isDark) {
                    icon.classList.remove('bi-moon-stars-fill');
                    icon.classList.add('bi-sun-fill');
                } else {
                    icon.classList.remove('bi-sun-fill');
                    icon.classList.add('bi-moon-stars-fill');
                }
            }
        });
    };

    if (localStorage.getItem(userKey) === 'dark') {
        updateIcons(true);
    }

    darkModeBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (htmlTag.getAttribute('data-theme') === 'dark') {
                htmlTag.removeAttribute('data-theme');
                localStorage.setItem(userKey, 'light');
                updateIcons(false);
            } else {
                htmlTag.setAttribute('data-theme', 'dark');
                localStorage.setItem(userKey, 'dark');
                updateIcons(true);
            }
            
            // Dispatch custom event so charts can update if they are listening
            window.dispatchEvent(new Event('themeChanged'));
            
            // Legacy fallbacks for dashboard charts
            if (typeof updateChartsTheme === 'function') updateChartsTheme();
            if (typeof updateStudentChartTheme === 'function') updateStudentChartTheme();
        });
    });
    
    // Update chart function listener
    window.addEventListener('themeChanged', () => {
        if (typeof Chart !== 'undefined') {
            const isDark = htmlTag.getAttribute('data-theme') === 'dark';
            const gridColor = isDark ? '#1F2937' : '#E2E8F0';
            const textColor = isDark ? '#94A3B8' : '#64748B';
            
            Chart.instances.forEach(chart => {
                if (chart.options.scales) {
                    if (chart.options.scales.x && chart.options.scales.x.grid) chart.options.scales.x.grid.color = gridColor;
                    if (chart.options.scales.y && chart.options.scales.y.grid) chart.options.scales.y.grid.color = gridColor;
                    if (chart.options.scales.x && chart.options.scales.x.ticks) chart.options.scales.x.ticks.color = textColor;
                    if (chart.options.scales.y && chart.options.scales.y.ticks) chart.options.scales.y.ticks.color = textColor;
                }
                if (chart.options.plugins && chart.options.plugins.centerText) {
                    chart.options.plugins.centerText.color = isDark ? "#F8FAFC" : "#0F172A";
                }
                chart.update();
            });
        }
    });
});
</script>