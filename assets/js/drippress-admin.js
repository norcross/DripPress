//*******************************************************
// Fire up our very basic jQuery functions.
//*******************************************************
jQuery(document).ready( function($) {

	// Handle the show / hide for the box.
	if ( $( 'div#dppress-schd-box' ).length > 0 ){

		// First confirm the value on load.
		if ( $('input#dppress-live' ).prop( 'checked' ) ) {
			$( 'ul.dppress-data' ).show();
		} else {
			$( 'ul.dppress-data' ).hide();
		}

		// Now look for the checkbox changing.
		$( 'input#dppress-live' ).change( function() {

			// Hide if checked.
			if ( $( this ).prop('checked' ) ) {
				$( 'ul.dppress-data' ).slideDown( 'slow' );
			} else {
				$( 'ul.dppress-data' ).slideUp( 'slow' );
			}
		});

		// Nothing else on this box.
	};

//*******************************************************
// that's all folks. we're done here
//*******************************************************

});
