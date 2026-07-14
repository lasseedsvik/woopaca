<?php
get_header();

$error_404_page_id = get_theme_mod('error_404_page_id', 0);
$error_404_page    = $error_404_page_id ? get_post($error_404_page_id) : null;

// Only use it if it's a real, currently-published page - a page
// could have been trashed/unpublished after being picked in the
// Customizer, and we don't want a 404 to ever show broken/private
// content.
if (!$error_404_page || 'page' !== $error_404_page->post_type || 'publish' !== $error_404_page->post_status) {
    $error_404_page = null;
}
?>
<div id="site-wrap">
	<main id="woopaca-main-content" class="site-main">

		<?php if ($error_404_page) : ?>

			<div class="container">
				<div class="page-content">
					<?php echo apply_filters('the_content', $error_404_page->post_content); ?>
				</div>
			</div>

		<?php else : ?>

			<div class="error-404 not-found container">
				<header class="page-header">
					<h1 class="page-title">404</h1>
				</header>

				<div class="page-content">
					<p>The page you're looking for couldn't be found.</p>
				</div>

				<div class="error-404-actions">
					<a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">Back to homepage</a>
					<?php if (class_exists('WooCommerce')) : ?>
						<a href="<?php echo esc_url(get_post_type_archive_link('product')); ?>" class="btn-outline">Go to shop</a>
					<?php endif; ?>
				</div>
			</div>

		<?php endif; ?>

	</main>
</div>
<?php get_footer(); ?>
