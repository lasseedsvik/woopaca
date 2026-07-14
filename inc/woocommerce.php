<?php
/**
 * WooPaca – WooCommerce integration
 *
 * All price/currency is always fetched via WooCommerce's own
 * functions (wc_price(), $product->get_price_html(), etc.). The theme
 * NEVER hardcodes a currency symbol – the store setting in WooCommerce
 * (Settings > General > Currency) controls this fully automatically.
 */

defined('ABSPATH') || exit;

/**
 * 1. Declare support for WooCommerce.
 */
function woopaca_woocommerce_setup()
{
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'woopaca_woocommerce_setup');

/**
 * 2. Disable WooCommerce's default stylesheet – we ship our own,
 *    separate file (assets/css/woocommerce.css) so the store styles
 *    are never mixed up with the theme's general style.css.
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * 3. Load our own WooCommerce stylesheet + JS for the attribute
 *    buttons.
 */
function woopaca_woocommerce_scripts()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    wp_enqueue_style(
        'woopaca-woocommerce',
        get_template_directory_uri() . '/assets/css/woocommerce.css',
        array('woopaca-style'),
        woopaca_asset_version('/assets/css/woocommerce.css')
    );

    // Keeps the header cart count in sync with cart changes made via
    // WooCommerce Blocks (see the file itself for why this is needed
    // in addition to the classic add-to-cart fragments below).
    wp_enqueue_script(
        'woopaca-cart-count-sync',
        get_template_directory_uri() . '/assets/js/cart-count-sync.js',
        array('wp-data'),
        woopaca_asset_version('/assets/js/cart-count-sync.js'),
        true
    );

    if (is_product()) {
        wp_enqueue_script(
            'woopaca-variations',
            get_template_directory_uri() . '/assets/js/variations.js',
            array('jquery', 'wc-add-to-cart-variation'),
            woopaca_asset_version('/assets/js/variations.js'),
            true
        );

        // Makes clicking anywhere on the main gallery image open the
        // lightbox, in addition to the magnifying-glass trigger in
        // its top corner. Depends on wc-single-product so it loads
        // after WooCommerce's own gallery/lightbox script.
        wp_enqueue_script(
            'woopaca-gallery',
            get_template_directory_uri() . '/assets/js/gallery.js',
            array('wc-single-product'),
            woopaca_asset_version('/assets/js/gallery.js'),
            true
        );
    }

    if (is_shop() || is_product_taxonomy()) {
        // Depends on jquery so WooCommerce's own price-slider script
        // (also jquery-based) is guaranteed to already be registered
        // when we trigger its init_price_filter event after an AJAX
        // swap – see the file itself.
        wp_enqueue_script(
            'woopaca-shop-filter-ajax',
            get_template_directory_uri() . '/assets/js/shop-filter-ajax.js',
            array('jquery'),
            woopaca_asset_version('/assets/js/shop-filter-ajax.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'woopaca_woocommerce_scripts', 20);

/**
 * 4. Our own page wrapper instead of WooCommerce's <div class="woocommerce">.
 *    We reuse the theme's regular header/footer and container class so
 *    the store pages (product list, product page, cart, checkout …)
 *    look like the rest of the site.
 */
function woopaca_wc_wrapper_start()
{
    get_header();
    echo '<div id="site-wrap"><main id="woopaca-main-content" class="site-main woocommerce-main"><div class="container">';
}
function woopaca_wc_wrapper_end()
{
    echo '</div></main></div>';
    get_footer();
}
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
add_action('woocommerce_before_main_content', 'woopaca_wc_wrapper_start', 10);
add_action('woocommerce_after_main_content', 'woopaca_wc_wrapper_end', 10);

/**
 * 5. Product page: replace the separate default hooks (title, rating,
 *    price, short description, add-to-cart) with a combined template,
 *    woocommerce/single-product/woopaca_product_info.php, that gathers
 *    everything in one place. It's in that template (via the
 *    add-to-cart template) that the attributes are now shown as
 *    clickable boxes instead of a dropdown.
 */
function woopaca_replace_product_summary_hooks()
{
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

    add_action('woocommerce_single_product_summary', 'woopaca_product_info', 15);
}
add_action('woocommerce_before_single_product_summary', 'woopaca_replace_product_summary_hooks', 1);

function woopaca_product_info()
{
    wc_get_template('single-product/woopaca_product_info.php');
}

/**
 * Renders one group of shop-filter options as boxes – same visual
 * style as the clickable attribute boxes on the product page (see
 * .dm-attribute-box in woocommerce.css and
 * woocommerce/single-product/add-to-cart/variable.php) – instead of
 * a plain text link list. Used for both global attribute filters and
 * custom/local attribute filters in shop-sidebar.php.
 *
 * @param string $label Section heading, e.g. the attribute name.
 * @param array  $items Each item: array('label' => ..., 'url' => ..., 'chosen' => bool).
 */
function woopaca_render_shop_attribute_filter($label, $items)
{
    if (empty($items)) {
        return;
    }

    // Folded by default on every screen size, so a shop with several
    // attribute groups doesn't turn into a long stack of open filter
    // lists before the visitor even reaches the sidebar's price
    // filter or the product grid. Force-opened via the "open"
    // attribute if one of its own options is already selected, so an
    // active filter is never hidden away from the visitor who set it.
    $has_active_selection = false;
    foreach ($items as $item) {
        if (!empty($item['chosen'])) {
            $has_active_selection = true;
            break;
        }
    }
    ?>
    <details class="shop-filter-group shop-filter-attribute" <?php echo $has_active_selection ? 'open' : ''; ?>>
        <summary class="shop-filter-title"><?php echo esc_html($label); ?></summary>
        <div class="dm-attribute-options dm-attribute-options--filter">
            <?php foreach ($items as $item) : ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                    class="dm-attribute-box<?php echo $item['chosen'] ? ' dm-attribute-box--selected' : ''; ?>">
                    <?php echo esc_html($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </details>
    <?php
}

/**
 * Renders the price right after the quantity selector, for every
 * product type (simple, variable, etc.) – WooCommerce fires
 * woocommerce_after_add_to_cart_quantity right after the quantity
 * field in both templates/single-product/add-to-cart/simple.php and
 * .../variation-add-to-cart-button.php, so this one hook covers both
 * without needing separate template overrides.
 */
function woopaca_product_info_price_after_quantity()
{
    global $product;

    if (!$product) {
        return;
    }

    echo '<div class="product-info-price">' . wp_kses_post($product->get_price_html()) . '</div>';
}
add_action('woocommerce_after_add_to_cart_quantity', 'woopaca_product_info_price_after_quantity');

/**
 * 6. Helper function: readable name for an attribute value. Global/
 *    taxonomy attributes are stored as slugs and need to be looked up
 *    against their term, custom product attributes already use the
 *    visible value.
 */
function woopaca_get_attribute_option_name($attribute_name, $option)
{
    if (taxonomy_exists($attribute_name)) {
        $term = get_term_by('slug', $option, $attribute_name);
        if ($term && !is_wp_error($term)) {
            return $term->name;
        }
    }

    return apply_filters('woocommerce_variation_option_name', $option);
}

/**
 * 7. Update the cart count in the header via AJAX (WooCommerce cart
 *    fragments) so the visitor doesn't have to reload the page after
 *    "Add to cart".
 */
function woopaca_cart_count_fragment($fragments)
{
    ob_start();
    ?>
    <span class="cart-count"><?php echo esc_html(WC()->cart ? WC()->cart->get_cart_contents_count() : 0); ?></span>
    <?php
    $fragments['span.cart-count'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'woopaca_cart_count_fragment');

/**
 * 8. Number of columns in the product grid (used by some WooCommerce
 *    functions/plugins). The actual layout on screen is controlled by
 *    the CSS grid in woocommerce.css, but we keep this filter in sync
 *    with the customizer choice below so anything that reads
 *    loop_shop_columns() (plugins, related-products args, etc.)
 *    reports the same number the visitor actually sees.
 */
function woopaca_loop_columns()
{
    return woopaca_shop_columns_desktop();
}
add_filter('loop_shop_columns', 'woopaca_loop_columns');

/**
 * 8b. Desktop column count for the shop/category product grid
 *     (/shop and any other product listing – category, tag, search
 *     results – since they all share woocommerce/archive-product.php
 *     and its .shop-content wrapper). Editable under
 *     Appearance → Customize → Shop layout. Defaults to 3, matching
 *     the theme's original design.
 */
function woopaca_shop_columns_desktop()
{
    $columns = (int) get_theme_mod('woopaca_shop_columns_desktop', 3);

    return in_array($columns, array(3, 4), true) ? $columns : 3;
}

/**
 * 8c. Customizer control for the setting above.
 */
function woopaca_customize_register_shop_layout(WP_Customize_Manager $wp_customize)
{
    $wp_customize->add_section('shop_layout', array(
        'title' => 'Product Layout',
        'panel' => 'shop_settings',
        'priority' => 30,
    ));

    $wp_customize->add_setting('woopaca_shop_columns_desktop', array(
        'default' => 3,
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('woopaca_shop_columns_desktop', array(
        'label' => 'Product columns (desktop)',
        'description' => 'Number of product columns on /shop and other product listing pages (category, tag, search) on desktop screens. Tablet and mobile automatically show fewer columns regardless of this setting.',
        'section' => 'shop_layout',
        'type' => 'radio',
        'choices' => array(
            3 => '3 columns',
            4 => '4 columns',
        ),
    ));
}
add_action('customize_register', 'woopaca_customize_register_shop_layout');

/**
 * 8d. Print the chosen desktop column count as inline CSS, right
 *     after woocommerce.css (which sets the 3-column default via
 *     !important – see that file for why !important is needed
 *     there). Only outputs anything when 4 columns has actually been
 *     chosen, since 3 columns is already the CSS default.
 */
function woopaca_shop_columns_desktop_css()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    if (4 !== woopaca_shop_columns_desktop()) {
        return;
    }

    $css = '@media (min-width: 1200px) { .shop-content ul.products { grid-template-columns: repeat(4, 1fr) !important; } }';

    wp_add_inline_style('woopaca-woocommerce', $css);
}
add_action('wp_enqueue_scripts', 'woopaca_shop_columns_desktop_css', 20);

/**
 * 9. Make sure WooCommerce's default pages (Shop, Cart, Checkout,
 *    My account) exist and are published, and that login/
 *    registration is enabled.
 *
 *    If the Checkout page is missing or not linked in WooCommerce
 *    (Settings > Advanced > Page woopaca_setup), the "Proceed to checkout"
 *    button on the cart page points nowhere, which looks like
 *    "nothing happens" when you click it. This function heals that
 *    automatically by recreating missing pages and turning on account
 *    registration, so both checkout and login/registration work right
 *    away.
 */
function woopaca_ensure_woocommerce_pages()
{
    if (!class_exists('WC_Install')) {
        return;
    }

    $required_pages = array('shop', 'cart', 'checkout', 'myaccount');
    $missing = false;

    foreach ($required_pages as $page) {
        $page_id = wc_get_page_id($page);
        if ($page_id < 1 || 'publish' !== get_post_status($page_id)) {
            $missing = true;
            break;
        }
    }

    if ($missing) {
        WC_Install::create_pages();
    }

    // Allow customers to create an account on the My account page and at checkout.
    if ('yes' !== get_option('woocommerce_enable_myaccount_registration')) {
        update_option('woocommerce_enable_myaccount_registration', 'yes');
    }
    if ('yes' !== get_option('woocommerce_enable_signup_and_login_from_checkout')) {
        update_option('woocommerce_enable_signup_and_login_from_checkout', 'yes');
    }
    if ('yes' !== get_option('woocommerce_enable_checkout_login_reminder')) {
        update_option('woocommerce_enable_checkout_login_reminder', 'yes');
    }
}
add_action('after_switch_theme', 'woopaca_ensure_woocommerce_pages');
add_action('admin_init', 'woopaca_ensure_woocommerce_pages');

/**
 * 10. Search in the header: clicking the search icon shows a text
 *     field, and while typing, matching products (with image) are
 *     fetched via AJAX without reloading the page. See
 *     assets/js/search.js.
 */
function woopaca_enqueue_search_assets()
{
    if (!class_exists('WooCommerce')) {
        return;
    }

    wp_enqueue_script(
        'woopaca-search',
        get_template_directory_uri() . '/assets/js/search.js',
        array('jquery'),
        woopaca_asset_version('/assets/js/search.js'),
        true
    );

    wp_localize_script('woopaca-search', 'woopacaSearch', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('site_search_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'woopaca_enqueue_search_assets', 20);

/**
 * AJAX response: finds published products whose name matches the
 * search term and returns what's needed to draw the results list
 * (image, name, price, link). The price comes from
 * $product->get_price_html() just like everywhere else, so the
 * currency is never hardcoded.
 */
function woopaca_ajax_product_search()
{
    check_ajax_referer('site_search_nonce', 'nonce');

    $search_term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';

    if (mb_strlen($search_term) < 2) {
        wp_send_json_success(array());
    }

    $query = new WP_Query(array(
        's'                   => $search_term,
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
    ));

    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());

            if (!$product) {
                continue;
            }

            $results[] = array(
                'title'     => $product->get_name(),
                'url'       => $product->get_permalink(),
                'price'     => $product->get_price_html(),
                'thumbnail' => $product->get_image('thumbnail'),
            );
        }
        wp_reset_postdata();
    }

    wp_send_json_success($results);
}
add_action('wp_ajax_woopaca_product_search', 'woopaca_ajax_product_search');
add_action('wp_ajax_nopriv_woopaca_product_search', 'woopaca_ajax_product_search');

/**
 * 11. "Empty cart" button at the bottom of the cart page.
 *
 *     a) Classic cart template: a button is added right after the
 *        cart items table (inside WooCommerce's own cart form), so it
 *        submits together with that form.
 *     b) Cart block (Gutenberg): the block doesn't offer a PHP hook to
 *        insert markup inside it, so a small script instead adds the
 *        same button right after the block once the page has loaded.
 *        Clicking it links to the cart URL with a signed "empty_cart"
 *        parameter, handled below.
 *     Both paths end up at woopaca_empty_cart_maybe_handle(), which does the
 *     actual emptying and redirects back to a clean cart URL.
 */
function woopaca_empty_cart_button_classic()
{
    if (!function_exists('WC') || WC()->cart->is_empty()) {
        return;
    }

    $confirm_text = get_theme_mod('cart_empty_confirm_text', 'Are you sure you want to empty the cart?');
    ?>
    <div class="cart-empty-actions">
        <?php wp_nonce_field('site_empty_cart', 'site_empty_cart_nonce'); ?>
        <button type="submit" name="site_empty_cart" value="1" class="cart-empty-button"
            onclick="return confirm('<?php echo esc_js($confirm_text); ?>');">
            <span class="material-symbols-outlined" aria-hidden="true">remove_shopping_cart</span>
            <?php echo esc_html(get_theme_mod('cart_empty_button_label', 'Empty cart!')); ?>
        </button>
    </div>
    <?php
}
add_action('woocommerce_after_cart_table', 'woopaca_empty_cart_button_classic');

function woopaca_empty_cart_button_block_fallback()
{
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }

    if (!function_exists('WC') || WC()->cart->is_empty()) {
        return;
    }

    wp_enqueue_script(
        'woopaca-cart',
        get_template_directory_uri() . '/assets/js/cart.js',
        array(),
        woopaca_asset_version('/assets/js/cart.js'),
        true
    );

    wp_localize_script('woopaca-cart', 'woopacaCart', array(
        'emptyCartUrl' => esc_url_raw(wp_nonce_url(add_query_arg('empty_cart', '1', wc_get_cart_url()), 'site_empty_cart', 'site_empty_cart_nonce')),
        'confirmText' => get_theme_mod('cart_empty_confirm_text', 'Are you sure you want to empty the cart?'),
        'buttonLabel' => get_theme_mod('cart_empty_button_label', 'Empty cart!'),
        'storeApiCartUrl' => esc_url_raw(rest_url('wc/store/v1/cart')),
    ));
}
add_action('wp_enqueue_scripts', 'woopaca_empty_cart_button_block_fallback', 25);

function woopaca_empty_cart_maybe_handle()
{
    if (!function_exists('WC')) {
        return;
    }

    $requested = isset($_POST['site_empty_cart']) || isset($_GET['empty_cart']);
    if (!$requested) {
        return;
    }

    $nonce = isset($_REQUEST['site_empty_cart_nonce']) ? wc_clean(wp_unslash($_REQUEST['site_empty_cart_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'site_empty_cart')) {
        return;
    }

    WC()->cart->empty_cart();
    wp_safe_redirect(wc_get_cart_url());
    exit;
}
add_action('wp_loaded', 'woopaca_empty_cart_maybe_handle', 20);

/**
 * Groups the catalog sorting dropdown together with a "jump to page"
 * dropdown in one right-aligned block on the shop/category product
 * listing, instead of WooCommerce's default flat, unwrapped ordering
 * dropdown. woocommerce_before_shop_loop is wrapped in a flex
 * container (.shop-toolbar, see archive-product.php) so this group
 * and the "Showing X of Y results" text sit on opposite ends of the
 * same row (see .shop-toolbar-right in woocommerce.css).
 */
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
add_action('woocommerce_before_shop_loop', 'woopaca_shop_toolbar_right', 30);

function woopaca_shop_toolbar_right()
{
    echo '<div class="shop-toolbar-right">';
    woocommerce_catalog_ordering();
    woopaca_shop_page_select();
    echo '</div>';
}

/**
 * "Jump to page" dropdown for the shop/category product listing.
 * Only rendered when there's more than one page of results. Each
 * option links via get_pagenum_link(), which preserves whatever
 * filters (price range, attributes, search) are already active in
 * the current URL.
 */
function woopaca_shop_page_select()
{
    global $wp_query;

    $total_pages = isset($wp_query->max_num_pages) ? (int) $wp_query->max_num_pages : 1;

    if ($total_pages < 2) {
        return;
    }

    $current_page = max(1, (int) get_query_var('paged'));
    ?>
    <div class="page-dropdown-container">
        <label class="screen-reader-text" for="shop-page-select">Go to page</label>
        <select id="shop-page-select" class="page-select"
            onchange="if (this.value) { window.location.href = this.value; }">
            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                <option value="<?php echo esc_url(get_pagenum_link($i)); ?>" <?php selected($i, $current_page); ?>>
                    <?php
                    /* translators: 1: current page number, 2: total number of pages */
                    printf(esc_html__('Page %1$d of %2$d', 'woopaca'), $i, $total_pages);
                    ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <?php
}

/**
 * Breadcrumbs everywhere WooCommerce renders them (product pages,
 * category/tag archives, etc.) start with "Home" linking to the site
 * root by default. Changed to "Shop", linking to whichever page is
 * configured as the WooCommerce Shop page (Settings → Products →
 * Shop page), since that's the actual top of the product listing on
 * this site. The label and the URL are two separate WooCommerce
 * filters.
 */
function woopaca_breadcrumb_home_is_shop($args)
{
    $args['home'] = 'Shop';

    return $args;
}
add_filter('woocommerce_breadcrumb_defaults', 'woopaca_breadcrumb_home_is_shop');

function woopaca_breadcrumb_home_url_is_shop($url)
{
    $shop_url = wc_get_page_permalink('shop');

    return $shop_url ? $shop_url : $url;
}
add_filter('woocommerce_breadcrumb_home_url', 'woopaca_breadcrumb_home_url_is_shop');

/**
 * Custom (non-global) product attribute filtering.
 *
 * WooCommerce's built-in filter (see the wc_get_attribute_taxonomies()
 * loop in shop-sidebar.php) only works for *global* attributes
 * (Products → Attributes), because those are real taxonomies with
 * indexed terms shared across products. An attribute added directly
 * on a single product ("custom product attribute", e.g. "Binocular
 * Width") has no such shared, query-able terms – it's just a
 * serialized value on that one product – so WooCommerce intentionally
 * doesn't support filtering by it.
 *
 * The two functions below add best-effort support for that anyway.
 * Because there's no real index to query against, matching is done
 * with a LIKE against the serialized _product_attributes postmeta
 * blob, which is slower than a proper taxonomy filter and, in rare
 * edge cases, could over-match if two different attributes on the
 * same product happen to share both a key and a value string
 * verbatim. For a store of any real size, converting these to global
 * attributes (Products → Attributes, then re-select them on each
 * product) is the more scalable, WooCommerce-native path – the
 * existing layered-nav filter in shop-sidebar.php will then pick them
 * up automatically and this fallback becomes unnecessary for them.
 */
function woopaca_get_local_product_attributes()
{
    $cached = get_transient('woopaca_local_product_attributes');
    if (false !== $cached) {
        return $cached;
    }

    global $wpdb;
    $rows = $wpdb->get_col(
        "SELECT pm.meta_value FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = '_product_attributes'
         AND p.post_type = 'product'
         AND p.post_status = 'publish'"
    );

    $attributes = array();

    foreach ($rows as $row) {
        $data = maybe_unserialize($row);
        if (!is_array($data)) {
            continue;
        }

        foreach ($data as $key => $attr) {
            if (!empty($attr['is_taxonomy'])) {
                continue; // Global attribute – already handled by shop-sidebar.php.
            }

            $key = sanitize_title($key);
            $label = isset($attr['name']) ? $attr['name'] : $key;
            $values = isset($attr['value']) ? array_map('trim', explode('|', $attr['value'])) : array();

            if (!isset($attributes[$key])) {
                $attributes[$key] = array('label' => $label, 'values' => array());
            }

            foreach ($values as $value) {
                if ('' !== $value && !in_array($value, $attributes[$key]['values'], true)) {
                    $attributes[$key]['values'][] = $value;
                }
            }
        }
    }

    foreach ($attributes as &$attribute) {
        sort($attribute['values']);
    }
    unset($attribute);

    set_transient('woopaca_local_product_attributes', $attributes, HOUR_IN_SECONDS);

    return $attributes;
}

// Keep the aggregated list fresh whenever a product is edited, instead
// of waiting up to an hour for the transient to expire.
add_action('save_post_product', function () {
    delete_transient('woopaca_local_product_attributes');
});

/**
 * Applies the ?local_attr[attribute-key][]=value query args (built by
 * the checkboxes in shop-sidebar.php) as a meta_query on the shop /
 * category / tag archive.
 */
function woopaca_filter_products_by_local_attributes($query)
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!(is_shop() || is_product_taxonomy())) {
        return;
    }

    if (empty($_GET['local_attr']) || !is_array($_GET['local_attr'])) {
        return;
    }

    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = array();
    }

    foreach ($_GET['local_attr'] as $attr_key => $values) {
        $attr_key = sanitize_title(wp_unslash($attr_key));
        $values = array_map('sanitize_text_field', (array) wp_unslash($values));
        $values = array_filter($values, 'strlen');

        if (empty($values)) {
            continue;
        }

        $value_clauses = array('relation' => 'OR');
        foreach ($values as $value) {
            // WordPress's meta_query LIKE comparison runs the value
            // through $wpdb->esc_like() before wrapping it in %...%,
            // which escapes any % we put in ourselves – so a single
            // clause with our own wildcards (e.g. '"key"%"value"%Term')
            // never actually matches anything; the % becomes a literal
            // character to search for. Instead, require two separate
            // literal substrings – the attribute key and the value
            // text – to both be present, ANDed together.
            $value_clauses[] = array(
                'relation' => 'AND',
                array(
                    'key' => '_product_attributes',
                    'value' => '"' . $attr_key . '"',
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => '_product_attributes',
                    'value' => $value,
                    'compare' => 'LIKE',
                ),
            );
        }
        $meta_query[] = $value_clauses;
    }

    if (count($meta_query) > 1 && !isset($meta_query['relation'])) {
        $meta_query['relation'] = 'AND';
    }

    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'woopaca_filter_products_by_local_attributes');
