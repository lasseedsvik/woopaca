/**
 * Runs in the Customizer's admin control panel (not the preview
 * iframe itself). The blog avatar/heading/text settings only appear
 * on the blog page, so this switches the preview pane over to that
 * page while the "Blog page" section is open, and switches back to
 * whatever page you were previously looking at once it's closed -
 * so you always get a live preview of what you're editing.
 */
( function ( wp ) {
	if ( ! wp || ! wp.customize || ! window.woopacaCustomizer || ! window.woopacaCustomizer.blogPageUrl ) {
		return;
	}

	wp.customize.bind( 'ready', function () {
		var api = wp.customize;
		var section = api.section( 'blog_settings' );

		if ( ! section ) {
			return;
		}

		var previousUrl = null;

		section.expanded.bind( function ( isExpanded ) {
			if ( isExpanded ) {
				previousUrl = api.previewer.previewUrl.get();
				api.previewer.previewUrl.set( window.woopacaCustomizer.blogPageUrl );
			} else if ( previousUrl ) {
				api.previewer.previewUrl.set( previousUrl );
				previousUrl = null;
			}
		} );
	} );
} )( window.wp );
