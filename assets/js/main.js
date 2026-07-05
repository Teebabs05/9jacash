/**
 * Global site-wide helpers: toast notifications, loading buttons.
 */

const NineJaCash = (function () {
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
    });

    return { toast, setLoading };
})();
