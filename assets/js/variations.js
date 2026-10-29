/**
 * WooPaca – syncs the clickable attribute boxes (SIZE, COLOR, etc.)
 * with WooCommerce's real, hidden <select> fields so the built-in
 * variation script (wc-add-to-cart-variation) keeps handling price,
 * stock status, and which variant is selected.
 *
 * On top of that sync, this file also implements a "cascading"
 * reveal: attributes are shown one at a time, in the order they were
 * created in the admin (which is the order the .dm-attribute-group
 * elements already appear in the DOM – see
 * woocommerce/single-product/add-to-cart/variable.php, which loops
 * over $attributes in the exact order WooCommerce returns them, i.e.
 * the attribute order/position set on the product in wp-admin).
 *
 * Only the first attribute is visible to start with. Each following
 * attribute only appears once every attribute above it has a
 * selection, so it's never possible to pick from a set of options
 * that doesn't match what's already been chosen. Picking a new value
 * for an attribute clears anything selected further down the chain
 * and collapses back to only showing the next attribute in line.
 *
 * Availability (graying out options that can't lead to a real
 * variation) is worked out here directly from the form's own
 * data-product_variations JSON, rather than relying on WooCommerce
 * disabling <option> elements in the real selects. That's
 * intentional: WooCommerce only disables options dynamically while a
 * product has 30 variations or fewer – above that it leaves every
 * option enabled for performance and only complains after the fact
 * ("no products matched your selection"). Reading the variation data
 * ourselves means the boxes gray out correctly no matter how many
 * variations a product has.
 *
 * WooCommerce's own "only one matching variation" check still gates
 * the add-to-cart button (via wc-add-to-cart-variation.js), so it
 * stays disabled until every attribute has a selection.
 */
jQuery(function ($) {

    /**
     * Applies everything that needs to happen when one attribute
     * option becomes selected: syncing the real <select>, clearing
     * anything chosen further down the chain, and refreshing the
     * cascade/availability/visual state.
     */
    function selectOption($radio) {
        if (!$radio.length || $radio.prop('disabled')) {
            return;
        }

        var name    = $radio.attr('name');
        var value   = $radio.val();
        var $form   = $radio.closest('.variations_form');
        var $group  = $radio.closest('.dm-attribute-group');
        var $select = $form.find('select[name="' + name + '"]');

        // Re-clicking the value that's already selected changes
        // nothing – don't wipe the groups below it.
        if ($select.val() === value) {
            updateSelectedVisual($form);
            return;
        }

        // Clear everything further down the chain FIRST. While a
        // later attribute still holds a value, WooCommerce's own
        // script (for products with ≤30 variations) has removed every
        // <option> from this group's <select> that doesn't combine
        // with that later value – so setting the new value before
        // clearing would silently fail (.val() can't select an option
        // that isn't there). Clearing first makes WooCommerce rebuild
        // this select with every option that's valid given only the
        // groups ABOVE it.
        clearGroupsAfter($form, $group);

        // Manually enforce radio-group exclusivity and the checked
        // state ourselves, rather than relying on the browser's
        // native label-click behavior to have done it already by the
        // time this runs.
        $form.find('input[name="' + name + '"]').prop('checked', false);
        $radio.prop('checked', true);

        $select.val(value).trigger('change');

        updateCascade($form);
        updateSelectedVisual($form);
        updateAvailability($form);
    }

    // Click directly on the visible box -> update state ourselves.
    // Bound to 'click' on .dm-attribute-box (the actual element the
    // visitor interacts with) rather than only relying on 'change' on
    // the underlying radio: the native label-for "change" event
    // reaching a delegated document-level listener turned out to be
    // unreliable for the very first interaction in testing (the
    // selection worked functionally – price, cascade, add-to-cart
    // gating – but the visual update lagged until the next full page
    // load). Handling the click directly removes that dependency for
    // mouse/touch use.
    $(document).on('click', '.dm-attribute-box', function (e) {
        // getElementById instead of a '#id' jQuery selector: ids can
        // contain characters (e.g. '%' from sanitize_title() on
        // non-ASCII option values) that break CSS selector parsing.
        var forId  = $(this).attr('for');
        var $radio = $(forId ? document.getElementById(forId) : null);

        if (!$radio.length || $radio.prop('disabled')) {
            return;
        }

        e.preventDefault();
        selectOption($radio);
    });

    // Keyboard users can focus a box (it's a real, tabbable <label
    // for="...">) and press Space/Enter, which activates the
    // associated radio directly rather than going through the click
    // handler above – listening for 'change' on the radio itself
    // keeps that path working too.
    $(document).on('change', '.dm-variation-buttons input[type=radio]', function () {
        selectOption($(this));
    });

    // If WooCommerce resets the selections ("Clear selection"), the boxes should follow
    $(document).on('reset_data', '.variations_form', function () {
        var $form = $(this);
        $form.find('.dm-variation-buttons input[type=radio]').prop('checked', false);
        updateCascade($form);
        updateSelectedVisual($form);
        updateAvailability($form);
    });

    /**
     * Single price display for variable products.
     *
     * The theme prints the product's price next to the quantity field
     * (.product-info-price, see woopaca_product_info_price_after_quantity()
     * in inc/woocommerce.php). For a variable product that's the full
     * range ("199 kr – 499 kr"), and WooCommerce ALSO printed the chosen
     * variation's price above the button – two prices, one of them never
     * changing. Instead, the range is swapped for the chosen variation's
     * price here, and WooCommerce's own copy is hidden in woocommerce.css
     * (.variations_form .woocommerce-variation-price).
     *
     * When every variation costs the same, WooCommerce sends an empty
     * price_html – the range then already IS that single price, so it's
     * simply left alone.
     */
    function getPriceBox($form) {
        var $box = $form.find('.product-info-price');
        if ($box.length && typeof $box.data('dmDefaultPrice') === 'undefined') {
            $box.data('dmDefaultPrice', $box.html());
        }
        return $box;
    }

    $(document).on('found_variation', '.variations_form', function (event, variation) {
        var $box = getPriceBox($(this));
        if ($box.length && variation && variation.price_html) {
            $box.html(variation.price_html);
        }
    });

    $(document).on('reset_data hide_variation', '.variations_form', function () {
        var $box = getPriceBox($(this));
        if ($box.length) {
            $box.html($box.data('dmDefaultPrice'));
        }
    });

    // WooCommerce moves focus to the first <select> after "Reset",
    // but those selects are hidden (inert) here – move focus to the
    // first visible option box instead so keyboard users aren't
    // dropped back at the top of the page.
    $(document).on('click', '.variations_form .reset_variations', function () {
        var $form = $(this).closest('.variations_form');
        window.setTimeout(function () {
            $form.find('.dm-attribute-group:not(.dm-attribute-hidden) .dm-attribute-radio:not(:disabled)').first().trigger('focus');
        }, 0);
    });

    // Safety net: WooCommerce fires this every time it finishes
    // recalculating the current variation, regardless of what
    // triggered it. Re-syncing here too means our boxes can never
    // end up out of step with whatever WooCommerce's own script
    // decided the select values should be.
    $(document).on('woocommerce_update_variation_values', '.variations_form', function () {
        var $form = $(this);
        updateCascade($form);
        updateSelectedVisual($form);
        updateAvailability($form);
    });

    /**
     * Returns this form's variation data (array of objects, each with
     * an "attributes" map of attribute_name -> value, where an empty
     * string means "any value matches").
     */
    function getVariations($form) {
        var data = $form.data('product_variations');

        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (e) {
                data = [];
            }
        }

        return Array.isArray(data) ? data : [];
    }

    /**
     * Rough JS equivalent of WordPress's sanitize_title(): lowercases,
     * strips accents (å/ä/ö included), and turns anything else into
     * hyphens. WooCommerce sometimes stores a custom (non-taxonomy)
     * attribute's variation value as this kind of slug even though
     * the visible option text/value is the original, un-slugified
     * text – comparing both the raw and the slugified form below
     * means the matching works either way.
     */
    function slugify(text) {
        return text
            .toString()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[åÅ]/g, 'a')
            .replace(/[äÄ]/g, 'a')
            .replace(/[öÖ]/g, 'o')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /**
     * True if a variation's stored value for one attribute should be
     * treated as matching a given option value: either it's the
     * WooCommerce "Any" wildcard (empty string), an exact match, or
     * matches once both sides are slugified.
     */
    function valuesMatch(variationValue, optionValue) {
        if (variationValue === '' || variationValue === undefined) {
            return true; // "Any" – matches everything
        }
        if (variationValue === optionValue) {
            return true;
        }
        return slugify(variationValue) === slugify(optionValue);
    }

    /**
     * Returns the real <select> that belongs to a given attribute
     * group (".dm-attribute-group").
     */
    function getSelectForGroup($form, $group) {
        return $form.find('select[name="' + $group.data('attribute_name') + '"]');
    }

    /**
     * True if the given <select> currently has a real value chosen.
     */
    function selectHasValue($select) {
        return !!($select.length && $select.val());
    }

    /**
     * Current selections across the whole form: attribute select name
     * -> selected value (empty string if not chosen yet).
     */
    function getCurrentSelections($form) {
        var selections = {};

        $form.find('.dm-attribute-group').each(function () {
            var attrName = $(this).data('attribute_name');
            var $select  = getSelectForGroup($form, $(this));
            selections[attrName] = $select.length ? $select.val() : '';
        });

        return selections;
    }

    /**
     * Grays out (and disables) every option box that can't lead to an
     * actual, orderable variation given what's selected in the
     * attribute groups ABOVE it. Groups below it are deliberately
     * ignored: in the cascade, picking a new value in a group clears
     * everything below it anyway, so a later selection must never
     * lock the visitor out of changing an earlier one. (Previously
     * every other group was taken into account, so after e.g. Red + M
     * the "Blue" box was grayed out and the only way to switch color
     * was the Reset link.)
     */
    function updateAvailability($form) {
        var variations = getVariations($form);
        var selections = getCurrentSelections($form);

        if (!variations.length) {
            return;
        }

        var precedingNames = [];

        $form.find('.dm-attribute-group').each(function () {
            var attrName    = $(this).data('attribute_name');
            var constraints = precedingNames.slice();
            precedingNames.push(attrName);
            var $ownSelect  = getSelectForGroup($form, $(this));
            var ownValue    = $ownSelect.length ? $ownSelect.val() : '';

            $(this).find('.dm-attribute-radio').each(function () {
                var $radio      = $(this);
                var optionValue = $radio.val();

                // A box that's currently checked (or is literally the
                // exact value the select already holds) is available
                // by definition – it's what's currently in use. Never
                // gray this one out, regardless of what the
                // computation below would otherwise conclude.
                // Checking the radio's own "checked" property directly
                // (rather than only comparing values) means this can
                // never be thrown off by any mismatch between how the
                // select's and the radio's values happen to be
                // formatted.
                if ($radio.prop('checked') || (ownValue && optionValue === ownValue)) {
                    $radio.prop('disabled', false);
                    $radio.closest('.dm-attribute-option').removeClass('dm-attribute-unavailable');
                    return;
                }

                var isAvailable = variations.some(function (variation) {
                    var varAttrs = variation.attributes || {};

                    // variation_is_active is what WooCommerce itself
                    // uses to disable an <option> (e.g. out of stock
                    // with "Hide out of stock items" turned on).
                    if (variation.variation_is_visible === false || variation.variation_is_active === false) {
                        return false;
                    }

                    if (!valuesMatch(varAttrs[attrName], optionValue)) {
                        return false;
                    }

                    for (var c = 0; c < constraints.length; c++) {
                        var otherName = constraints[c];
                        if (!selections[otherName]) {
                            continue;
                        }
                        if (!valuesMatch(varAttrs[otherName], selections[otherName])) {
                            return false;
                        }
                    }

                    return true;
                });

                $radio.prop('disabled', !isAvailable);
                $radio.closest('.dm-attribute-option').toggleClass('dm-attribute-unavailable', !isAvailable);
            });
        });
    }

    /**
     * Clears the selection (both the visible boxes and the real
     * hidden <select>) for every attribute group that comes after
     * the given group in the DOM/admin order, and tells WooCommerce
     * via 'change' so price/availability get recalculated.
     */
    function clearGroupsAfter($form, $group) {
        var reachedCurrentGroup = false;

        $form.find('.dm-attribute-group').each(function () {
            var $thisGroup = $(this);

            if ($thisGroup.is($group)) {
                reachedCurrentGroup = true;
                return; // never touch the group that was just changed
            }

            if (!reachedCurrentGroup) {
                return; // this group comes before the one just changed
            }

            var $select = getSelectForGroup($form, $thisGroup);

            $thisGroup.find('.dm-attribute-radio').prop('checked', false);

            if (selectHasValue($select)) {
                $select.val('').trigger('change');
            }
        });
    }

    /**
     * Marks each option box as visually selected (or not) based on
     * whether it matches its group's real <select> CURRENT value –
     * the same value WooCommerce's own variation script reads price/
     * stock/availability from – using an explicit class
     * (.dm-attribute-box--selected) rather than leaning on the native
     * :checked CSS pseudo-class or a separately-tracked "checked"
     * property.
     *
     * This is deliberate: earlier versions derived the selected look
     * from the radio's own .prop('checked'), which could end up out
     * of sync with the actual selection depending on exactly when
     * WooCommerce's own script (wc-add-to-cart-variation) ran its
     * internal recalculation relative to our code. The <select> is
     * the one thing WooCommerce itself is guaranteed to keep correct
     * at all times (that's what it uses to find/price/gate the
     * variation), so treating it as the single source of truth here
     * removes any possibility of the visual state drifting away from
     * what's actually selected.
     */
    function updateSelectedVisual($form) {
        $form.find('.dm-attribute-group').each(function () {
            var $group        = $(this);
            var $select       = getSelectForGroup($form, $group);
            var currentValue  = $select.length ? $select.val() : '';

            $group.find('.dm-attribute-radio').each(function () {
                var $radio     = $(this);
                var isSelected = !!currentValue && $radio.val() === currentValue;

                $radio.prop('checked', isSelected);

                $radio.closest('.dm-attribute-option')
                    .find('.dm-attribute-box')
                    .toggleClass('dm-attribute-box--selected', isSelected);
            });
        });
    }

    /**
     * Shows the first attribute group, then reveals each following
     * group only once every group before it has a selected value.
     * Groups that aren't reachable yet are hidden with
     * .dm-attribute-hidden (see assets/css/woocommerce.css).
     */
    function updateCascade($form) {
        var $groups          = $form.find('.dm-attribute-group');
        var previousHasValue = true;

        $groups.each(function () {
            var $group  = $(this);
            var $select = getSelectForGroup($form, $group);

            $group.toggleClass('dm-attribute-hidden', !previousHasValue);

            previousHasValue = previousHasValue && selectHasValue($select);
        });
    }

    // Set the initial cascade and availability state once, right away, on page load.
    // (updateSelectedVisual reads each group's real <select> value, so
    // any pre-filled/default/sticky attribute selections are picked up
    // automatically here – no separate pre-fill step needed.)
    $('.variations_form').each(function () {
        var $form = $(this);
        updateCascade($form);
        updateSelectedVisual($form);
        updateAvailability($form);
    });
});
