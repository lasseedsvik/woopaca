<?php
/**
 * functions.php
 */

/**
 * WooCommerce support (theme support, own stylesheet, own templates
 * etc.) is broken out into its own file for clarity.
 */
require get_template_directory() . '/inc/woocommerce.php';

function woopaca_setup()
{
    register_nav_menus(array(
        'primary' => esc_html__('Mainmenu', 'woopaca'),
    ));
    add_theme_support('title-tag');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
    add_theme_support('editor-styles');
    add_editor_style('editor-style.css');

    // Used for the category banner image on shop/category pages (see
    // woocommerce/archive-product.php). Hard-cropped (true) so any
    // image uploaded as a category thumbnail – whatever its original
    // dimensions – is consistently cropped to this 1376×768 canvas
    // instead of being left at WordPress's arbitrary "large" size.
    add_image_size('woopaca_category_hero', 1376, 768, true);
}
add_action('after_setup_theme', 'woopaca_setup');


/**
 * Add Google Analytics dynamically if an ID has been provided.
 * The ID is now entered under Appearance → Customize → Google
 * Analytics in wp-admin (see woopaca_customize_register further down),
 * not hardcoded here in the code.
 */
function woopaca_add_google_analytics()
{
    $ga_id = get_theme_mod('google_analytics_id', '');

    if (!empty($ga_id)) {
        ?>

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());

            gtag('config', '<?php echo esc_js($ga_id); ?>');
        </script>
        <?php
    }
}
add_action('wp_head', 'woopaca_add_google_analytics', 10);

/**
 * Cache-busting version for the theme's own files: the file's last
 * modified time is used as a version parameter (?ver=...). This makes
 * sure the browser/cache plugin/CDN always fetches a fresh copy as
 * soon as a file is updated, instead of risking that an old, cached
 * CSS or JS file keeps being shown after a theme update.
 */
function woopaca_asset_version($relative_path)
{
    $file = get_template_directory() . $relative_path;
    return file_exists($file) ? filemtime($file) : '1.0';
}

function woopaca_scripts()
{
    wp_enqueue_style('woopaca-style', get_stylesheet_uri(), array(), woopaca_asset_version('/style.css'));
    wp_enqueue_style('woopaca-fonts', 'https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500;600;700&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lexend:wght@100..900&display=swap');
    wp_enqueue_style('woopaca-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block');
    wp_enqueue_script(
        'woopaca-menu',
        get_template_directory_uri() . '/assets/js/menu.js',
        array(),
        woopaca_asset_version('/assets/js/menu.js'),
        true
    );
    wp_enqueue_script(
        'woopaca-menu-toggle',
        get_template_directory_uri() . '/assets/js/menu-toggle.js',
        array(),
        woopaca_asset_version('/assets/js/menu-toggle.js'),
        true
    );
    wp_enqueue_script(
        'woopaca-reveal',
        get_template_directory_uri() . '/assets/js/reveal.js',
        array(),
        woopaca_asset_version('/assets/js/reveal.js'),
        true
    );

    // Remove WordPress injected styles
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'woopaca_scripts');

// Remove global styles injected inline by WordPress
remove_action('wp_head', 'wp_enqueue_global_styles');
remove_action('wp_body_open', 'wp_enqueue_global_styles');
remove_action('wp_footer', 'wp_enqueue_global_styles');


remove_filter('the_content', 'wpautop');
remove_filter('the_excerpt', 'wpautop');


function woopaca_cookie_banner()
{
    if (!isset($_COOKIE['site_cookie_consent'])) {

        $cookie_text = get_theme_mod('cookie_banner_text', 'We are using cookies fo improve your experience!');
        $link_text = get_theme_mod('cookie_banner_link_text', 'Read more');
        $link_url = get_theme_mod('cookie_banner_link_url', '/privacy-policy');
        $accept_text = get_theme_mod('cookie_banner_accept_text', 'Acceptera');
        $decline_text = get_theme_mod('cookie_banner_decline_text', 'Neka');
        ?>
        <div id="cookie-banner"
            style="position:fixed;bottom:0;left:0;right:0;z-index:9999;background:#0d0d0d;color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid rgba(255,255,255,0.1);">
            <p style="margin:0;font-size:14px;"><?php echo nl2br(esc_html($cookie_text)); ?>
                <?php if (!empty($link_text) && !empty($link_url)) : ?>
                    <a href="<?php echo esc_url($link_url); ?>" style="color:#d1a12e;"><?php echo esc_html($link_text); ?></a>
                <?php endif; ?>
            </p>
            <div style="display:flex;gap:10px;">
                <button onclick="dmAcceptCookies()"
                    style="background:#d1a12e;color:#0d0d0d;border:none;padding:10px 20px;border-radius:3px;cursor:pointer;font-size:14px;font-weight:700;white-space:nowrap;">
                    <?php echo esc_html($accept_text); ?>
                </button>
                <button onclick="dmDeclineCookies()"
                    style="background:transparent;color:#fff;border:1px solid #fff;padding:10px 20px;border-radius:3px;cursor:pointer;font-size:14px;white-space:nowrap;">
                    <?php echo esc_html($decline_text); ?>
                </button>
            </div>
        </div>
        <script>
            function dmAcceptCookies() {
                document.cookie = "site_cookie_consent=1;path=/;max-age=" + (60 * 60 * 24 * 365);
                document.getElementById('cookie-banner').style.display = 'none';
            }
            function dmDeclineCookies() {
                document.cookie = "site_cookie_consent=0;path=/;max-age=" + (60 * 60 * 24 * 365);
                document.getElementById('cookie-banner').style.display = 'none';
            }
        </script>
        <?php
    }
}
add_action('wp_footer', 'woopaca_cookie_banner');

/**
 * Groups every shop-related Customizer section ("Homepage shop
 * section", "Shop filters", "Shop layout", "Cart page") under a
 * single "WooPaca Shop Settings" panel instead of leaving them loose
 * at the top level. Also groups the general site sections ("Logos",
 * "Error page (404)", "Cookie banner", "Google Analytics", "Footer
 * information") under a separate "WooPaca Site settings" panel. Both
 * run at priority 5 – before the section registrations below and in
 * inc/woocommerce.php – so the panels always exist by the time a
 * section asks to join one.
 */
function woopaca_register_shop_settings_panel(WP_Customize_Manager $wp_customize)
{
    $wp_customize->add_panel('shop_settings', array(
        'title' => 'WooPaca Shop Settings',
        'description' => 'Homepage shop section, filters, product grid layout, and the cart page.',
        'priority' => 25,
    ));

    $wp_customize->add_panel('woopaca_site_settings', array(
        'title' => 'WooPaca Site Settings',
        'description' => 'Logos, the 404 page, the cookie banner, Google Analytics, and footer information.',
        'priority' => 24,
    ));
}
add_action('customize_register', 'woopaca_register_shop_settings_panel', 5);

/**
 * Cookie banner text – editable in Appearance → Customize → Cookie
 * banner, instead of being hardcoded in woopaca_cookie_banner() above.
 */
function woopaca_customize_register_cookie_banner(WP_Customize_Manager $wp_customize)
{
    $wp_customize->add_section('cookie_banner_settings', array(
        'title' => 'Cookie Banner',
        'panel' => 'woopaca_site_settings',
        'priority' => 20,
    ));

    $wp_customize->add_setting('cookie_banner_text', array(
        'default' => 'We are using cookies to improve your experience!',
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('cookie_banner_text', array(
        'label' => 'Message text',
        'section' => 'cookie_banner_settings',
        'type' => 'textarea',
    ));

    $wp_customize->add_setting('cookie_banner_link_text', array(
        'default' => 'Read more',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cookie_banner_link_text', array(
        'label' => 'Link text',
        'description' => 'Leave the text or URL empty to hide the link entirely.',
        'section' => 'cookie_banner_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('cookie_banner_link_url', array(
        'default' => '/integritetspolicy',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cookie_banner_link_url', array(
        'label' => 'Link URL',
        'section' => 'cookie_banner_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('cookie_banner_accept_text', array(
        'default' => 'Acceptera',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cookie_banner_accept_text', array(
        'label' => 'Accept button text',
        'section' => 'cookie_banner_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('cookie_banner_decline_text', array(
        'default' => 'Neka',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cookie_banner_decline_text', array(
        'label' => 'Decline button text',
        'section' => 'cookie_banner_settings',
        'type' => 'text',
    ));
}
add_action('customize_register', 'woopaca_customize_register_cookie_banner');


function woopaca_meta_description()
{
    if (is_front_page()) {
        $description = '';
    } elseif (is_singular()) {
        $description = get_the_excerpt();
    } elseif (is_category() || is_tag()) {
        $description = strip_tags(category_description());
    } else {
        $description = get_bloginfo('description');
    }

    if (!empty($description)) {
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '">' . "\n";
    }
}
add_action('wp_head', 'woopaca_meta_description');

/**
 * Custom walker that outputs the correct <ul><li> structure
 * so the CSS dropdowns and mobile menu work.
 */
class woopaca_Nav_Menu_Walker extends Walker_Nav_Menu
{

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $classes = array('nav-item');
        if ($depth === 0) {
            $classes[] = 'nav-item--top';
        } else {
            $classes[] = 'nav-item--child';
        }

        // WordPress marks parent items by adding 'menu-item-has-children'
        // to $item->classes during wp_nav_menu() – there's no
        // $args->has_children property (that object only holds the
        // wp_nav_menu() call's own arguments, not per-item data), so
        // checking $args here would always be false and the dropdown
        // arrow below would never actually render.
        $has_children = in_array('menu-item-has-children', (array) $item->classes, true);
        if ($has_children) {
            $classes[] = 'nav-item--has-children';
        }

        $output .= '<li class="' . implode(' ', $classes) . '">';
        $output .= '<a class="nav-link" href="' . esc_attr($item->url) . '">';
        $output .= esc_html($item->title);
        // Only top-level items get the visible dropdown arrow – nested
        // submenu items with their own children (rare, but possible)
        // don't need one, since submenus in this theme only expand on
        // hover/click at the top level.
        if ($has_children && $depth === 0) {
            $output .= '<span class="material-symbols-outlined nav-dropdown-arrow" aria-hidden="true">expand_more</span>';
        }
        $output .= '</a>';
    }

    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= '</li>';
    }

    function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '<ul class="sub-menu">';
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= '</ul>';
    }
}

/**
 * Custom walker for wp_list_pages() (used in wp_nav_menu's fallback_cb
 * when no menu has been selected under Appearance > Menus).
 * Outputs the same <li>/<a> structure and CSS classes as woopaca_Nav_Menu_Walker
 * above, so the design and dropdown CSS work identically.
 */
class woopaca_Nav_Link_Page_Walker extends Walker_Page
{

    function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0)
    {
        $classes = array('nav-item');
        if ($depth === 0) {
            $classes[] = 'nav-item--top';
        } else {
            $classes[] = 'nav-item--child';
        }
        $output .= '<li class="' . implode(' ', $classes) . '">';
        $output .= '<a class="nav-link" href="' . esc_attr(get_page_link($page->ID)) . '">';
        $output .= esc_html($page->post_title);
        $output .= '</a>';
    }

    function end_el(&$output, $page, $depth = 0, $args = array())
    {
        $output .= '</li>';
    }

    function start_lvl(&$output, $depth = 0, $args = array())
    {
        $output .= '<ul class="sub-menu">';
    }

    function end_lvl(&$output, $depth = 0, $args = array())
    {
        $output .= '</ul>';
    }
}

// Footer Customizer Settings
function woopaca_customize_register(WP_Customize_Manager $wp_customize)
{

    $wp_customize->add_section('google_analytics', array(
        'title' => 'Google Analytics',
        'panel' => 'woopaca_site_settings',
        'priority' => 40,
    ));

    $wp_customize->add_setting('google_analytics_id', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('google_analytics_id', array(
        'label' => 'Google Analytics ID',
        'description' => 'Paste the full Measurement ID from Google Analytics (e.g. G-XXXXXXXXXX). Leave this field empty to disable Google Analytics entirely.',
        'section' => 'google_analytics',
        'type' => 'text',
    ));

    // Logotyper (header/footer). Inget standardvärde sätts – om inget
    // laddas upp visas ingen bild alls istället för en trasig bild-
    // ikon (se header.php/footer.php, som bara skriver ut <img> om ett
    // värde faktiskt finns).
    $wp_customize->add_section('site_logos', array(
        'title' => 'Logos',
        'panel' => 'woopaca_site_settings',
        'priority' => 10,
    ));

    $wp_customize->add_setting('header_logo', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'header_logo', array(
        'label' => 'Header logo',
        'section' => 'site_logos',
    )));

    $wp_customize->add_section('footer_info', array(
        'title' => 'Footer Settings',
        'panel' => 'woopaca_site_settings',
        'priority' => 50,
    ));

    $wp_customize->add_setting('footer_logo', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'footer_logo', array(
        'label' => 'Footer logo',
        'section' => 'footer_info',
    )));

    $wp_customize->add_setting('footer_hours_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_hours_heading', array(
        'label' => 'Opening hours heading',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_links_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_links_heading', array(
        'label' => 'Info links heading (Privacy Policy / Refund & Returns)',
        'description' => 'Falls back to "Information" if left empty. The section only appears at all once at least one of those pages is set and published (Settings → Privacy for the Privacy Policy page, WooCommerce → Settings → Advanced for the Refund and Returns page).',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_social_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_social_heading', array(
        'label' => 'Follow us heading',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_address', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_address', array(
        'label' => 'Address',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_phone', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_phone', array(
        'label' => 'Phone',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_email', array(
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('footer_email', array(
        'label' => 'Email',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_orgnr', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_orgnr', array(
        'label' => 'Company registration number',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_hours_weekdays', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_hours_weekdays', array(
        'label' => 'Opening hours Monday - Friday',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_hours_saturday', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_hours_saturday', array(
        'label' => 'Opening hours Saturday',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_hours_sunday', array(
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_hours_sunday', array(
        'label' => 'Opening hours Sunday',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    $wp_customize->add_setting('footer_facebook_url', array(
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('footer_facebook_url', array(
        'label' => 'Facebook link',
        'section' => 'footer_info',
        'type' => 'url',
    ));

    $wp_customize->add_setting('footer_facebook_qr', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'footer_facebook_qr', array(
        'label' => 'Facebook QR code',
        'section' => 'footer_info',
    )));

    $wp_customize->add_setting('footer_bottom_text', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('footer_bottom_text', array(
        'label' => 'Copyright text (bottom of footer)',
        'description' => '"© [year]" is always added automatically before this text.',
        'section' => 'footer_info',
        'type' => 'text',
    ));

    // Store image
    $wp_customize->add_setting('footer_store_image', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'footer_store_image', array(
        'label' => 'Store photo',
        'section' => 'footer_info',
    )));

    // Bloggsida (home.php): avatar + välkomsttext högst upp på
    // blogglistan, samt startsidans bloggsektion (front-page.php).
    $wp_customize->add_section('front_shop_settings', array(
        'title' => 'Homepage section',
        'panel' => 'shop_settings',
        'priority' => 10,
    ));

    $wp_customize->add_setting('front_shop_eyebrow', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_shop_eyebrow', array(
        'label' => 'Small label above the heading',
        'description' => 'Currently "Nyheter".',
        'section' => 'front_shop_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('front_shop_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_shop_heading', array(
        'label' => 'Heading',
        'description' => 'Currently "Från butiken".',
        'section' => 'front_shop_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('front_shop_link_text', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_shop_link_text', array(
        'label' => '"View shop" link text',
        'description' => 'Currently "Visa hela butiken".',
        'section' => 'front_shop_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('front_shop_product_count', array(
        'default' => 6,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('front_shop_product_count', array(
        'label' => 'Number of products to show',
        'description' => 'How many products appear in this section. Defaults to 6.',
        'section' => 'front_shop_settings',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 1,
            'max' => 24,
            'step' => 1,
        ),
    ));

    $wp_customize->add_setting('front_shop_source', array(
        'default' => 'recent',
        'sanitize_callback' => 'sanitize_key',
    ));
    $wp_customize->add_control('front_shop_source', array(
        'label' => 'Which products to show',
        'section' => 'front_shop_settings',
        'type' => 'radio',
        'choices' => array(
            'recent' => 'Most recently added products',
            'category' => 'Products from a specific category',
        ),
    ));

    // Choices are built from whatever product categories exist at the
    // time the Customizer loads (class_exists() guards against
    // WooCommerce being inactive).
    $product_cat_choices = array('' => '— Select a category —');
    if (class_exists('WooCommerce')) {
        $product_cats = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        if (!is_wp_error($product_cats)) {
            foreach ($product_cats as $product_cat) {
                $product_cat_choices[$product_cat->slug] = $product_cat->name;
            }
        }
    }
    $wp_customize->add_setting('front_shop_category', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_shop_category', array(
        'label' => 'Category',
        'description' => 'Only used when "Products from a specific category" is selected above.',
        'section' => 'front_shop_settings',
        'type' => 'select',
        'choices' => $product_cat_choices,
        'active_callback' => function ($control) {
            return 'category' === $control->manager->get_setting('front_shop_source')->value();
        },
    ));

    // Filterpanelen på arkiv-/kategorisidan (woocommerce/shop-sidebar.php).
    $wp_customize->add_section('shop_filter_settings', array(
        'title' => 'Filters',
        'panel' => 'shop_settings',
        'priority' => 20,
    ));

    $wp_customize->add_setting('shop_category_filter_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('shop_category_filter_label', array(
        'label' => 'Categories heading',
        'description' => 'Shown above the category list in the shop sidebar. Falls back to "Categories" if left empty.',
        'section' => 'shop_filter_settings',
        'type' => 'text',
        'priority' => 5,
    ));

    $wp_customize->add_setting('shop_price_filter_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('shop_price_filter_label', array(
        'label' => 'Price filter heading',
        'description' => 'Shown above the price slider in the shop sidebar. Falls back to "Filter by price" if left empty.',
        'section' => 'shop_filter_settings',
        'type' => 'text',
    ));

    // Inget standardvärde på avatarbilden – se home.php som bara
    // skriver ut <img> om ett värde faktiskt finns.
    $wp_customize->add_section('blog_settings', array(
        'title' => 'WooPaca Blog Settings',
        'priority' => 27,
    ));

    // "Empty cart" button on the cart page (see woopaca_empty_cart_button_classic()
    // / woopaca_empty_cart_button_block_fallback() in inc/woocommerce.php).
    $wp_customize->add_section('cart_settings', array(
        'title' => 'Cart Page',
        'panel' => 'shop_settings',
        'priority' => 40,
    ));

    $wp_customize->add_setting('cart_empty_button_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cart_empty_button_label', array(
        'label' => '"Empty cart" button text',
        'description' => 'Falls back to "Empty cart!" if left empty.',
        'section' => 'cart_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('cart_empty_confirm_text', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('cart_empty_confirm_text', array(
        'label' => '"Empty cart" confirmation dialog text',
        'description' => 'Shown in the browser\'s confirm popup before the cart is actually emptied. Falls back to "Are you sure you want to empty the cart?" if left empty.',
        'section' => 'cart_settings',
        'type' => 'text',
    ));

    $wp_customize->add_section('error_404_settings', array(
        'title' => 'Error Page (404)',
        'panel' => 'woopaca_site_settings',
        'priority' => 30,
    ));

    $wp_customize->add_setting('error_404_page_id', array(
        'default' => 0,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control('error_404_page_id', array(
        'label' => '404 page (page not found)',
        'description' => 'Leave on "None" to keep the theme\'s built-in 404 message and buttons (see 404.php). If a page is picked here, that page\'s own title and content are shown instead, using this page\'s regular design (header/footer).',
        'section' => 'error_404_settings',
        'type' => 'dropdown-pages',
    ));

    $wp_customize->add_setting('blog_avatar', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'blog_avatar', array(
        'label' => 'Avatar image',
        'description' => 'Only shown on the blog page itself. Expanding this section switches the preview on the right to the blog page so you can see the change.',
        'section' => 'blog_settings',
    )));

    $wp_customize->add_setting('blog_welcome_heading', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('blog_welcome_heading', array(
        'label' => 'Heading next to the avatar',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('blog_welcome_text', array(
        'default' => '',
        'transport' => 'postMessage',
        'sanitize_callback' => 'wp_kses_post',
    ));
    $wp_customize->add_control('blog_welcome_text', array(
        'label' => 'Text below the heading',
        'description' => 'A blank line between paragraphs starts a new paragraph.',
        'section' => 'blog_settings',
        'type' => 'textarea',
    ));

    $wp_customize->add_setting('front_blog_section_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_blog_section_heading', array(
        'label' => 'Heading for the blog section on the homepage',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('front_blog_section_link_text', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('front_blog_section_link_text', array(
        'label' => 'Link text to the full blog (bottom of the section)',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('blog_post_published_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('blog_post_published_label', array(
        'label' => 'Text before the publish date (below blog posts)',
        'description' => 'E.g. "Published:". The date is always appended automatically after this text.',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('blog_post_back_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('blog_post_back_label', array(
        'label' => 'Text for the "Back" link (below blog posts)',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('blog_sidebar_see_all_label', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('blog_sidebar_see_all_label', array(
        'label' => '"See all posts" link text (blog post sidebar)',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

    $wp_customize->add_setting('blog_sidebar_heading', array(
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('blog_sidebar_heading', array(
        'label' => 'Heading above the post list (blog post sidebar)',
        'section' => 'blog_settings',
        'type' => 'text',
    ));

}
add_action('customize_register', 'woopaca_customize_register');

/**
 * Blog page Customizer live preview.
 *
 * The avatar/heading/text settings above only ever show up on the
 * blog page (home.php) - never on the front page, which is what the
 * Customizer opens by default. Without help, that makes it look like
 * "selecting an avatar image doesn't do anything": you're simply
 * looking at a page that was never going to show it.
 *
 * This fixes it in two parts:
 * 1. customizer-controls.js switches the preview pane to the blog
 *    page automatically whenever the "Blog page" section is opened
 *    (and back to the page you were on when it's closed).
 * 2. customizer-preview.js, together with the postMessage transport
 *    set on those settings above, updates the avatar image / heading
 *    / text live inside the preview the moment you change them - no
 *    full page reload needed - the same way the Customizer's own
 *    site title/tagline fields behave.
 */
function woopaca_customize_controls_scripts()
{
    $blog_page_id = get_option('page_for_posts');
    $blog_page_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blogg');

    wp_enqueue_script(
        'woopaca-customizer-controls',
        get_template_directory_uri() . '/assets/js/customizer-controls.js',
        array('customize-controls'),
        woopaca_asset_version('/assets/js/customizer-controls.js'),
        true
    );

    wp_localize_script('woopaca-customizer-controls', 'woopacaCustomizer', array(
        'blogPageUrl' => esc_url_raw($blog_page_url),
    ));
}
add_action('customize_controls_enqueue_scripts', 'woopaca_customize_controls_scripts');

function woopaca_customize_preview_scripts()
{
    wp_enqueue_script(
        'woopaca-customizer-preview',
        get_template_directory_uri() . '/assets/js/customizer-preview.js',
        array('customize-preview'),
        woopaca_asset_version('/assets/js/customizer-preview.js'),
        true
    );
}
add_action('customize_preview_init', 'woopaca_customize_preview_scripts');