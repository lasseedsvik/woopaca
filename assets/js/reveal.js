/**
 * Discreet "reveal on scroll". Elements with the class
 * .reveal-on-scroll fade/slide gently into view the first time they
 * become visible, instead of just popping up instantly. The animated
 * version only runs if the browser supports IntersectionObserver and
 * the visitor hasn't requested reduced motion (prefers-reduced-motion);
 * otherwise every target is revealed immediately so content is never
 * left stuck invisible.
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var targets = document.querySelectorAll('.reveal-on-scroll');

        if (!targets.length) {
            return;
        }

        // Without IntersectionObserver support, or when the visitor has
        // requested reduced motion, skip the animation entirely and just
        // show everything immediately – it must never stay at opacity:0.
        var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!('IntersectionObserver' in window) || prefersReducedMotion) {
            targets.forEach(function (el) {
                el.classList.add('reveal-visible');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        });

        targets.forEach(function (el, index) {
            // Slight, staggered delay so multiple cards don't pop in
            // at exactly the same time.
            el.style.transitionDelay = Math.min(index % 6, 5) * 60 + 'ms';
            observer.observe(el);

            // Safety net: if this element never crosses the
            // IntersectionObserver's threshold for any reason (e.g. a
            // very short page, or markup swapped in by another script
            // after this file's own DOMContentLoaded handler already
            // ran, like an AJAX-loaded content refresh elsewhere on
            // the site), it must not stay invisible forever. Reveal it
            // unconditionally after a short delay if that happens.
            setTimeout(function () {
                el.classList.add('reveal-visible');
            }, 1500);
        });
    });
})();
