/**
 * Global site-wide helpers: toast notifications, loading buttons.
 */

const SureCashMining = (function () {
    function toastStack() {
        let stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function toast(message, type = 'success', timeout = 4500) {
        const stack = toastStack();
        const el = document.createElement('div');
        const colors = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info',
        };
        const bsColor = colors[type] || 'success';

        el.className = `toast align-items-center text-bg-${bsColor} border-0 show fade-in-up`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Close"></button>
            </div>`;

        stack.appendChild(el);

        const remove = () => el.remove();
        el.querySelector('.btn-close').addEventListener('click', remove);
        setTimeout(remove, timeout);
    }

    function setLoading(button, loading = true) {
        if (!button) return;
        if (loading) {
            button.dataset.originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Please wait...';
        } else {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || button.innerHTML;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-loading-submit]').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.checkValidity && !form.checkValidity()) return;
                const btn = form.querySelector('button[type="submit"]');
                setLoading(btn, true);
            });
        });

        document.querySelectorAll('[data-toggle-password]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const targetId = toggle.getAttribute('data-toggle-password');
                const input = document.getElementById(targetId);
                if (!input) return;
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                toggle.innerHTML = isHidden ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            });
        });

        // Bidirectional NGN <-> USD converter. Pair a real (submitted) NGN
        // amount input marked data-currency-group="x" with a USD-only
        // convenience input marked data-currency-usd="x". Only the NGN
        // input's value is ever submitted with the form.
        if (window.USD_RATE) {
            document.querySelectorAll('[data-currency-group]').forEach(function (ngnInput) {
                const group = ngnInput.getAttribute('data-currency-group');
                const usdInput = document.querySelector('[data-currency-usd="' + group + '"]');
                if (!usdInput) return;

                let syncing = false;

                ngnInput.addEventListener('input', function () {
                    if (syncing) return;
                    syncing = true;
                    const ngn = parseFloat(ngnInput.value) || 0;
                    usdInput.value = ngn > 0 ? (ngn / window.USD_RATE).toFixed(2) : '';
                    syncing = false;
                });

                usdInput.addEventListener('input', function () {
                    if (syncing) return;
                    syncing = true;
                    const usd = parseFloat(usdInput.value) || 0;
                    ngnInput.value = usd > 0 ? (usd * window.USD_RATE).toFixed(2) : '';
                    syncing = false;
                    ngnInput.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        }
    });

    // Clickable table rows: a <tr data-href="..."> navigates when clicked,
    // unless the click landed on an actual interactive element inside it
    // (buttons, links, form controls keep their own behavior).
    document.addEventListener('click', function (e) {
        const row = e.target.closest('tr[data-href]');
        if (!row || e.target.closest('a, button, input, textarea, select, label, .modal')) return;
        window.location.href = row.getAttribute('data-href');
    });

    // Clickable wallet_ledger rows: <tr data-ledger-row data-ledger-*> opens
    // the shared #txnDetailModal (see includes/partials/transaction-detail-modal.php)
    // populated entirely from the row's own data attributes - no extra request.
    document.addEventListener('click', function (e) {
        const row = e.target.closest('tr[data-ledger-row]');
        if (!row || e.target.closest('a, button, input, textarea, select, label')) return;

        const modalEl = document.getElementById('txnDetailModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        const fields = ['description', 'wallet', 'type', 'amount', 'balance', 'status', 'reference', 'date'];
        fields.forEach(function (field) {
            const el = document.getElementById('txnDetail' + field.charAt(0).toUpperCase() + field.slice(1));
            if (el) el.textContent = row.dataset['ledger' + field.charAt(0).toUpperCase() + field.slice(1)] || '-';
        });

        new bootstrap.Modal(modalEl).show();
    });

    // Track the *actual* visible viewport height as --vvh (assets/css/app.css
    // uses it to cap modal height). window.innerHeight/100vh don't shrink when
    // an on-screen keyboard opens on iOS Safari - it just overlays on top of
    // the page - but visualViewport.height does, on both iOS and Android,
    // which is what lets the modal fix know how much space is really visible.
    if (window.visualViewport) {
        const setVvh = function () {
            document.documentElement.style.setProperty('--vvh', window.visualViewport.height + 'px');
        };
        window.visualViewport.addEventListener('resize', setVvh);
        setVvh();
    }

    return { toast, setLoading };
})();

// Temporary on-screen error reporter for tracking down a mobile-only bug
// (Bootstrap modal backdrop shows but the dialog itself never appears) that
// can't be reproduced with devtools. Only active when ?debugjs=1 is in the
// URL, so it stays invisible to normal visitors. Remove once the bug that
// prompted it is confirmed fixed.
if (new URLSearchParams(window.location.search).has('debugjs')) {
    window.addEventListener('error', function (e) {
        SureCashMining.toast(
            'JS error: ' + e.message + ' @ ' + (e.filename || '').split('/').pop() + ':' + e.lineno,
            'error',
            20000
        );
    });

    window.addEventListener('unhandledrejection', function (e) {
        SureCashMining.toast('Unhandled promise rejection: ' + (e.reason && e.reason.message || e.reason), 'error', 20000);
    });

    // Bootstrap's own modal lifecycle events, so we can see exactly how far
    // a modal.show() call actually gets even when nothing throws: does it
    // fire show.bs.modal (JS started) but never shown.bs.modal (the dialog
    // never actually finished appearing)?
    ['show.bs.modal', 'shown.bs.modal', 'hide.bs.modal', 'hidden.bs.modal'].forEach(function (evtName) {
        document.addEventListener(evtName, function () {
            SureCashMining.toast(evtName + ' fired', 'info', 20000);
        });
    });

    // shown.bs.modal firing with nothing visible on screen points at the
    // dialog being rendered but positioned/scrolled out of view rather than
    // not displayed at all - report its actual computed box and scroll
    // position so that distinction is visible without devtools.
    document.addEventListener('shown.bs.modal', function (e) {
        const content = e.target.querySelector('.modal-content');
        if (!content) {
            alert('shown.bs.modal fired but no .modal-content found in DOM at all.');
            return;
        }

        // Walk every ancestor from .modal-content up to <html>, since a
        // property on .modal-content itself can read back as perfectly
        // normal (opacity:1, visible, correctly colored) while an ANCESTOR
        // still visually hides the whole subtree - opacity compounds, and
        // clip-path/mask/filter/overflow on a parent can clip a child to
        // nothing even when the child's own computed style looks fine.
        const lines = [];
        let el = content;
        let depth = 0;
        while (el && depth < 12) {
            const cs = getComputedStyle(el);
            const r = el.getBoundingClientRect();
            lines.push(
                (el.className ? '.' + String(el.className).split(' ').join('.') : el.tagName)
                + ' | op=' + cs.opacity + ' vis=' + cs.visibility + ' disp=' + cs.display
                + ' ovf=' + cs.overflow + ' clip=' + cs.clipPath + ' filter=' + cs.filter
                + ' transform=' + cs.transform + ' w=' + Math.round(r.width) + ' h=' + Math.round(r.height)
            );
            el = el.parentElement;
            depth++;
        }

        alert('MODAL DEBUG (top of chain = .modal-content):\n\n' + lines.join('\n\n'));
    });
}
