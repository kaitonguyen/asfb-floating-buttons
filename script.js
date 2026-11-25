(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.querySelector('.asfb-fb-container');
        var scrollTopBtn = document.getElementById('asfb-fb-scroll-top');
        var toggleBtn = document.getElementById('asfb-fb-toggle-btn');
        var config = window.asfbFbConfig || {};

        if (scrollTopBtn) {
            var updateScrollBtn = function () {
                var y = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
                if (y > 200) {
                    scrollTopBtn.classList.add('is-visible');
                } else {
                    scrollTopBtn.classList.remove('is-visible');
                }
            };

            window.addEventListener('scroll', updateScrollBtn, { passive: true });
            updateScrollBtn();

            scrollTopBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        if (container && toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                var expanded = container.classList.toggle('is-expanded');
                toggleBtn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });

            document.addEventListener('click', function (e) {
                if (container.classList.contains('is-expanded') && !container.contains(e.target)) {
                    container.classList.remove('is-expanded');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            });

            document.addEventListener('keydown', function (e) {
                if ('Escape' === e.key && container.classList.contains('is-expanded')) {
                    container.classList.remove('is-expanded');
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (config.trackAnalytics && container) {
            container.querySelectorAll('[data-asfb-channel]').forEach(function (link) {
                link.addEventListener('click', function () {
                    var channel = link.getAttribute('data-asfb-channel') || 'unknown';
                    if (typeof window.gtag === 'function') {
                        window.gtag('event', 'contact_click', {
                            event_category: 'asfb_floating_buttons',
                            event_label: channel
                        });
                    }
                    if (typeof window.fbq === 'function') {
                        window.fbq('track', 'Contact', { content_name: channel });
                    }
                });
            });
        }
    });
})();
