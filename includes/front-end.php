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
add_action( 'pre_get_posts', __NAMESPACE__ . '\remove_drip_from_queries', 1 );
add_filter( 'the_content', __NAMESPACE__ . '\drip_control', 11 );

/**
 * Removed dripped content from post queries.
 *
 * @param  object $query  The existing query object.
 *
 * @return object
 */
function remove_drip_from_queries( $query ) {

	// Don't run on admin.
	if ( is_admin() ) {
		return $query;
	}

	// @@todo figure out how to find the posts.

	// Send back the possibly modified query.
	return $query;
}

/**
 * Run our various checks for drips.
 *
 * @param  mixed $content  The existing content.
 *
 * @return mixed
 */
function drip_control( $content ) {

	// Don't run on non-logged in users. Maybe?
	if ( ! is_user_logged_in() ) {
		return $content;
	}

	// Grab our post status.
	$maybe_publish  = get_post_status( get_the_ID() );

	// Bail if this content isn't published.
	if ( 'publish' !== esc_attr( $maybe_publish ) ) {
		return $content;
	}

	// Figure out what post type we're on.
	$current_type   = get_post_type( get_the_ID() );

	// Run our post type confirmation.
	$confirm_type   = Utilities\confirm_supported_type( $current_type );

	// Bail if we don't have what we need.
	if ( ! $confirm_type ) {
		return $content;
	}

	// Compare our dates.
	$drip_compare   = Utilities\compare_drip_signup_dates( get_the_ID() );

	// If we have a false return (which means missing data) then just return the content.
	if ( ! $drip_compare ) {
		return $content;
	}

	// Check for the display flag.
	if ( ! empty( $drip_compare['display'] ) ) {
		return $content;
	}

	// Set our 'content not available' message.
	$drip_message   = ! empty( $drip_compare['message'] ) ? esc_attr( $drip_compare['message'] ) : __( 'This content is not available.', 'drip-press' );

	// Return our message.
	return apply_filters( Core\HOOK_PREFIX . 'drip_pending_message_format', wpautop( $drip_message ) );
}

