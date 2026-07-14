<?php
/**
 * WooPaca – woocommerce/shop-sidebar.php
 *
 * Filter panel for the shop's product list: an "active filters"
 * summary (tags + Reset), categories, price filter, global attribute
 * filters (if any global product attributes exist), and custom/
 * per-product attribute filters (see woopaca_get_local_product_attributes()
 * in inc/woocommerce.php). The price filter is rendered via
 * the_widget() so it works out of the box without anyone adding
 * widgets manually in wp-admin; the two attribute sections build
 * their own markup (see woopaca_render_shop_attribute_filter() in
 * inc/woocommerce.php) so they can match the clickable attribute-box
 * design used on the product page, instead of WooCommerce's default
 * layered-nav link list.
 *
 * WooCommerce handles the actual filtering logic for categories,
 * price, and global attributes; the local/custom attribute filter has
 * its own logic (see woopaca_filter_products_by_local_attributes()).
 *
 * The price filter's own "Filter" submit button is pulled out of its
 * widget markup and re-rendered at the very bottom of this file,
 * below every attribute group, instead of sitting between the price
 * slider and the attribute filters – see $filter_button_html below.
 */

defined('ABSPATH') || exit;

$base_url = is_shop() ? get_post_type_archive_link('product') : get_term_link(get_queried_object());

// Collected while building the attribute filter groups below, then
// rendered as removable tags + a "Reset" link near the top of the
// panel.
$active_filter_tags = array();
?>
<div class="shop-filter-group shop-filter-categories">
    <h2 class="shop-filter-title"><?php echo esc_html(get_theme_mod('shop_category_filter_label', 'Categories')); ?></h2>
    <ul class="shop-category-list">
        <?php
        $current_term = is_tax('product_cat') ? get_queried_object() : null;

        wp_list_categories(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'orderby'    => 'menu_order',
            'title_li'   => '',
            'show_count' => true,
        ));
        ?>
    </ul>
    <?php if ($current_term) : ?>
        <a class="shop-filter-clear" href="<?php echo esc_url(get_post_type_archive_link('product')); ?>">
            <?php esc_html_e('Show all products', 'woopaca'); ?>
        </a>
    <?php endif; ?>
</div>

<?php
// WC_Widget_Price_Filter intentionally renders nothing at all (no
// markup, no error) on pages that aren't the shop page or a product
// category/tag archive, and also when every visible product's price
// rounds to the exact same value – there'd be nothing to filter. That
// makes the widget's own output unpredictable depending on context,
// so we capture it first and only show the "Filtrera efter pris"
// heading if a slider was actually produced, instead of leaving a
// heading with nothing underneath it.
ob_start();
the_widget(
    'WC_Widget_Price_Filter',
    array('title' => ''),
    array(
        'before_widget' => '<div class="widget woocommerce widget_price_filter">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="screen-reader-text">',
        'after_title'   => '</h3>',
    )
);
$price_filter_html = ob_get_clean();

// Pull the widget's "Filter" submit button out of its own markup so
// it can be echoed again, once, at the bottom of this file. It stays
// working from its new spot via the HTML5 form="" attribute, since
// physically moving it outside the <form> it came from would
// otherwise stop it from submitting anything.
$filter_button_html = '';
if (trim($price_filter_html) !== '') {
    $price_filter_html = preg_replace('/<form\b/i', '<form id="woopaca-price-filter-form"', $price_filter_html, 1);

    if (preg_match('/<button\b[^>]*>.*?<\/button>/is', $price_filter_html, $button_match)) {
        $filter_button_html = preg_replace('/<button\b/i', '<button form="woopaca-price-filter-form"', $button_match[0], 1);
        $price_filter_html = str_replace($button_match[0], '', $price_filter_html);
    }
}

if (trim($price_filter_html) !== '') :
?>
    <div class="shop-filter-group shop-filter-price">
        <h2 class="shop-filter-title"><?php echo esc_html(get_theme_mod('shop_price_filter_label', 'Filter by price')); ?></h2>
        <?php echo $price_filter_html; ?>
    </div>
<?php endif; ?>

<?php
$attribute_taxonomies = wc_get_attribute_taxonomies();

if (!empty($attribute_taxonomies)) :
    foreach ($attribute_taxonomies as $tax) :
        $taxonomy = wc_attribute_taxonomy_name($tax->attribute_name);

        if (!taxonomy_exists($taxonomy)) {
            continue;
        }

        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => true));

        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }

        // filter_{attribute} / query_type_{attribute} are WooCommerce's
        // own query var conventions (see wc_get_layered_nav_chosen_attributes()
        // and WC_Query::layered_nav_query()) – core applies the actual
        // filtering based on these regardless of which widget/markup
        // produced the links, so building our own box-styled links here
        // still uses WooCommerce's native, indexed filtering.
        $filter_key = 'filter_' . $tax->attribute_name;
        $query_type_key = 'query_type_' . $tax->attribute_name;
        $current_slugs = isset($_GET[$filter_key]) ? explode(',', wc_clean(wp_unslash($_GET[$filter_key]))) : array();

        $items = array();
        foreach ($terms as $term) {
            $is_chosen = in_array($term->slug, $current_slugs, true);
            $new_slugs = $is_chosen
                ? array_values(array_diff($current_slugs, array($term->slug)))
                : array_merge($current_slugs, array($term->slug));

            $query_args = wp_unslash($_GET);
            unset($query_args['paged']);

            if (!empty($new_slugs)) {
                $query_args[$filter_key] = implode(',', $new_slugs);
                $query_args[$query_type_key] = 'and';
            } else {
                unset($query_args[$filter_key], $query_args[$query_type_key]);
            }

            $item_url = $query_args ? $base_url . '?' . http_build_query($query_args) : $base_url;

            $items[] = array(
                'label' => $term->name,
                'url' => $item_url,
                'chosen' => $is_chosen,
            );

            if ($is_chosen) {
                $active_filter_tags[] = array(
                    'label' => $tax->attribute_label . ': ' . $term->name,
                    'remove_url' => $item_url,
                );
            }
        }

        woopaca_render_shop_attribute_filter($tax->attribute_label, $items);
    endforeach;
endif;

// Custom (per-product, non-global) attributes – see
// woopaca_get_local_product_attributes() / woopaca_filter_products_by_local_attributes()
// in inc/woocommerce.php for why these need separate handling from the
// global attribute loop above.
$local_attributes = woopaca_get_local_product_attributes();

if (!empty($local_attributes)) :
    foreach ($local_attributes as $attr_key => $attribute) :
        if (empty($attribute['values'])) {
            continue;
        }

        $selected_values = isset($_GET['local_attr'][$attr_key]) ? array_map('sanitize_text_field', (array) wp_unslash($_GET['local_attr'][$attr_key])) : array();

        $items = array();
        foreach ($attribute['values'] as $value) {
            $is_chosen = in_array($value, $selected_values, true);

            $query_args = wp_unslash($_GET);
            unset($query_args['paged']);
            $current_values = isset($query_args['local_attr'][$attr_key]) ? (array) $query_args['local_attr'][$attr_key] : array();

            if ($is_chosen) {
                $current_values = array_values(array_diff($current_values, array($value)));
            } else {
                $current_values[] = $value;
            }

            if (!empty($current_values)) {
                $query_args['local_attr'][$attr_key] = $current_values;
            } elseif (isset($query_args['local_attr'][$attr_key])) {
                unset($query_args['local_attr'][$attr_key]);
            }

            $item_url = $query_args ? $base_url . '?' . http_build_query($query_args) : $base_url;

            $items[] = array(
                'label' => $value,
                'url' => $item_url,
                'chosen' => $is_chosen,
            );

            if ($is_chosen) {
                $active_filter_tags[] = array(
                    'label' => $attribute['label'] . ': ' . $value,
                    'remove_url' => $item_url,
                );
            }
        }

        woopaca_render_shop_attribute_filter($attribute['label'], $items);
    endforeach;
endif;

if (!empty($active_filter_tags) || $current_term || isset($_GET['min_price']) || isset($_GET['max_price'])) :
    // A full reset: back to the plain /shop/ page with no category,
    // no price range, and no attribute filters – not just the current
    // category/price with attributes stripped, since "Reset" should
    // mean a genuinely blank slate.
    $reset_url = get_post_type_archive_link('product');
    ?>
    <div class="shop-active-filters">
        <?php foreach ($active_filter_tags as $tag) : ?>
            <a class="shop-active-filter-tag" href="<?php echo esc_url($tag['remove_url']); ?>">
                <?php echo esc_html($tag['label']); ?>
                <span aria-hidden="true">&times;</span>
            </a>
        <?php endforeach; ?>
        <a class="shop-active-filters-reset" href="<?php echo esc_url($reset_url); ?>"><?php esc_html_e('Reset all', 'woopaca'); ?></a>
    </div>
<?php endif; ?>

<?php if (!empty($filter_button_html)) : ?>
    <div class="shop-filter-apply">
        <?php echo $filter_button_html; ?>
    </div>
<?php endif; ?>
