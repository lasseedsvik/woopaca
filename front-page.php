<?php get_header(); ?>

<div id="site-wrap">
    <main id="woopaca-main-content" class="site-main">
        <?php
        if (have_posts()):
            while (have_posts()):
                the_post();
                the_content();
            endwhile;
        endif;
        ?>

        <?php if (class_exists('WooCommerce')):
            $front_shop_query_args = array(
                'post_type' => 'product',
                'posts_per_page' => (int) get_theme_mod('front_shop_product_count', 6),
                'post_status' => 'publish',
            );

            $front_shop_category = ('category' === get_theme_mod('front_shop_source', 'recent'))
                ? get_theme_mod('front_shop_category', '')
                : '';

            if ($front_shop_category) {
                $front_shop_query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $front_shop_category,
                    ),
                );
            }

            $front_page_products = new WP_Query($front_shop_query_args);

            if ($front_page_products->have_posts()): ?>
                <section class="front-shop-section">
                    <div class="container">
                        <div class="front-shop-heading">
                            <div>
                                <span
                                    class="section-eyebrow"><?php echo esc_html(get_theme_mod('front_shop_eyebrow', 'News')); ?></span>
                                <h1><?php echo esc_html(get_theme_mod('front_shop_heading', 'From the store')); ?></h1>
                            </div>

                        </div>

                        <ul class="products">
                            <?php
                            while ($front_page_products->have_posts()):
                                $front_page_products->the_post();
                                global $product;
                                $product = wc_get_product(get_the_ID());
                                wc_get_template_part('content', 'product');
                            endwhile;
                            ?>
                        </ul>
                        <h2 class="front-shop-link-header">
                            <a class="front-shop-link" href="<?php echo esc_url(get_post_type_archive_link('product')); ?>">
                                <?php echo esc_html(get_theme_mod('front_shop_link_text', 'Show all products')); ?>
                            </a>
                        </h2>
                    </div>
                </section>
                <?php
                wp_reset_postdata();
            endif;
        endif; ?>

        <div class="post-container-front-page">

            <?php
            // The WordPress "Posts page" setting (Settings > Reading)
            // is the actual source of truth for where the blog lives –
            // reading it here means this never has to be updated by
            // hand if that page is ever renamed or moved.
            $blog_page_id = get_option('page_for_posts');
            $blog_page_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blogg');
            ?>

            <h1 style="margin-bottom: 20px"><img alt="" class="logo-main icon"
                    src="<?php echo get_template_directory_uri(); ?>/assets/images/blog.svg">
                <?php echo esc_html(get_theme_mod('front_blog_section_heading', 'Latest from the blog!')); ?>
            </h1>

            <?php
            $latest_posts = new WP_Query(array(
                'posts_per_page' => 5,
            ));

            if ($latest_posts->have_posts()):
                while ($latest_posts->have_posts()):
                    $latest_posts->the_post(); ?>

                    <div class="latest-post reveal-on-scroll">
                        <div class="latest-post-image">
                            <?php if (has_post_thumbnail()): ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium'); ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="latest-post-content">
                            <h2><a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a></h2>
                            <p>
                                <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                            </p>
                        </div>
                    </div>

                <?php endwhile;
                wp_reset_postdata();
            endif;
            ?>
            <h3><a href="<?php echo esc_url($blog_page_url); ?>"><img alt="" class="logo-main icon"
                        src="<?php echo get_template_directory_uri(); ?>/assets/images/blog.svg">
                    <?php echo esc_html(get_theme_mod('front_blog_section_link_text', 'See all posts here!')); ?></a>
            </h3>
        </div>
    </main>
</div>

<?php get_footer(); ?>