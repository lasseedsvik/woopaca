<?php get_header(); ?>

<div id="site-wrap">
    <main id="woopaca-main-content" class="site-main">
        <?php

        global $wp_query;

        // Check if there's more than 1 page in total        
        $total_pages = $wp_query->max_num_pages;

        if ($total_pages > 1) {
            // Get the current page number
        
            $current_page = max(1, get_query_var('paged'));
            ?>

            <div class="page-dropdown-container">
                <label for="page-select" class="screen-reader-text"><?php esc_html_e('Choose page', 'woopaca'); ?></label>
                <select id="page-select" onchange="if (this.value) window.location.href = this.value;">

                    <option value=""><?php esc_html_e('Go to page…', 'woopaca'); ?></option>
                    <?php
                    for ($i = 1; $i <= $total_pages; $i++) {

                        // Build the link for each page number                
                        $page_link = get_pagenum_link($i);

                        // Mark the page we're currently on as 'selected'                
                        $selected = ($i == $current_page) ? 'selected' : '';

                        echo '<option value="' . esc_url($page_link) . '" ' . $selected . '>' . esc_html(sprintf(__('Page %d', 'woopaca'), $i)) . '</option>';
                    }

                    ?>
                </select>
            </div>
            <?php
        }
        ?>

        <?php if (is_home() && !is_paged()): ?>

            <div class="welcome-box">

                <?php $blog_avatar = get_theme_mod('blog_avatar'); ?>
                <?php if ($blog_avatar) : ?>
                    <img id="blog-avatar-img" alt="" class="logo-main blog-avatar"
                        src="<?php echo esc_url($blog_avatar); ?>">
                <?php endif; ?>

                <div class="welcome-text">
                    <h2 id="blog-welcome-heading"><?php echo esc_html(woopaca_mod('blog_welcome_heading', __('Welcome to the blog!', 'woopaca'))); ?></h2>

                    <div id="blog-welcome-text">
                        <?php
                        $welcome_text = get_theme_mod('blog_welcome_text');

                        if ($welcome_text) {
                            echo wp_kses_post(wpautop($welcome_text));
                        } else {
                            ?>
                            <p><?php esc_html_e('Here you\'ll find our latest blog posts!', 'woopaca'); ?></p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php if (have_posts()):

            while (have_posts()):

                the_post(); ?>
                <article class="post-card">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="post-card-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php
                                // 'list-thumbnail' was never registered, so
                                // WordPress fell back to the full-size
                                // original upload for every card.
                                the_post_thumbnail('medium_large');
                                ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="post-card-content">
                        <div class="post-card-date">

                            [<?php the_time(get_option('date_format')); ?>]

                        </div>

                        <h2 class="post-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>

                        <div class="post-card-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    </div>
                </article>

            <?php endwhile; ?>

            <nav class="pagination-container">
                <?php
                the_posts_pagination(array(
                    'mid_size' => 2,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'woopaca'),
                    'next_text' => esc_html__('Next', 'woopaca') . ' &raquo;',
                ));

                ?>
            </nav>

        <?php endif; ?>
    </main>
</div>

<?php

get_footer();