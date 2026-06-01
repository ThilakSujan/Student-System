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
</body>
</html>