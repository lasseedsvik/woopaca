<?php
/**
 * WooPaca – woocommerce/single-product/title.php
 *
 * Overrides WooCommerce core's title.php, which normally outputs
 * <h1 class="product_title entry-title">. Rendered as a plain <h1>
 * with no classes on request, relying on the browser/theme's default
 * <h1> styling instead of WooCommerce's classes.
 */

defined('ABSPATH') || exit;
?>
<h1><?php the_title(); ?></h1>
