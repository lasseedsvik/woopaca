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
            <?php echo esc_html(apply_filters('woocommerce_out_of_stock_message', __('This product is out of stock.', 'woopaca'))); ?>
        </p>
    <?php else : ?>

        <div class="dm-variation-buttons variations">

            <?php
            $attribute_index = 0;
            foreach ($attributes as $attribute_name => $options) :
                $attribute_index++;
                $attribute_label = wc_attribute_label($attribute_name);
                // Ids are built from the product ID + position instead of
                // sanitize_title() of the option text: two options can
                // sanitize to the same slug (e.g. "XL" / "xl", "1/2" / "12"),
                // and non-ASCII characters can end up as %xx sequences.
                $group_id = 'dm-' . $product->get_id() . '-' . $attribute_index;

                // Global (taxonomy) attributes: show the boxes in the
                // term order set under Products → Attributes, the same
                // order wc_dropdown_variation_attribute_options() uses
                // for the real <select>. $options on its own comes back
                // in whatever order the variations happen to store it.
                if (taxonomy_exists($attribute_name)) {
                    $ordered_slugs = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'slugs'));
                    $options       = array_values(array_intersect($ordered_slugs, $options));
                }
                $selected_key    = 'attribute_' . sanitize_title($attribute_name);
                $current_value   = isset($_REQUEST[$selected_key])
                    ? wc_clean(wp_unslash($_REQUEST[$selected_key]))
                    : $product->get_variation_default_attribute($attribute_name);
                ?>
                <div class="dm-attribute-group" data-attribute_name="<?php echo esc_attr($selected_key); ?>">
                    <div class="dm-attribute-heading">
                        <span class="dm-attribute-label" id="<?php echo esc_attr($group_id . '-label'); ?>"><?php echo esc_html($attribute_label); ?></span>
                    </div>

                    <div class="dm-attribute-options" role="radiogroup" aria-labelledby="<?php echo esc_attr($group_id . '-label'); ?>">
                        <?php foreach (array_values($options) as $option_index => $option) :
                            $option_name = woopaca_get_attribute_option_name($attribute_name, $option);
                            $input_id    = $group_id . '-' . $option_index;
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
             * via .dm-hidden-selects in woocommerce.css, and also made
             * inert + aria-hidden: the radio boxes above are the
             * accessible controls, so keyboard and screen-reader users
             * shouldn't land on a second, invisible copy of every
             * attribute. JS can still read/write the selects as usual.
             */
            ?>
            <table class="variations dm-hidden-selects" cellspacing="0" role="presentation" aria-hidden="true" inert>
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

        <div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>

        <?php do_action('woocommerce_after_variations_table'); ?>

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
