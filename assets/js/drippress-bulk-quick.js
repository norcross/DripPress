//*******************************************************
// Fire up our basic quick and bulk edit JS functions.
//*******************************************************
(function($) {

	// Create a copy of the WP inline edit post function.
	var $wp_inline_edit = inlineEditPost.edit;

	// Then we overwrite the function with our own code.
	inlineEditPost.edit = function( id ) {

		// We "call" the original WP edit function so
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );

		// Set a blank post ID.
		var $post_id = 0;

		// Now dig in and get out the ID.
		if ( typeof( id ) == 'object' ) {
			$post_id = parseInt( this.getId( id ) );
		}

		// We have a post ID, so set our value in the dropdown.
		if ( $post_id > 0 ) {

			// Define our post and edit rows.
			var $edit_row = $( '#edit-' + $post_id );
			var $post_row = $( '#post-' + $post_id );

			// Get the drip values.
			var $drip_count = $( '.column-drippress-quick input.drippress-quick-count-val', $post_row ).val();
			var $drip_range = $( '.column-drippress-quick input.drippress-quick-range-val', $post_row ).val();

			// Now set the values.
			$( '.column-drippress-quick input#dppress-quick-count', $edit_row ).val( $drip_count );
			$( '.column-drippress-quick select#dppress-quick-range', $edit_row ).val( $drip_range );
		}
	};

	// This is actually the checkbox inside the box.
	$( document ).on( 'change', 'input#dppress-bulk-clear', function() {

		// Define the bulk edit row.
		var $bulk_row = $( '#bulk-edit' );

		// See if the box is checked or not.
		var $is_maybe = $( this ).prop( 'checked' );

		// Now toggle the fields based on the result.
		$bulk_row.find( 'input#dppress-bulk-count' ).prop( 'disabled', $is_maybe );
		$bulk_row.find( 'select#dppress-bulk-range' ).prop( 'disabled', $is_maybe );
	});

	// Now we handle the bulk edit Ajax call.
	$( document ).on( 'click', '#bulk_edit', function() {

		// Define the bulk edit row.
		var $bulk_row = $( '#bulk-edit' );

		// First check for the nonce.
		var $drip_nonce = $bulk_row.find( 'input#dppress_nonce_bulk_name' ).val();

		// Bail without a nonce.
		if ( '' === $drip_nonce || undefined === $drip_nonce ) {
			return;
		}

		// Then check for the trigger.
		var $drip_triggr = $bulk_row.find( 'input#dppress-bulk-trigger' ).val();

		// Bail without the correct trigger.
		if ( '' === $drip_triggr || undefined === $drip_triggr || 'bulk' !== $drip_triggr ) {
			return;
		}

		// Get the selected post ids that are being edited.
		var $post_ids = new Array();
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});

		// Get the drip values.
		var $drip_count = $bulk_row.find( 'input#dppress-bulk-count' ).val();
		var $drip_range = $bulk_row.find( 'select#dppress-bulk-range' ).val();

		// Also check for the clear.
		var $drip_clear = $bulk_row.find( 'input#dppress-bulk-clear' ).prop( 'checked' );

		// save the data
		$.ajax({
			url: ajaxurl, // this is a variable that WordPress has already defined for us
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'save_drip_bulkedit',
				post_ids: $post_ids,
				nonce: $drip_nonce,
				trigger: $drip_triggr,
				count: $drip_count,
				range: $drip_range,
				clear: $drip_clear,
			}
		});
	});

//*******************************************************
// that's all folks. we're done here
//*******************************************************

})(jQuery);
