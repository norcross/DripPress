<?php
/**
 * Our front end specific functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\FrontEnd;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;

/**
 * Start our engines.
 */
add_filter( 'the_content', __NAMESPACE__ . '\drip_control', 11 );

/**
 * Run our various checks for drips.
 *
 * @param  mixed $content  The existing content.
 *
 * @return mixed
 */
function drip_control( $content ) {

	// Figure out what post type we're on.
	$current_type   = get_post_type( get_the_ID() );

	// Run our post type confirmation.
	$confirm_type   = Utilities\confirm_supported_type( $current_type );

	// Bail if we don't have what we need.
	if ( ! $confirm_type ) {
		return $content;
	}

	// Get the stored meta.
	$stored_values  = Helpers\get_content_drip_meta( get_the_ID() );

	// Bail right away if we aren't set to live.
	if ( empty( $stored_values['live'] ) ) {
		return $content;
	}

	// Compare our dates.
	$drip_compare   = Utilities\compare_drip_dates( get_the_ID() );

	// If we have a false return (which means missing data) then just return the content.
	if ( ! $drip_compare ) {
		return $content;
	}

	// Check for empty display and a message.
	if ( empty( $drip_compare['display'] ) && ! empty( $drip_compare['message'] ) ) {
		return wpautop( $drip_compare['message'] );
	}

	// Just bail?
	return $content;
}

