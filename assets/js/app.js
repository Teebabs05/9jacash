/**
 * Authenticated app-shell interactions: mobile sidebar toggle.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.app-sidebar');
        const backdrop = document.querySelector('.sidebar-backdrop');
        const toggles = document.querySelectorAll('[data-sidebar-toggle]');

        function close() {
            sidebar?.classList.remove('open');
            document.body.classList.remove('sidebar-open');
        }

        function open() {
            sidebar?.classList.add('open');
            document.body.classList.add('sidebar-open');
        }

        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                sidebar?.classList.contains('open') ? close() : open();
            });
        });

        backdrop?.addEventListener('click', close);
    });
})();
