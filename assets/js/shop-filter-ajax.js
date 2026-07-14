/**
 * WooPaca – assets/js/shop-filter-ajax.js
 *
 * Every filter control in the sidebar (categories, price, global and
 * custom attribute boxes, the active-filter tags, Reset, and the
 * relocated "Filter" button – see woocommerce/shop-sidebar.php)
 * already works as a plain link/GET form pointing at the correctly
 * filtered URL. Instead of building a separate AJAX endpoint that
 * duplicates all of that filtering logic, this just fetches that same
 * URL in the background and swaps in the two containers that can
 * actually change (.shop-sidebar and .shop-content – see
 * woocommerce/archive-product.php), then updates the address bar via
 * pushState. Falls back to a normal navigation if the fetch fails for
 * any reason.
 */
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.querySelector('.shop-sidebar');
    var content = document.querySelector('.shop-content');
    var layout = document.querySelector('.shop-layout');

    if (!sidebar || !content || !layout) {
        return;
    }

    function setLoading(isLoading) {
        layout.classList.toggle('shop-filter-loading', isLoading);
    }

    function afterSwap(url, title) {
        // WooCommerce's own price-slider script listens for this event
        // on document.body to (re)initialize any .price_slider element
        // present in the DOM – needed since the slider we just inserted
        // via innerHTML has no JS behavior attached yet.
        if (window.jQuery) {
            window.jQuery(document.body).trigger('init_price_filter');
        }

        document.title = title;
        window.history.pushState({ shopFilterAjax: true }, title, url);
        setLoading(false);

        var top = layout.getBoundingClientRect().top + window.pageYOffset - 32;
        window.scrollTo({ top: top, behavior: 'smooth' });
    }

    function loadUrl(url) {
        setLoading(true);

        fetch(url, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Bad response');
                }
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newSidebar = doc.querySelector('.shop-sidebar');
                var newContent = doc.querySelector('.shop-content');

                if (!newSidebar || !newContent) {
                    throw new Error('Unexpected response markup');
                }

                sidebar.innerHTML = newSidebar.innerHTML;
                content.innerHTML = newContent.innerHTML;

                // The product cards just inserted above carry the
                // .reveal-on-scroll class (see reveal.js / style.css),
                // which starts them at opacity: 0 until a one-time
                // IntersectionObserver set up on the original page load
                // reveals them. That observer only ever knew about the
                // cards present at DOMContentLoaded, not ones swapped in
                // afterwards here – so without this, freshly filtered
                // cards would stay invisible forever. Mark them visible
                // immediately instead; a scroll-triggered fade-in isn't
                // useful right after the visitor just clicked a filter
                // control anyway.
                content.querySelectorAll('.reveal-on-scroll').forEach(function (el) {
                    el.classList.add('reveal-visible');
                });

                afterSwap(url, doc.title);
            })
            .catch(function () {
                // Something went wrong (offline, blocked request,
                // unexpected markup) – fall back to a real page load
                // rather than leaving the visitor stuck mid-filter.
                window.location.href = url;
            });
    }

    function isInterceptableLink(link) {
        if (!link || !sidebar.contains(link)) {
            return false;
        }
        if (link.target && link.target !== '' && link.target !== '_self') {
            return false;
        }
        var href = link.getAttribute('href');
        return !!href && href.charAt(0) !== '#';
    }

    // Categories, attribute boxes, active-filter tags, Reset, "Show
    // all products" – every filter link lives inside .shop-sidebar.
    sidebar.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!isInterceptableLink(link)) {
            return;
        }
        e.preventDefault();
        loadUrl(link.getAttribute('href'));
    });

    // The price filter's "Filter" button (see shop-sidebar.php) is a
    // type="submit" button associated with #woopaca-price-filter-form
    // via the HTML5 form="" attribute, even though it's rendered
    // outside that form in the DOM – its click still fires a normal
    // submit event on the form, which bubbles up to document.
    function submitPriceFilterForm(form) {
        var params = new URLSearchParams(new FormData(form));
        var action = form.getAttribute('action') || window.location.pathname;
        loadUrl(action + '?' + params.toString());
    }

    document.addEventListener('submit', function (e) {
        if (!e.target || e.target.id !== 'woopaca-price-filter-form') {
            return;
        }
        e.preventDefault();
        submitPriceFilterForm(e.target);
    });

    // Releasing the price slider handle also refreshes the results
    // immediately, without needing a separate click on "Filter".
    // WooCommerce's own price-slider script (jQuery UI slider under
    // the hood) fires a "slidestop" event on .price_slider when a
    // handle is released – delegated via jQuery so it still works
    // after the slider markup gets replaced by an AJAX swap above.
    if (window.jQuery) {
        window.jQuery(document).on('slidestop', '.price_slider', function () {
            var form = document.getElementById('woopaca-price-filter-form');
            if (form) {
                submitPriceFilterForm(form);
            }
        });
    }

    // Back/forward after an AJAX filter change.
    window.addEventListener('popstate', function () {
        loadUrl(window.location.href);
    });
});
