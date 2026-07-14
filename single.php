<?php get_header(); ?>

<div id="site-wrap">

	<main id="woopaca-main-content" class="site-main">

		<!-- Main container for the two-column layout -->

		<div class="single-container">



			<?php if (have_posts()):

				while (have_posts()):

					the_post(); ?>



					<article id="post-<?php the_ID(); ?>" <?php post_class('single-post-main'); ?>>



						<div class="entry-content">

							<?php the_content(); ?>

							<?php
							wp_link_pages(array(
								'before' => '<div class="page-links">' . esc_html__('Pages:', 'woopaca'),
								'after' => '</div>',
							));
							?>

						</div>



						<div class="post-meta">

							<span class="post-date"><?php echo esc_html(get_theme_mod('blog_post_published_label', 'Publicerad:')); ?> <?php the_time(get_option('date_format')); ?></span>

						</div>



						<div style="height:40px" aria-hidden="true" class="wp-block-spacer"></div>



						<h3>

							<?php
							// Same reasoning as the front-page blog teaser: read the
							// actual "Posts page" setting instead of hardcoding /blogg,
							// so this keeps working even if that page is ever renamed
							// or moved.
							$blog_page_id  = get_option('page_for_posts');
							$blog_page_url = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blogg');
							?>

							<a href="<?php echo esc_url($blog_page_url); ?>" onclick="goBackOrHome(event)"><img alt="WooPaca" class="logo-main icon"

									src="<?php echo get_template_directory_uri(); ?>/assets/images/blog.svg">

								<?php echo esc_html(get_theme_mod('blog_post_back_label', 'Tillbaka!')); ?></a>



							<script>

								function goBackOrHome(event) {

									if (document.referrer) {

										event.preventDefault();

										history.back();

									}

								}

							</script>
						</h3>

					</article>



				<?php endwhile; endif; ?>





			<!-- RIGHT COLUMN: Listing of other blog posts -->

			<aside class="single-sidebar">

				<h3 class="sidebar-title"><?php echo esc_html(get_theme_mod('blog_sidebar_heading', 'Latest posts!')); ?></h3>

				<ul class="sidebar-post-list">

					<?php

					// Get the current post's ID so we can exclude it from the list

					$current_post_id = get_the_ID();



					// Custom settings for WP_Query

					$args = array(

						'post_type' => 'post',

						'posts_per_page' => 10,                    // Number of posts to show in the list

						'post__not_in' => array($current_post_id) // Exclude the post the visitor is currently reading

					);



					$sidebar_query = new WP_Query($args);



					if ($sidebar_query->have_posts()):

						while ($sidebar_query->have_posts()):

							$sidebar_query->the_post();

							?>

							<li class="sidebar-post-item reveal-on-scroll">

								<a href="<?php the_permalink(); ?>" class="sidebar-post-link">

									<!-- Optional: a small thumbnail image in the list -->

									<?php if (has_post_thumbnail()): ?>

										<div class="sidebar-post-thumb">

											<?php the_post_thumbnail('thumbnail'); ?>

										</div>

									<?php endif; ?>



									<div class="sidebar-post-info">

										<span class="sidebar-post-date"><?php the_time(get_option('date_format')); ?></span>

										<span class="sidebar-post-title"><?php the_title(); ?></span>

									</div>

								</a>

							</li>

							<?php

						endwhile;

						// Reset the global post data after a custom WP_Query (important!)

						wp_reset_postdata();

					else:

						echo '<li>Inga andra inlägg hittades.</li>';

					endif;

					?>

					<li class="sidebar-post-item sidebar-post-item-return">

						<a href="<?php echo esc_url($blog_page_url); ?>">
							<img alt="WooPaca" class="logo-main icon sidebar-post-item-return-icon"
								src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/blog.svg'); ?>">
						</a>

						<h4>

							<a href="<?php echo esc_url($blog_page_url); ?>"><?php echo esc_html(get_theme_mod('blog_sidebar_see_all_label', 'See all posts!')); ?></a>

						</h4>

					</li>

				</ul>

			</aside>



		</div>

	</main>

</div>

<?php get_footer(); ?>