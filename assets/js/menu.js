document.addEventListener('DOMContentLoaded', function () {
    // Translated strings from woopaca_scripts() in functions.php.
    var t = window.woopacaMenuL10n || {
        openMenu: 'Open menu', closeMenu: 'Close menu',
        showSubmenu: 'Show submenu', hideSubmenu: 'Hide submenu'
    };

    var toggle = document.querySelector('.menu-toggle');
    var nav    = document.querySelector('.main-navigation');

    if (!toggle || !nav) return;

    // ── Hamburger ───────────────────────────────────────
    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.setAttribute('aria-label',    isOpen ? t.closeMenu : t.openMenu);
    });

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            nav.classList.remove('nav-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', t.openMenu);
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
        btn.setAttribute('aria-label', t.showSubmenu);
        btn.setAttribute('type', 'button');
        btn.innerHTML = '<span class="material-symbols-outlined">expand_more</span>';

        // Click on the arrow: toggle dropdown — the link navigates as usual
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = item.classList.toggle('submenu-open');
            btn.querySelector('.material-symbols-outlined').textContent =
                isOpen ? 'expand_less' : 'expand_more';
            btn.setAttribute('aria-label', isOpen ? t.hideSubmenu : t.showSubmenu);
        });

        link.insertAdjacentElement('afterend', btn);
    });
});
