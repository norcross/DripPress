<?php
/**
 * Our basic utility functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Utilities;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;

/**
 * Check where we are on the current admin.
 *
 * @param  string $key  A single key from the data array.
 *
 * @return mixed
 */
function check_admin_screen( $key = '' ) {

	// If we aren't on the admin, or don't have the function, fail right away.
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return false;
	}

	// Get the screen object.
	$screen = get_current_screen();

	// If we didn't get our screen object, bail.
	if ( ! is_object( $screen ) ) {
		return false;
	}

	// Switch through and return the item.
	switch ( sanitize_key( $key ) ) {

		case 'object' :
			return $screen;
			break;

		case 'action' :
			return $screen->action;
			break;

		case 'base' :
			return $screen->base;
			break;

		case 'id' :
			return $screen->id;
			break;

		case 'type' :
		case 'post_type' :
			return $screen->post_type;
			break;

		default :
			return array(
				'action'    => $screen->action,
				'base'      => $screen->base,
				'id'        => $screen->id,
				'post_type' => $screen->post_type,
			);

		// End all case breaks.
	}
}

/**
 * Confirm the type we're on is supported.
 *
 * @param  string $post_type  The post type we wanna check.
 *
 * @return boolean
 */
function confirm_supported_type( $post_type = '' ) {

	// Get our supported post types.
	$supported_types    = Helpers\get_supported_types();

	// Return our boolean based on what was passed.
	return ! empty( $post_type ) && in_array( $post_type, $supported_types ) ? true : false;
}

/**
 * Fetch the user signup date to compare against content being displayed.
 *
 * @param  integer $user_id  The user ID we want to get information for.
 *
 * @return mixed
 */
function get_user_signup_date( $user_id = 0 ) {

	// Bail if no ID can be found.
	if ( empty( $user_id ) ) {
		return false;
	}

	// First check for the usermeta key.
	$signup = get_user_meta( $user_id, Core\META_PREFIX . 'signup_date', true );

	// If we have no stored date, get the item from the user object.
	if ( empty( $signup ) ) {

		// Fetch WP_User object.
		$user_data  = get_user_by( 'ID', $user_id );

		// Bail without the user object.
		if ( empty( $user_data ) || ! $user_data->exists() ) {
			return false;
		}

		// Now set our signup time.
		$signup = strtotime( $user_data->user_registered );
	}

	// Send it back filtered with the user ID.
	return apply_filters( 'dppress_user_signup', absint( $signup ), $user_id );
}

/**
 * Compare the signup date to the drip schedule.
 *
 * @param  integer $post_id  The content ID we wanna check.
 *
 * @return mixed
 */
function compare_drip_dates( $post_id = 0 ) {

	// Bail if we dont have a post ID.
	if ( empty( $post_id ) ) {
		return;
	}

	// Get our post publish date.
	$post_stamp = Helpers\get_published_datestamp( $post_id );

	// Bail if we dont have a post date.
	if ( empty( $post_stamp ) ) {
		return;
	}

	// Pull our time now.
	$time_now   = apply_filters( Core\HOOK_PREFIX . 'drip_baseline', current_time( 'timestamp', 1 ) );

	// Bail on scheduled posts.
	if ( absint( $post_stamp ) > absint( $time_now ) ) {
		return false;
	}

	// attempt to get drip calculation
	$drip_date  = Helpers\build_drip_date( $post_id, get_current_user_id() );

	// return true if we've passed our drip duration
	if ( absint( $time_now ) >= absint( $drip_date ) ) {

		// Set my return array.
		return array(
			'display' => true,
			'item_id' => $post_id,
		);
	}

	// send back our message
	if ( absint( $time_now ) < absint( $drip_date ) ) {

		// Set my return array.
		return array(
			'display'   => false,
			'item_id'   => $post_id,
			'remaining' => absint( $drip_date ) - absint( $time_now ),
			'message'   => Helpers\get_pending_message( $drip_date, $post_id ),
		);
	}

	// Nothing left to compare.
}

/**
 * Preset our displayed date format modification with filter.
 *
 * @param  boolean $include_time  Optional flag to include the time
 *
 * @return string
 */
function get_date_format( $include_time = false ) {

	// Get both our formats.
	$date_format    = get_option( 'date_format' );
	$time_format    = get_option( 'time_format' );

	// Set up how we want the display.
	$format_display = ! empty( $include_time ) ? $date_format . ' ' . $time_format : $date_format;

	// Send back my date format.
	return apply_filters( Core\HOOK_PREFIX . 'date_format', $format_display, $include_time );
}

/**
 * Cacluate the seconds for drips.
 *
 * @param  integer $count  What the count was.
 * @param  string  $range  What the specific range was.
 *
 * @return integer
 */
function calculate_content_drip( $count = 0, $range = '' ) {

	// Bail if neither value came through.
	if ( empty( $count ) || empty( $range ) ) {
		return;
	}

	// Fetch the ranges we have.
	$ranges	= Helpers\get_drip_ranges();

	// Check for array key.
	if ( empty( $ranges ) || ! array_key_exists( $range, $ranges ) ) {
		return;
	}

	// Process the actual caluculation or false if we don't have it.
	return ! empty( $ranges[ $range ]['value'] ) ? absint( $count ) * absint( $ranges[ $range ]['value'] ) : false;
}
