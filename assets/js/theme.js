/**
 * Dark / Light theme toggle. Persists choice in localStorage under "surecash_theme".
 * Falls back to the OS preference when the user hasn't chosen explicitly.
 */
(function () {
    const STORAGE_KEY = 'surecash_theme';
    const root = document.documentElement;

    function applyTheme(theme) {
        if (theme === 'dark' || theme === 'light') {
            root.setAttribute('data-theme', theme);
        } else {
            root.removeAttribute('data-theme');
        }
        document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
            const current = theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            el.innerHTML = current === 'dark'
                ? '<i class="bi bi-sun"></i>'
                : '<i class="bi bi-moon-stars"></i>';
        });
    }

    const saved = localStorage.getItem(STORAGE_KEY);
    applyTheme(saved);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const current = root.getAttribute('data-theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem(STORAGE_KEY, next);
                applyTheme(next);
            });
        });
        applyTheme(localStorage.getItem(STORAGE_KEY));
    });
})();
