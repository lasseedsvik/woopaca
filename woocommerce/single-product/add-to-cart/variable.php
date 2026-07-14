<?php
/**
 * WooPaca – woocommerce/single-product/add-to-cart/variable.php
 *
 * Replaces WooCommerce's default template that shows each attribute
 * (e.g. "Size") as a <select> dropdown. Here the options are instead
 * rendered as clickable boxes/buttons.
 *
 * WooCommerce's built-in variation script (wc-add-to-cart-variation)
 * controls everything – price, stock status, image, and which button
 * is active – by reading the real <select> elements in the form.
 * We therefore keep the real <select> fields (visually hidden, but
 * fully functional for screen readers and keyboard navigation) and
 * sync them with our boxes via assets/js/variations.js.
 */

defined('ABSPATH') || exit;

global $product;

$attribute_keys  = array_keys($attributes);
$variations_json = wp_json_encode($available_variations);
$variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);

do_action('woocommerce_before_add_to_cart_form'); ?>

<form class="variations_form cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post" enctype="multipart/form-data"
    data-product_id="<?php echo absint($product->get_id()); ?>"
    data-product_variations="<?php echo $variations_attr; ?>">
    <?php do_action('woocommerce_before_variations_form'); ?>

    <?php if (empty($available_variations) && false !== $available_variations) : ?>
        <p class="stock out-of-stock">
            <?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('Denna produkt är tyvärr slut i lager.', 'woopaca'))); ?>
        </p>
    <?php else : ?>

        <div class="dm-variation-buttons variations">

            <?php foreach ($attributes as $attribute_name => $options) :
                $attribute_label = wc_attribute_label($attribute_name);
                $selected_key    = 'attribute_' . sanitize_title($attribute_name);
                $current_value   = isset($_REQUEST[$selected_key])
                    ? wc_clean(wp_unslash($_REQUEST[$selected_key]))
                    : $product->get_variation_default_attribute($attribute_name);
                ?>
                <div class="dm-attribute-group" data-attribute_name="<?php echo esc_attr($selected_key); ?>">
                    <div class="dm-attribute-heading">
                        <label class="dm-attribute-label"><?php echo esc_html($attribute_label); ?></label>
                    </div>

                    <div class="dm-attribute-options" role="group" aria-label="<?php echo esc_attr($attribute_label); ?>">
                        <?php foreach ($options as $option) :
                            $option_name = woopaca_get_attribute_option_name($attribute_name, $option);
                            $input_id    = 'dm-' . sanitize_title($attribute_name) . '-' . sanitize_title($option);
                            ?>
                            <span class="dm-attribute-option">
                                <input
                                    type="radio"
                                    id="<?php echo esc_attr($input_id); ?>"
                                    name="<?php echo esc_attr($selected_key); ?>"
                                    value="<?php echo esc_attr($option); ?>"
                                    class="dm-attribute-radio"
                                    <?php checked($current_value, $option); ?>
                                >
                                <label for="<?php echo esc_attr($input_id); ?>" class="dm-attribute-box">
                                    <?php echo esc_html($option_name); ?>
                                </label>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php
            echo wp_kses_post(apply_filters(
                'woocommerce_reset_variations_link',
                '<a class="reset_variations" href="#">' . esc_html__('Reset', 'woopaca') . '</a>'
            ));
            ?>

            <?php
            /**
             * Real <select> fields that WooCommerce's variation script
             * needs to work out the correct variation. Visually hidden
             * via .dm-hidden-selects in woocommerce.css (not
             * display:none, so they remain accessible to screen
             * readers/keyboard navigation).
             */
            ?>
            <table class="variations dm-hidden-selects" cellspacing="0">
                <tbody>
                    <?php foreach ($attributes as $attribute_name => $options) : ?>
                        <tr>
                            <td class="label">
                                <label for="<?php echo esc_attr(sanitize_title($attribute_name)); ?>"><?php echo wc_attribute_label($attribute_name); ?></label>
                            </td>
                            <td class="value">
                                <?php
                                wc_dropdown_variation_attribute_options(
                                    array(
                                        'options'   => $options,
                                        'attribute' => $attribute_name,
                                        'product'   => $product,
                                    )
                                );
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="single_variation_wrap">
            <?php
                do_action('woocommerce_before_single_variation');
                do_action('woocommerce_single_variation');
                do_action('woocommerce_after_single_variation');
            ?>
        </div>

    <?php endif; ?>

    <?php do_action('woocommerce_after_variations_form'); ?>
</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>
