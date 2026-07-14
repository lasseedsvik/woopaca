/**
 * WooPaca – product gallery: open the lightbox from anywhere on the
 * main image, not just the small magnifying-glass trigger in the
 * top corner.
 *
 * WooCommerce core already wires the trigger button
 * (.woocommerce-product-gallery__trigger) up to open the PhotoSwipe
 * lightbox. We don't reimplement that logic – we just forward clicks
 * on the rest of the image to the trigger itself, so both the image
 * and the trigger open the same gallery. The trigger stays visible
 * in the corner as before, for people who look for it there.
 */
(function () {
    document.addEventListener('click', function (e) {
        var image = e.target.closest('.woocommerce-product-gallery__image');

        if (!image) {
            return;
        }

        // Clicks on the trigger itself already do the right thing –
        // leave those alone.
        if (e.target.closest('.woocommerce-product-gallery__trigger')) {
            return;
        }

        // If WooCommerce's own lightbox script already handled this
        // click (it calls preventDefault when it opens the gallery),
        // there's nothing left for us to do.
        if (e.defaultPrevented) {
            return;
        }

        var gallery = image.closest('.woocommerce-product-gallery');
        var trigger = gallery && gallery.querySelector('.woocommerce-product-gallery__trigger');

        if (!trigger) {
            return;
        }

        e.preventDefault();
        trigger.click();
    });
})();
