/**
 * Client-side helpers for the authentication pages: password strength meter
 * and confirm-password matching. Server-side validation is always the
 * source of truth; this is purely UX sugar.
 */
(function () {
    function scorePassword(value) {
        let score = 0;
        if (value.length >= 8) score += 25;
        if (/[A-Z]/.test(value)) score += 20;
        if (/[a-z]/.test(value)) score += 20;
        if (/[0-9]/.test(value)) score += 20;
        if (/[^A-Za-z0-9]/.test(value)) score += 15;
        return Math.min(score, 100);
    }

    function colorFor(score) {
        if (score < 40) return '#f04438';
        if (score < 70) return '#f79009';
        return '#12b76a';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const pwInput = document.getElementById('password');
        const meter = document.querySelector('.pw-strength > span');

        if (pwInput && meter) {
            pwInput.addEventListener('input', function () {
                const score = scorePassword(pwInput.value);
                meter.style.width = score + '%';
                meter.style.backgroundColor = colorFor(score);
            });
        }

        const confirmInput = document.getElementById('password_confirmation');
        if (pwInput && confirmInput) {
            const validateMatch = function () {
                if (confirmInput.value && confirmInput.value !== pwInput.value) {
                    confirmInput.setCustomValidity('Passwords do not match.');
                } else {
                    confirmInput.setCustomValidity('');
                }
            };
            pwInput.addEventListener('input', validateMatch);
            confirmInput.addEventListener('input', validateMatch);
        }
    });
})();
