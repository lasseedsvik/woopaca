<?php
/**
 * WooPaca – woocommerce/archive-product.php
 *
 * Replaces WooCommerce's default template for the shop/category
 * pages. Builds a layout with a filter panel (categories, price,
 * attributes) next to the product grid – this can't be solved just
 * via the wrapper hooks in inc/woocommerce.php, since the
 * woocommerce_sidebar hook would otherwise fire after the footer has
 * already been printed.
 *
 * The grid has fewer columns than before (see woocommerce.css) so the
 * cards don't become unnecessarily narrow when the filter panel takes
 * up space on the left.
 */

defined('ABSPATH') || exit;

get_header(); ?>

<div id="site-wrap">
    <main id="woopaca-main-content" class="site-main woocommerce-main">
        <div class="container shop-layout">

            <aside class="shop-sidebar" aria-label="<?php esc_attr_e('Filtrera produkter', 'woopaca'); ?>">
                <?php wc_get_template('shop-sidebar.php'); ?>
            </aside>

            <div class="shop-content">
                <header class="woocommerce-products-header">
                    <?php
                    // WooCommerce already gives every product category an
                    // image field (Products > Categories > edit a category
                    // > "Thumbnail") and a description field, but only the
                    // description is shown automatically via
                    // woocommerce_archive_description below – the image
                    // isn't output anywhere by default, so we fetch and
                    // show it here ourselves.
                    $category_image_url = '';
                    if (is_product_category()) {
                        $term = get_queried_object();
                        if ($term && !is_wp_error($term)) {
                            $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                            if ($thumbnail_id) {
                                $category_image_url = wp_get_attachment_image_url($thumbnail_id, 'woopaca_category_hero');
                            }
                        }
                    }
                    ?>

                    <?php if ($category_image_url) : ?>
                        <div class="category-banner-image">
                            <img src="<?php echo esc_url($category_image_url); ?>"
                                alt="<?php echo esc_attr(single_term_title('', false)); ?>">
                        </div>
                    <?php endif; ?>

                    <?php if (apply_filters('woocommerce_show_page_title', true)) : ?>
                        <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
                    <?php endif; ?>

                    <?php do_action('woocommerce_archive_description'); ?>
                </header>

                <?php
                if (woocommerce_product_loop()) {

                    echo '<div class="shop-toolbar">';
                    do_action('woocommerce_before_shop_loop');
                    echo '</div>';

                    woocommerce_product_loop_start();

                    if (wc_get_loop_prop('total')) {
                        while (have_posts()) {
                            the_post();

                            do_action('woocommerce_shop_loop');

                            wc_get_template_part('content', 'product');
                        }
                    }

                    woocommerce_product_loop_end();

                    do_action('woocommerce_after_shop_loop');
                } else {
                    do_action('woocommerce_no_products_found');
                }
                ?>
            </div>
        </div>
    </main>
</div>

<?php get_footer(); ?>
