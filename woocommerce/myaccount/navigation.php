<?php
/**
 * My Account navigation
 *
 * Based on WooCommerce's own myaccount/navigation.php, extended to
 * show a Material Symbols icon in front of each menu item (Dashboard,
 * Orders, Downloads, Addresses, Account details, Log out, and any
 * further items added by plugins).
 *
 * @see https://woocommerce.com/document/template-structure/
 */

defined('ABSPATH') || exit;

/**
 * Icon shown in front of each menu item, keyed by endpoint. Anything
 * not listed here (e.g. an endpoint added by a plugin) falls back to
 * the 'circle' icon below instead of being left without one.
 */
if (!function_exists('myaccount_menu_item_icon')) {
    function myaccount_menu_item_icon($endpoint)
    {
        $icons = array(
            'dashboard'       => 'dashboard',
            'orders'          => 'receipt_long',
            'downloads'       => 'download',
            'edit-address'    => 'location_on',
            'edit-account'    => 'person',
            'customer-logout' => 'logout',
        );

        return isset($icons[$endpoint]) ? $icons[$endpoint] : 'circle';
    }
}

do_action('woocommerce_before_account_navigation');
?>
<nav class="woocommerce-MyAccount-navigation">
    <ul>
        <?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
            <li class="<?php echo esc_attr(wc_get_account_menu_item_classes($endpoint)); ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>">
                    <span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html(myaccount_menu_item_icon($endpoint)); ?></span>
                    <?php echo esc_html($label); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
<?php do_action('woocommerce_after_account_navigation'); ?>
