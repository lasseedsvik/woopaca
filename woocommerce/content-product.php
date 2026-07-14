<?php
/**
 * WooPaca – product card in the product list (replaces WooCommerce's
 * default template content-product.php).
 *
 * We keep all the usual WooCommerce hooks (before/after_shop_loop_item
 * etc.) so that plugins and e.g. the "SALE" badge keep working, but
 * wrap them in the theme's own card design (.product-card).
 *
 * NOTE: The layout for <ul class="products"> is set as CSS Grid in
 * assets/css/woocommerce.css. That file also resets WooCommerce's
 * ::before/::after clearfix on .products, which would otherwise
 * create an empty "ghost cell" in the grid layout (a known
 * WooCommerce bug).
 */

defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
    return;
}
?>
<li <?php wc_product_class('product-card-item', $product); ?>>
    <div class="product-card">
        <a href="<?php the_permalink(); ?>" class="product-card-link">
            <?php
            /**
             * woocommerce_before_shop_loop_item_title hook.
             * Contains, among other things, the product image and the
             * "SALE" badge.
             */
            do_action('woocommerce_before_shop_loop_item_title');
            ?>
            <h2 class="product-name"><?php the_title(); ?></h2>
        </a>

        <?php
        /**
         * woocommerce_after_shop_loop_item_title hook.
         * Contains the rating and price (the currency always comes
         * from WooCommerce – never hardcoded).
         */
        do_action('woocommerce_after_shop_loop_item_title');
        ?>

        <div class="product-card-footer">
            <?php
            /**
             * woocommerce_after_shop_loop_item hook.
             * Contains the "Add to cart" button.
             */
            do_action('woocommerce_after_shop_loop_item');
            ?>
        </div>
    </div>
</li>
