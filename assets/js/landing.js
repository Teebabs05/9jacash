/**
 * Landing page interactions: scroll-reveal, animated stat counters,
 * smooth-scroll navigation, and AJAX newsletter/contact form submission.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        // Reveal-on-scroll
        const revealEls = document.querySelectorAll('.reveal');
        if ('IntersectionObserver' in window && revealEls.length) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });
            revealEls.forEach(function (el) { observer.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('in-view'); });
        }

        // Animated stat counters
        document.querySelectorAll('[data-count-to]').forEach(function (el) {
            const target = parseFloat(el.getAttribute('data-count-to')) || 0;
            const prefix = el.getAttribute('data-prefix') || '';
            const suffix = el.getAttribute('data-suffix') || '';
            let started = false;

            function animate() {
                if (started) return;
                started = true;
                const duration = 1400;
                const start = performance.now();

                function tick(now) {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = Math.floor(eased * target);
                    el.textContent = prefix + value.toLocaleString() + suffix;
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }

            if ('IntersectionObserver' in window) {
                const obs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) { animate(); obs.unobserve(entry.target); }
                    });
                }, { threshold: 0.4 });
                obs.observe(el);
            } else {
                animate();
            }
        });

        // Smooth scroll for in-page anchors
        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                const targetId = link.getAttribute('href');
                if (targetId.length < 2) return;
                const target = document.querySelector(targetId);
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelector('.navbar-collapse.show')?.classList.remove('show');
            });
        });

        // Newsletter form (AJAX)
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = newsletterForm.querySelector('button[type="submit"]');
                SureCashMining.setLoading(btn, true);

                fetch(newsletterForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(newsletterForm),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        SureCashMining.toast(data.message, data.success ? 'success' : 'error');
                        if (data.success) newsletterForm.reset();
                    })
                    .catch(function () {
                        SureCashMining.toast('Something went wrong. Please try again.', 'error');
                    })
                    .finally(function () { SureCashMining.setLoading(btn, false); });
            });
        }

        // Contact form (AJAX)
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = contactForm.querySelector('button[type="submit"]');
                SureCashMining.setLoading(btn, true);

                fetch(contactForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(contactForm),
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        SureCashMining.toast(data.message, data.success ? 'success' : 'error');
                        if (data.success) contactForm.reset();
                    })
                    .catch(function () {
                        SureCashMining.toast('Something went wrong. Please try again.', 'error');
                    })
                    .finally(function () { SureCashMining.setLoading(btn, false); });
            });
        }
    });
})();
