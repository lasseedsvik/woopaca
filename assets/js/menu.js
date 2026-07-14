document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.menu-toggle');
    var nav    = document.querySelector('.main-navigation');

    if (!toggle || !nav) return;

    // ── Hamburger ───────────────────────────────────────
    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.setAttribute('aria-label',    isOpen ? 'Stäng meny' : 'Öppna meny');
    });

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            nav.classList.remove('nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Öppna meny');
        }
    });

    // ── Arrow buttons for submenu (mobile/tablet) ───────
    document.querySelectorAll('.nav-item--top').forEach(function (item) {
        var subMenu = item.querySelector('.sub-menu');
        var link    = item.querySelector(':scope > .nav-link');

        if (!subMenu || !link) return;

        // Create arrow button
        var btn = document.createElement('button');
        btn.className = 'submenu-toggle';
        btn.setAttribute('aria-label', 'Visa undermeny');
        btn.setAttribute('type', 'button');
        btn.innerHTML = '<span class="material-symbols-outlined">expand_more</span>';

        // Click on the arrow: toggle dropdown — the link navigates as usual
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = item.classList.toggle('submenu-open');
            btn.querySelector('.material-symbols-outlined').textContent =
                isOpen ? 'expand_less' : 'expand_more';
            btn.setAttribute('aria-label', isOpen ? 'Dölj undermeny' : 'Visa undermeny');
        });

        link.insertAdjacentElement('afterend', btn);
    });
});
