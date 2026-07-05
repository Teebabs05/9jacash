/**
 * Live countdown chips. Any element with [data-countdown] and a
 * data-target ISO datetime attribute gets a ticking "Xh Ym Zs" label.
 */
(function () {
    function format(ms) {
        if (ms <= 0) return 'Processing…';
        const totalSeconds = Math.floor(ms / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        return `${hours}h ${minutes}m ${seconds}s`;
    }

    function tick() {
        document.querySelectorAll('[data-countdown]').forEach(function (el) {
            const target = new Date(el.getAttribute('data-target')).getTime();
            el.textContent = format(target - Date.now());
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.querySelector('[data-countdown]')) return;
        tick();
        setInterval(tick, 1000);
    });
})();
