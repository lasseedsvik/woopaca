/**
 * WooPaca – keeps the header cart count badge (.cart-count) in sync
 * with cart changes made through WooCommerce Blocks (the Cart page's
 * quantity steppers/remove links, coupon codes, etc.).
 *
 * The classic add-to-cart flow (product page "Add to cart" button)
 * already updates .cart-count on its own, via WooCommerce's built-in
 * AJAX cart-fragments system (see woopaca_cart_count_fragment() in
 * inc/woocommerce.php, hooked to woocommerce_add_to_cart_fragments).
 *
 * But the Cart page itself is a WooCommerce Blocks Cart, which talks
 * to the Store API directly and has no idea the classic fragments
 * system exists – so editing quantities or removing an item there
 * never triggered a fragments refresh, leaving the header badge
 * showing a stale number. This file watches the Cart block's own
 * data store (wc/store/cart, powered by @wordpress/data) instead,
 * which reflects every change the Cart/Checkout blocks make,
 * regardless of what triggered it.
 */
(function () {
    'use strict';

    function updateCartCountBadges(count) {
        if (typeof count === 'undefined' || count === null) {
            return;
        }

        document.querySelectorAll('.cart-count').forEach(function (el) {
            el.textContent = count;
        });
    }

    function watchCartStore(attemptsLeft) {
        if (typeof wp === 'undefined' || !wp.data || typeof wp.data.select !== 'function') {
            if (attemptsLeft > 0) {
                setTimeout(function () {
                    watchCartStore(attemptsLeft - 1);
                }, 250);
            }
            return;
        }

        var cartStore = wp.data.select('wc/store/cart');

        if (!cartStore) {
            // The store registers itself once a Cart/Checkout block is
            // on the page – it may not exist yet the moment this runs,
            // or may never exist on pages without those blocks.
            if (attemptsLeft > 0) {
                setTimeout(function () {
                    watchCartStore(attemptsLeft - 1);
                }, 250);
            }
            return;
        }

        var lastCount = null;

        wp.data.subscribe(function () {
            var cartData = wp.data.select('wc/store/cart').getCartData();

            if (!cartData) {
                return;
            }

            var count = typeof cartData.itemsCount !== 'undefined'
                ? cartData.itemsCount
                : cartData.items_count;

            if (typeof count === 'undefined' || count === lastCount) {
                return;
            }

            lastCount = count;
            updateCartCountBadges(count);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Try for up to ~5 seconds; harmless no-op if the store never
        // shows up (e.g. on a page with no Cart/Checkout block at all).
        watchCartStore(20);
    });
})();
