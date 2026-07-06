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

    return { toast, setLoading };
})();
