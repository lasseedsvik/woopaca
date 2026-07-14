<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <?php $footer_logo = get_theme_mod('footer_logo'); ?>
                <?php if ($footer_logo) : ?>
                    <img alt="WooPaca" class="footer-logo"
                        src="<?php echo esc_url($footer_logo); ?>">
                <?php endif; ?>
                <ul class="contact-list">
                    <li class="contact-item">
                        <span class="material-symbols-outlined contact-icon">location_on</span>
                        <?php echo esc_html(get_theme_mod('footer_address', '')); ?>
                    </li>
                    <li class="contact-item">
                        <span class="material-symbols-outlined contact-icon">phone</span>
                        <?php echo esc_html(get_theme_mod('footer_phone', '')); ?>
                    </li>
                    <li class="contact-item">
                        <span class="material-symbols-outlined contact-icon">mail</span>
                        <?php $email = get_theme_mod('footer_email', ''); ?>
                        <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                    </li>
                     <li class="contact-item">
                        <span class="material-symbols-outlined contact-icon">house</span>
                        <?php echo esc_html(get_theme_mod('footer_orgnr', '')); ?>
                    </li>
                </ul>
            </div>
            <div class="footer-hours">
                <h4 class="footer-title"><?php echo esc_html(get_theme_mod('footer_hours_heading', 'Opening Hours')); ?></h4>
                <ul class="hours-list">
                    <li class="hours-row"><span>Monday - Friday</span> <strong><?php echo esc_html(get_theme_mod('footer_hours_weekdays', '09:00 - 18:00')); ?></strong></li>
                    <li class="hours-row"><span>Saturday</span> <strong><?php echo esc_html(get_theme_mod('footer_hours_saturday', '10:00 - 14:00')); ?></strong></li>
                    <li class="hours-row"><span>Sunday</span> <strong><?php echo esc_html(get_theme_mod('footer_hours_sunday', 'Closed')); ?></strong></li>
                </ul>
                <?php $store_photo = get_theme_mod('footer_store_image'); ?>
                <?php if ($store_photo) : ?>
                    <img src="<?php echo esc_url($store_photo); ?>"
                        class="store-photo">
                <?php endif; ?>
            </div>
            <div class="footer-links-col">
                <?php
                $footer_links_heading = get_theme_mod('footer_links_heading', 'Information');
                $footer_policy_links = array();

                $privacy_page_id = (int) get_option('wp_page_for_privacy_policy');
                if ($privacy_page_id && 'publish' === get_post_status($privacy_page_id)) {
                    $footer_policy_links[] = array(
                        'label' => get_the_title($privacy_page_id),
                        'url' => get_permalink($privacy_page_id),
                    );
                }

                // WooCommerce's own default page, created alongside Shop/Cart/
                // Checkout/My Account – its ID is stored the same way as those.
                $refund_page_id = (int) get_option('woocommerce_refund_returns_page_id');
                if ($refund_page_id && 'publish' === get_post_status($refund_page_id)) {
                    $footer_policy_links[] = array(
                        'label' => get_the_title($refund_page_id),
                        'url' => get_permalink($refund_page_id),
                    );
                }
                ?>
                <?php if (!empty($footer_policy_links)) : ?>
                    <div class="footer-links">
                        <h4 class="footer-title"><?php echo esc_html($footer_links_heading); ?></h4>
                        <ul class="footer-links-list">
                            <?php foreach ($footer_policy_links as $link) : ?>
                                <li>&#9679; <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="footer-social">
                    <h4 class="footer-title"><?php echo esc_html(get_theme_mod('footer_social_heading', 'Follow Us!')); ?></h4>
                    <div class="social-area">
                        <a class="social-btn" href="<?php echo esc_url(get_theme_mod('footer_facebook_url', '')); ?>">
                            <img alt="Facebook"
                                src="<?php echo get_template_directory_uri(); ?>/assets/images/fb-logo.png"></a>
                        <?php $facebook_qr = get_theme_mod('footer_facebook_qr', ''); ?>
                        <?php if ($facebook_qr) : ?>
                            <div class="qr-wrapper">
                                <img alt="Facebook QR code" src="<?php echo esc_url($facebook_qr); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            © <?php echo esc_html(current_time('Y')); ?> <?php echo esc_html(get_theme_mod('footer_bottom_text', '')); ?>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>