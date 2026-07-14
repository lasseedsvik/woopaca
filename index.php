<?php get_header(); ?>

<div id="site-wrap">
    <main id="woopaca-main-content" class="site-main">
        <?php
        // Display the page content (if WordPress uses index.php as a fallback)
        if (have_posts()):
            while (have_posts()):
                the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </main>
</div>
<?php get_footer(); ?>