    </div><!-- /admin-content -->
</div><!-- /admin-wrapper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo asset('js/script.js') . '?v=' . filemtime(__DIR__ . '/../../js/script.js'); ?>"></script>
<script src="<?php echo asset('js/aerobook.js') . '?v=' . filemtime(__DIR__ . '/../../js/aerobook.js'); ?>"></script>
<script>
// Admin sidebar drawer: sync the backdrop with Bootstrap's collapse state and
// close the drawer when the backdrop or a nav link is tapped on mobile.
(function () {
    var sidebar = document.getElementById('adminSidebar');
    var backdrop = document.getElementById('adminSidebarBackdrop');
    if (!sidebar || !backdrop) return;

    function syncBackdrop() {
        var open = sidebar.classList.contains('show');
        backdrop.classList.toggle('show', open);
        document.body.classList.toggle('admin-drawer-open', open);
    }

    sidebar.addEventListener('show.bs.collapse', syncBackdrop);
    sidebar.addEventListener('shown.bs.collapse', syncBackdrop);
    sidebar.addEventListener('hide.bs.collapse', syncBackdrop);
    sidebar.addEventListener('hidden.bs.collapse', syncBackdrop);

    backdrop.addEventListener('click', function () {
        var instance = bootstrap.Collapse.getOrCreateInstance(sidebar, { toggle: false });
        instance.hide();
    });

    // Tap a sidebar link on mobile → close the drawer so the target page shows.
    // Includes the pinned Operations Dashboard link above the scrollable nav.
    sidebar.querySelectorAll('.sidebar-nav a, .pinned-dashboard-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 992 && sidebar.classList.contains('show')) {
                var instance = bootstrap.Collapse.getOrCreateInstance(sidebar, { toggle: false });
                instance.hide();
            }
        });
    });
})();
</script>
</body>
</html>

