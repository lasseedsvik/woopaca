<?php
/**
 * WooPaca – woocommerce/single-product/meta.php
 *
 * Based on WooCommerce core's single-product/meta.php. SKU and
 * category output are unchanged. The tags section is rebuilt from
 * get_the_terms() instead of wc_get_product_tag_list() so each tag
 * can be rendered as its own pill/chip (see .product-tags in
 * woocommerce.css) instead of a plain comma-separated link list.
 */

defined('ABSPATH') || exit;

global $product;

$sku = $product->get_sku();
$has_categories = wc_get_product_category_list($product->get_id()) ? true : false;
$tags = get_the_terms($product->get_id(), 'product_tag');
$has_tags = $tags && !is_wp_error($tags) ? true : false;
?>
<div class="product_meta">

    <?php do_action('woocommerce_product_meta_start'); ?>

    <?php if (wc_product_sku_enabled() && ($sku || $product->is_type('variable'))) : ?>

        <span class="sku_wrapper"><?php esc_html_e('SKU:', 'woocommerce'); ?> <span class="sku"><?php echo ($sku) ? esc_html($sku) : esc_html__('N/A', 'woocommerce'); ?></span></span>

    <?php endif; ?>

    <?php if ($has_categories) : ?>

        <span class="posted_in">
            <?php
            echo wp_kses_post(wc_get_product_category_list($product->get_id(), ', ', '<span class="posted_in">' . _n('Category:', 'Categories:', count($product->get_category_ids()), 'woocommerce') . ' ', '</span>'));
            ?>
        </span>

    <?php endif; ?>

    <?php do_action('woocommerce_product_meta_end'); ?>

</div>

<?php if ($has_tags) : ?>
    <div class="product-tags">
        <span class="product-tags-label"><?php echo esc_html(_n('Tag', 'Tags', count($tags), 'woocommerce')); ?></span>
        <ul class="product-tags-list">
            <?php foreach ($tags as $tag) : ?>
                <li>
                    <a href="<?php echo esc_url(get_term_link($tag)); ?>" class="product-tag-pill"><?php echo esc_html($tag->name); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
