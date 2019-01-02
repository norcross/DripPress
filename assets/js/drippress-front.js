//*******************************************************
// Fire up our very basic jQuery functions.
//*******************************************************
jQuery(document).ready( function($) {

	// Run this on the existance of the form.
	if ( $( 'form.dppress-prompt-form' ).length > 0 ) {

		// Run on the checkbox.
		$( '.dppress-prompt-button-wrap' ).on( 'click', 'button.dppress-prompt-button', function( event ) {

			// Don't do the link.
			event.preventDefault();

			// First check for the nonce.
			var nonce = $( '.dppress-prompt-button-wrap' ).find( '#dppress_nonce_status_name' ).val();

			// Bail without a nonce.
			if ( '' === nonce || undefined === nonce ) {
				return;
			}

			// Get the two required values.
			var post_id = $( '.dppress-prompt-button-wrap' ).find( '#dppress-prompt-post-id' ).val();
			var user_id = $( '.dppress-prompt-button-wrap' ).find( '#dppress-prompt-user-id' ).val();

			// Set my data array.
			var data = {
				action:  'dppress_set_user_status',
				nonce: nonce,
				post_id: post_id,
				user_id: user_id,
			};

			// Now handle the response.
			jQuery.post( dppressLocal.ajaxurl, data, function( response ) {

				// Handle the failure.
				if ( response.success !== true ) {
					return false;
				}

				// We got message markup, so show it.
				if ( response.data.markup !== '' ) {
					$( 'div.dppress-completed-prompt-wrap' ).replaceWith( response.data.markup );
				}

				// Nothing left inside the response.
			});

			// Nothing left on the click.
		});

		// Nothing else on this box.
	};

//*******************************************************
// that's all folks. we're done here
//*******************************************************

});
