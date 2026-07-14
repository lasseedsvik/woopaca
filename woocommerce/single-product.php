<?php
/**
 * WooPaca – woocommerce/single-product.php
 *
 * Replaces WooCommerce's default template for the product page. Just
 * like in archive-product.php, we call get_header()/get_footer()
 * directly instead of relying on the generic
 * woocommerce_before_main_content / woocommerce_after_main_content
 * hooks. Reason: the default template runs
 * do_action('woocommerce_sidebar') AFTER these hooks, but our wrapper
 * (woopaca_wc_wrapper_end in inc/woocommerce.php) already prints
 * get_footer() at woocommerce_after_main_content – so the sidebar
 * would otherwise end up rendering after the page was already closed
 * (</html>), showing WordPress's default widgets (Pages/Archives/
 * Categories) at the very bottom of the source code. By building the
 * page ourselves here, without do_action('woocommerce_sidebar') at
 * all, this can never happen on the product page.
 */

defined('ABSPATH') || exit;

get_header(); ?>

<div id="site-wrap">
    <main id="woopaca-main-content" class="site-main woocommerce-main">
        <div class="container">
            <?php
            // WooCommerce normally shows breadcrumbs via
            // woocommerce_breadcrumb(), hooked to
            // woocommerce_before_main_content. Since this template
            // deliberately doesn't call that action at all (see the
            // reasoning above), we call the breadcrumb function
            // directly instead, rather than reintroducing the whole
            // hook chain just to get this one piece back.
            woocommerce_breadcrumb();
            ?>
            <?php while (have_posts()) : ?>
                <?php the_post(); ?>
                <?php wc_get_template_part('content', 'single-product'); ?>
            <?php endwhile; ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>
