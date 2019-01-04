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
 * Check the constants we know about during an Ajax call.
 *
 * @return boolean
 */
function check_constants_for_process( $include_ajax = true ) {

	// Bail out if running an autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return false;
	}

	// Bail if we are doing a REST API request.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	// Include the possible Ajax check.
	if ( ! empty( $include_ajax ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return false;
	}

	// Passed them all. Return true.
	return true;
}

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
	if ( ! $screen || ! is_object( $screen ) ) {
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
 * @param  boolean $store    Whether to store the value if we don't have it.
 *
 * @return mixed
 */
function get_user_signup_date( $user_id = 0, $store = false ) {

	// Bail if no ID can be found.
	if ( empty( $user_id ) ) {
		return false;
	}

	// First check for the usermeta key.
	$signup = get_user_meta( $user_id, Core\META_PREFIX . 'signup_stamp', true );

	// If we have no stored date, get the item from the user object.
	if ( empty( $signup ) ) {

		// Fetch WP_User object.
		$user_data  = get_user_by( 'ID', $user_id );

		// Bail without the user object.
		if ( empty( $user_data ) || ! $user_data->exists() ) {
			return false;
		}

		// Set our timestamp and then set it to midnight.
		$signup = strtotime( 'today', strtotime( $user_data->user_registered ) );

		// Store the date if requested.
		if ( ! empty( $store ) ) {
			update_user_meta( $user_id, Core\META_PREFIX . 'signup_stamp', absint( $signup ) );
		}
	}

	// Send it back filtered with the user ID.
	return apply_filters( Core\HOOK_PREFIX . 'user_signup', absint( $signup ), $user_id );
}

/**
 * Compare the signup date to the drip schedule.
 *
 * @param  integer $post_id  The content ID we wanna check.
 * @param  string  $display  Where this is being displayed. Impacts the message.
 *
 * @return mixed
 */
function compare_drip_signup_dates( $post_id = 0, $display = 'content' ) {

	// Bail if we dont have a post ID or a drip.
	if ( empty( $post_id ) ) {
		return;
	}

	// Get the stored meta.
	$stored_values  = Helpers\get_content_drip_meta( $post_id );

	// Bail right away if we aren't set to live or have a range value.
	if ( empty( $stored_values['live'] ) || empty( $stored_values['range'] ) || empty( $stored_values['count'] ) ) {
		return false;
	}

	// Get the user signup date.
	$signup_stamp   = get_user_signup_date( get_current_user_id() );

	// Bail without a signup date.
	if ( ! $signup_stamp ) {
		return false;
	}

	// Get my current timestamp.
	$today_stamp    = strtotime( 'today', time() );

	// Get the timestamp for access.
	$access_stamp   = Helpers\get_user_access_date( $stored_values['range'], $signup_stamp, $stored_values['count'] );

	// Return true if we've passed our drip duration.
	if ( absint( $today_stamp ) >= absint( $access_stamp ) ) {

		// Set my return array.
		return array(
			'display'    => true,
			'access'     => $access_stamp,
			'content_id' => $post_id,
			'remaining'  => 0,
			'message'    => Helpers\get_completed_message( $access_stamp ),
		);
	}

	// Send back our message.
	if ( absint( $today_stamp ) < absint( $access_stamp ) ) {

		// Set my message args.
		$message_args   = wp_parse_args( $stored_values, array( 'id' => $post_id, 'signup' => $signup_stamp ) );

		// Set my return array.
		return array(
			'display'    => false,
			'access'     => $access_stamp,
			'content_id' => $post_id,
			'remaining'  => absint( $access_stamp ) - absint( $today_stamp ),
			'message'    => Helpers\get_pending_message( $access_stamp, $display, $message_args ),
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
