/**
 * Runs inside the Customizer's preview iframe. Together with the
 * postMessage transport set on the blog_avatar / blog_welcome_heading
 * / blog_welcome_text settings (functions.php), this updates the blog
 * page live as those fields are edited, instead of waiting for a full
 * page reload.
 */
( function ( wp ) {
	if ( ! wp || ! wp.customize ) {
		return;
	}

	wp.customize( 'blog_avatar', function ( value ) {
		value.bind( function ( newUrl ) {
			var wrapper = document.querySelector( '.welcome-box' );
			var img = document.getElementById( 'blog-avatar-img' );

			if ( ! newUrl ) {
				if ( img ) {
					img.remove();
				}
				return;
			}

			if ( img ) {
				img.src = newUrl;
			} else if ( wrapper ) {
				img = document.createElement( 'img' );
				img.id = 'blog-avatar-img';
				img.alt = 'WooPaca';
				img.className = 'logo-main thomas-avatar';
				img.src = newUrl;
				wrapper.insertBefore( img, wrapper.firstChild );
			}
		} );
	} );

	wp.customize( 'blog_welcome_heading', function ( value ) {
		value.bind( function ( newHeading ) {
			var heading = document.getElementById( 'blog-welcome-heading' );
			if ( heading ) {
				heading.textContent = newHeading;
			}
		} );
	} );

	wp.customize( 'blog_welcome_text', function ( value ) {
		value.bind( function ( newText ) {
			var textWrap = document.getElementById( 'blog-welcome-text' );
			if ( textWrap ) {
				// Same paragraph-splitting behaviour as wpautop() server-side.
				var paragraphs = newText.split( /\n\s*\n/ ).filter( function ( p ) {
					return p.trim().length > 0;
				} );
				textWrap.innerHTML = paragraphs.map( function ( p ) {
					return '<p>' + p.trim() + '</p>';
				} ).join( '' );
			}
		} );
	} );
} )( window.wp );
