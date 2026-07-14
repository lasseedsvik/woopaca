/**
 * Adds an "Empty cart" button below the block-based Cart page
 * (the WooCommerce Cart block). Not needed - and does nothing - on
 * the classic cart template, which already gets its own button
 * server-side (see woopaca_empty_cart_button_classic() in inc/woocommerce.php).
 *
 * The Cart block updates itself via its own internal AJAX/React state
 * (removing the last item doesn't reload the page), so our button –
 * injected once as plain DOM outside that block's control – has no
 * way to know the cart emptied out unless we watch for it ourselves.
 * A MutationObserver on the cart block, combined with a check against
 * WooCommerce's public Store API, hides the button again once that
 * happens (checking the REST API instead of guessing at the block's
 * internal CSS classes, which could change between WooCommerce
 * versions).
 */
( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! window.woopacaCart || document.querySelector( '.cart-empty-button' ) ) {
			return;
		}

		var cartBlock = document.querySelector( '.wp-block-woocommerce-cart' );
		if ( ! cartBlock ) {
			return;
		}

		var wrapper = document.createElement( 'div' );
		wrapper.className = 'cart-empty-actions';

		var button = document.createElement( 'a' );
		button.href = window.woopacaCart.emptyCartUrl;
		button.className = 'cart-empty-button';
		button.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">remove_shopping_cart</span>' + window.woopacaCart.buttonLabel;
		button.addEventListener( 'click', function ( e ) {
			if ( ! confirm( window.woopacaCart.confirmText ) ) {
				e.preventDefault();
			}
		} );

		wrapper.appendChild( button );
		cartBlock.insertAdjacentElement( 'afterend', wrapper );

		var checkTimer = null;

		function checkIfCartEmptied() {
			fetch( window.woopacaCart.storeApiCartUrl, { credentials: 'same-origin' } )
				.then( function ( response ) { return response.json(); } )
				.then( function ( data ) {
					var isEmpty = ! data || ! data.items || data.items.length === 0;
					wrapper.style.display = isEmpty ? 'none' : '';
				} )
				.catch( function () {
					// Can't confirm either way over the network - leave
					// the button as-is rather than guessing.
				} );
		}

		var observer = new MutationObserver( function () {
			// Cart block re-renders repeatedly while WooCommerce syncs
			// quantity changes, so debounce instead of checking on
			// every single mutation.
			clearTimeout( checkTimer );
			checkTimer = setTimeout( checkIfCartEmptied, 400 );
		} );

		observer.observe( cartBlock, { childList: true, subtree: true } );
	} );
} )();
