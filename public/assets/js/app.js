(function () {
    'use strict';

    // ---------------------------------------------------------------- Theme
    const root = document.documentElement;
    const stored = localStorage.getItem('9jc_theme');
    if (stored) root.setAttribute('data-theme', stored);

    function toggleTheme() {
        const current = root.getAttribute('data-theme') === 'dark' ? 'dark' :
            (root.getAttribute('data-theme') === 'light' ? 'light' :
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
        const next = current === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem('9jc_theme', next);
        updateThemeIcon(next);
    }

    function updateThemeIcon(mode) {
        document.querySelectorAll('.theme-toggle-icon').forEach(el => {
            el.className = 'theme-toggle-icon fa-solid ' + (mode === 'dark' ? 'fa-sun' : 'fa-moon');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.theme-toggle-btn').forEach(btn => btn.addEventListener('click', toggleTheme));
        updateThemeIcon(root.getAttribute('data-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

        document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
            btn.addEventListener('click', () => document.querySelector('.app-sidebar')?.classList.toggle('show'));
        });

        initCountdowns();
        initFlashToasts();

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }
    });

    // ---------------------------------------------------------------- AJAX
    window.NineJC = window.NineJC || {};

    NineJC.csrfToken = function () {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    NineJC.post = async function (url, data) {
        const formData = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) {
            Object.keys(data || {}).forEach(k => formData.append(k, data[k]));
        }
        formData.append('csrf_token', NineJC.csrfToken());

        const res = await fetch(url, { method: 'POST', body: formData, credentials: 'same-origin' });
        let json;
        try {
            json = await res.json();
        } catch (e) {
            json = { success: false, message: 'Unexpected server response.' };
        }
        return json;
    };

    NineJC.toast = function (icon, title) {
        if (typeof Swal === 'undefined') { alert(title); return; }
        Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 3000, timerProgressBar: true });
    };

    NineJC.confirm = async function (opts) {
        if (typeof Swal === 'undefined') return confirm(opts.text || 'Are you sure?');
        const result = await Swal.fire({
            title: opts.title || 'Are you sure?',
            text: opts.text || '',
            icon: opts.icon || 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0D47A1',
            cancelButtonColor: '#6c757d',
            confirmButtonText: opts.confirmText || 'Yes, proceed',
        });
        return result.isConfirmed;
    };

    function initFlashToasts() {
        document.querySelectorAll('[data-flash]').forEach(el => {
            const type = el.getAttribute('data-flash');
            const msg = el.textContent.trim();
            if (msg) NineJC.toast(type === 'error' ? 'error' : type, msg);
        });
    }

    function initCountdowns() {
        document.querySelectorAll('[data-countdown]').forEach(el => {
            const end = new Date(el.getAttribute('data-countdown')).getTime();
            const tick = () => {
                const diff = end - Date.now();
                if (diff <= 0) { el.textContent = 'Completed'; return; }
                const d = Math.floor(diff / 86400000);
                const h = Math.floor((diff % 86400000) / 3600000);
                const m = Math.floor((diff % 3600000) / 60000);
                const s = Math.floor((diff % 60000) / 1000);
                el.textContent = `${d}d ${h}h ${m}m ${s}s`;
                requestAnimationFrame(() => setTimeout(tick, 1000));
            };
            tick();
        });
    }
})();
