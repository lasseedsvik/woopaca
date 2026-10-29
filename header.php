<?php
/**
 * WooPaca - header.php
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> prefix="og: https://ogp.me/ns# fb: https://ogp.me/ns/fb#">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <?php
    // 1. Set the image that SHOULD be shared on the homepage and all static pages (e.g. /om-oss)
    // Make sure this image has a good format (ideally 1200x630 pixels)
    // Priority: logo selected under Customizer > Site Settings > Logos > Header logo,
    // then the site's favicon (Site Icon) at its highest available resolution,
    // then fb-logo.png as the final fallback.
    $fb_image = get_template_directory_uri() . '/assets/images/fb-logo.png';
    // Real pixel size of whichever image ends up in $fb_image (0 = unknown,
    // in which case og:image:width/height are left out rather than
    // claiming 1200×630 for e.g. a 512×512 favicon).
    $fb_image_w = 0;
    $fb_image_h = 0;
    $fb_logo_size = @getimagesize(get_template_directory() . '/assets/images/fb-logo.png');
    if ($fb_logo_size) {
        $fb_image_w = (int) $fb_logo_size[0];
        $fb_image_h = (int) $fb_logo_size[1];
    }

    $site_icon_id = get_option('site_icon');
    if ($site_icon_id) {
        $best_url = false;
        $best_width = 0;
        $best_height = 0;

        // The original uploaded site icon (usually the largest version available).
        $full_src = wp_get_attachment_image_src($site_icon_id, 'full');
        if ($full_src) {
            $best_url = $full_src[0];
            $best_width = (int) $full_src[1];
            $best_height = (int) $full_src[2];
        }

        // WordPress also generates several smaller cropped sizes (32, 180, 192, 270...)
        // for the site icon. Check those too, in case one of them is somehow larger
        // than what "full" reports.
        $metadata = wp_get_attachment_metadata($site_icon_id);
        if (!empty($metadata['sizes']) && !empty($metadata['file'])) {
            $upload_dir = wp_get_upload_dir();
            $base_url = trailingslashit($upload_dir['baseurl']) . trailingslashit(dirname($metadata['file']));

            foreach ($metadata['sizes'] as $size) {
                if (!empty($size['width']) && !empty($size['file']) && (int) $size['width'] > $best_width) {
                    $best_width = (int) $size['width'];
                    $best_height = !empty($size['height']) ? (int) $size['height'] : 0;
                    $best_url = $base_url . $size['file'];
                }
            }
        }

        if ($best_url) {
            $fb_image = $best_url;
            $fb_image_w = $best_width;
            $fb_image_h = $best_height;
        }
    }

    $site_header_logo = get_theme_mod('header_logo');
    if ($site_header_logo) {
        $fb_image = $site_header_logo;
        $fb_image_w = 0;
        $fb_image_h = 0;
        $logo_id = attachment_url_to_postid($site_header_logo);
        $logo_src = $logo_id ? wp_get_attachment_image_src($logo_id, 'full') : false;
        if ($logo_src) {
            $fb_image_w = (int) $logo_src[1];
            $fb_image_h = (int) $logo_src[2];
        }
    }

    // 3. Set a default description (shown on the homepage/pages without their own excerpt)
    $fb_description = get_bloginfo('description');

    // 2. Check if this is a single BLOG POST.
    // By using is_singular('post') instead of is_singular() we don't touch the homepage or regular pages.
    if (is_singular('post')) {
        global $post;

        // Get the ID of the blog post's own featured image
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            // Get the full-size image URL
            $image_src = wp_get_attachment_image_src($thumbnail_id, 'full');

            if ($image_src) {
                $fb_image = $image_src[0]; // Replace the default image with the blog post's image
                $fb_image_w = (int) $image_src[1];
                $fb_image_h = (int) $image_src[2];
            }
        }

        // Get the description from the excerpt, otherwise from the post content
        if (has_excerpt($post->ID)) {
            $fb_description = wp_strip_all_tags(get_the_excerpt($post->ID));
        } else {
            $fb_description = wp_trim_words(wp_strip_all_tags($post->post_content), 35, '...');
        }
    } elseif (is_singular()) {
        // Regular pages (e.g. /om-oss): use the excerpt if there is one, otherwise the page content
        global $post;
        if (has_excerpt($post->ID)) {
            $fb_description = wp_strip_all_tags(get_the_excerpt($post->ID));
        } elseif (!empty($post->post_content)) {
            $fb_description = wp_trim_words(wp_strip_all_tags($post->post_content), 35, '...');
        }
    }

    // Product pages: use the product's own first/main image for the shared image,
    // overriding whatever $fb_image was set to above (favicon or fb-logo.png fallback).
    if (function_exists('is_product') && is_product()) {
        global $post;
        $woopaca_product = wc_get_product($post->ID);

        if ($woopaca_product) {
            $product_image_id = $woopaca_product->get_image_id(); // ID of the product's first/main image

            if ($product_image_id) {
                $product_image_src = wp_get_attachment_image_src($product_image_id, 'full');

                if ($product_image_src) {
                    $fb_image = $product_image_src[0];
                    $fb_image_w = (int) $product_image_src[1];
                    $fb_image_h = (int) $product_image_src[2];
                }
            }
        }
    }

    // Facebook App ID. Replace the value below with your real App ID from
    // https://developers.facebook.com/apps/ (required by Facebook's sharing debugger).
    $fb_app_id = ''; // Enter your real Facebook App ID here once you've created one
    ?>
    <meta property="og:type" content="<?php echo is_singular() ? 'article' : 'website'; ?>" />
    <meta property="og:title" content="<?php wp_title('|', true, 'right');
    bloginfo('name'); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($fb_description); ?>" />
    <meta property="og:image" content="<?php echo esc_url($fb_image); ?>" />
    <?php if ($fb_image_w && $fb_image_h) : ?>
        <meta property="og:image:width" content="<?php echo (int) $fb_image_w; ?>" />
        <meta property="og:image:height" content="<?php echo (int) $fb_image_h; ?>" />
    <?php endif; ?>
    <?php
    // get_permalink() is only right on single posts/pages/products. On
    // the shop, category pages, the blog listing and search it returns
    // the FIRST post/product in the loop, so sharing a category page on
    // Facebook showed (and linked to) a random product instead.
    global $wp;
    $og_url = is_singular() ? get_permalink() : home_url(add_query_arg(array(), $wp->request ? trailingslashit($wp->request) : '/'));
    ?>
    <meta property="og:url" content="<?php echo esc_url($og_url); ?>" />
    <?php if (!empty($fb_app_id)): ?>
        <meta property="fb:app_id" content="<?php echo esc_attr($fb_app_id); ?>" />
    <?php endif; ?>


    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <a class="skip-link screen-reader-text" href="#woopaca-main-content"><?php esc_html_e('Skip to content', 'woopaca'); ?></a>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-inner">
            <div class="header-left">
                <button aria-label="<?php esc_attr_e('Open menu', 'woopaca'); ?>" aria-expanded="false" class="menu-toggle">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <?php $header_logo = get_theme_mod('header_logo'); ?>
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <?php if ($header_logo) : ?>
                        <img src="<?php echo esc_url($header_logo); ?>"
                            alt="<?php bloginfo('name'); ?>" class="logo-main">
                    <?php endif; ?>
                </a>
            </div>
            <nav class="main-navigation">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_class' => 'nav-list',
                    'container' => false,
                    'walker' => new woopaca_Nav_Menu_Walker(),
                    'fallback_cb' => function () {
                        echo '<ul class="nav-list">';
                        wp_list_pages(array(
                            'title_li' => '',
                            'walker' => new woopaca_Nav_Link_Page_Walker(),
                        ));
                        echo '</ul>';
                    },
                ));
                ?>
            </nav>
            <div class="header-right">
                <?php if (class_exists('WooCommerce')): ?>
                    <div class="header-search">
                        <button type="button" class="header-icon-link header-search-toggle" aria-label="<?php esc_attr_e('Search', 'woopaca'); ?>"
                            aria-expanded="false">
                            <span class="material-symbols-outlined header-search-icon">search</span>
                        </button>

                        <div class="header-search-panel" hidden>
                            <form role="search" method="get" class="header-search-form"
                                action="<?php echo esc_url(home_url('/')); ?>">
                                <input type="search" name="s" class="header-search-input" autocomplete="off"
                                    placeholder="<?php esc_attr_e('Search products…', 'woopaca'); ?>" aria-label="<?php esc_attr_e('Search products', 'woopaca'); ?>">
                                <input type="hidden" name="post_type" value="product">
                                <button type="button" class="header-search-close" aria-label="<?php esc_attr_e('Close search', 'woopaca'); ?>">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </form>
                            <div class="header-search-results" hidden></div>
                        </div>
                    </div>

                    <?php $myaccount_page_id = wc_get_page_id('myaccount'); ?>
                    <a href="<?php echo esc_url($myaccount_page_id > 0 ? get_permalink($myaccount_page_id) : home_url('/')); ?>"
                        class="header-icon-link account-icon-link" aria-label="<?php esc_attr_e('My account', 'woopaca'); ?>">
                        <span class="material-symbols-outlined">person</span>
                    </a>
                    <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="header-icon-link cart-icon-link"
                        aria-label="<?php esc_attr_e('Cart', 'woopaca'); ?>">
                        <span class="material-symbols-outlined">shopping_bag</span>
                        <span
                            class="cart-count"><?php echo esc_html(WC()->cart ? WC()->cart->get_cart_contents_count() : 0); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/logo.png" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                            class="logo-woopaca-small">
                    </a>
                    <button class="shop-button" onclick="location.href='<?php echo esc_url(home_url('/')); ?>'">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        <?php esc_html_e('Visit the shop', 'woopaca'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </header>