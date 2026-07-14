<?php
/**
 * WooPaca – woocommerce/single-product/woopaca_product_info.php
 *
 * Combined template for the product page's "info column": title,
 * rating, short description, and add-to-cart. Hooked via
 * woocommerce_single_product_summary (see inc/woocommerce.php).
 *
 * The price is no longer rendered here – it's now injected right
 * next to the quantity selector via the
 * woocommerce_after_add_to_cart_quantity hook (see
 * woopaca_product_info_price_after_quantity() in inc/woocommerce.php),
 * so it works the same way for every product type without needing a
 * separate override of each add-to-cart template.
 *
 * The attribute selections themselves (e.g. "SIZE (EU)") are rendered
 * as clickable boxes instead of a dropdown – see
 * woocommerce/single-product/add-to-cart/variable.php.
 */

defined('ABSPATH') || exit;

global $product;
?>
<div class="product-info">
    <?php woocommerce_template_single_title(); ?>

    <?php woocommerce_template_single_rating(); ?>

    <?php woocommerce_template_single_excerpt(); ?>

    <?php woocommerce_template_single_add_to_cart(); ?>
</div>
