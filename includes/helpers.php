<?php
/**
 * Our helper and data functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Helpers;

// Set our alias items.
use DripPress as Core;
use DripPress\Utilities as Utilities;

/**
 * Get an array of the supported post types.
 *
 * @return array
 */
function get_supported_types() {
	return apply_filters( Core\HOOK_PREFIX . 'supported_types', array( 'post' ) );
}

/**
 * Get one (or more) values based on a range.
 *
 * @param  string $range   Which range we want.
 * @param  string $single  A single value. Blank will return the array.
 *
 * @return mixed
 */
function get_values_from_range( $range = '', $single = '' ) {

	// Bail without a range.
	if ( empty( $range ) ) {
		return false;
	}

	// Set an empty.
	$setup_args = array();

	// Now run the switch.
	switch ( sanitize_key( $range ) ) {

		case 'hour' :

			// Create the array and break.
			$setup_args = array( 'relative' => 'now', 'seconds' => HOUR_IN_SECONDS );
			break;

		case 'day' :

			// Create the array and break.
			$setup_args = array( 'relative' => 'tomorrow', 'seconds' => DAY_IN_SECONDS );
			break;

		case 'week' :

			// Get my starting week.
			$week_start = get_stored_week_start();

			// Create the array and break.
			$setup_args = array( 'relative' => $week_start, 'seconds' => WEEK_IN_SECONDS );
			break;

		// End all case breaks.
	}

	// Allow the args to be filtered.
	$setup_args = apply_filters( Core\HOOK_PREFIX . 'range_values_setup_args', $setup_args, $range, $single );

	// Return the entire setup arg if no single requested.
	if ( empty( $single ) ) {
		return ! empty( $setup_args ) ? $setup_args : false;
	}

	// Return the single key or false.
	return isset( $setup_args[ $single ] ) ? $setup_args[ $single ] : false;
}

/**
 * Get the written name of the item selected.
 *
 * @return string
 */
function get_stored_week_start() {

	// Set the array of days.
	$dayset = array(
		'sunday',
		'monday',
		'tuesday',
		'wednesday',
		'thursday',
		'friday',
		'saturday',
	);

	// Now get the "start of week" value.
	$stored = get_option( 'start_of_week', 1 );

	// Now return the string.
	return $dayset[ $stored ];
}

/**
 * Get the date a user can access dripped content.
 *
 * @param  string  $range   What range we are comparing.
 * @param  integer $signup  The timestamp of the user's signup.
 * @param  integer $count   The increment we are applying to the range value.
 *
 * @return integer          The timestamp.
 */
function get_user_access_date( $range = '', $signup = 0, $count = 1 ) {

	// Return false without a range or a signup.
	if ( empty( $range ) || empty( $signup ) ) {
		return false;
	}

	// Get my range values.
	$range_values   = get_values_from_range( $range );

	// Bail if we don't have the pieces in the range.
	if ( empty( $range_values['relative'] ) || empty( $range_values['seconds'] ) ) {
		return false;
	}

	// Calculate what the first stamps would be.
	$start_stamp  = strtotime( esc_attr( $range_values['relative'] ), $signup );

	// Set the incrementer.
	$incrementer  = 2 === absint( $count ) ? $range_values['seconds'] : absint( $range_values['seconds'] ) * ( absint( $count ) - 1 );

	// Now run the switch.
	switch ( absint( $count ) ) {

		case 1 :
			return absint( $start_stamp );
			break;

		case 2 :
			return absint( $start_stamp ) + absint( $incrementer );
			break;

		default :

			return absint( $start_stamp ) + absint( $incrementer );
			break;

		// End all case breaks.
	}
}

/**
 * Get the completed status on a particular post.
 *
 * @param  integer $post_id  The post ID being checked.
 *
 * @return mixed
 */
function get_user_content_status( $post_id = 0 ) {

	// Don't run on non-logged in users. Maybe?
	if ( empty( $post_id ) ) {
		return false;
	}

	// Attempt to get the array of all the statuses they've done.
	$status = get_user_meta( get_current_user_id(), Core\META_PREFIX . 'drip_status', true );

	// Bail if no status array exists.
	if ( empty( $status ) ) {
		return false;
	}

	// Now return either the timestamp it was done or false.
	return ! empty( $status[ $post_id ] ) ? absint( $status[ $post_id ] ) : false;
}

/**
 * Get all the meta for a single.
 *
 * @param  integer $post_id  The post ID we're checking.
 * @param  string  $single   Optional flag for a single key from the array.
 *
 * @return array
 */
function get_content_drip_meta( $post_id = 0, $single = '' ) {

	// Bail without a post ID.
	if ( empty( $post_id ) ) {
		return false;
	}

	// Create the data array.
	$setup_args = array(
		'live'  => get_post_meta( $post_id, Core\META_PREFIX . 'live', true ),
		'count' => get_post_meta( $post_id, Core\META_PREFIX . 'count', true ),
		'range' => get_post_meta( $post_id, Core\META_PREFIX . 'range', true ),
		'drip'  => get_post_meta( $post_id, Core\META_PREFIX . 'drip', true ),
	);

	// Return our entire array if requested.
	if ( empty( $single ) ) {
		return $setup_args;
	}

	// Return the single key or false.
	return isset( $setup_args[ $single ] ) ? $setup_args[ $single ] : false;
}

/**
 * Set up the possible ranges to apply a numerical value to.
 *
 * @param  string $single  Whether to return just a single item.
 *
 * @return array
 */
function get_drip_ranges( $single = '' ) {

	// Set up my array.
	$ranges = array(

		'hour' => array(
			'single' => __( 'Hour', 'drip-press' ),
			'plural' => __( 'Hours', 'drip-press' ),
			'blank'  => __( 'Hour(s)', 'drip-press' ),
			'value'  => HOUR_IN_SECONDS
		),

		'day' => array(
			'single' => __( 'Day', 'drip-press' ),
			'plural' => __( 'Days', 'drip-press' ),
			'blank'  => __( 'Day(s)', 'drip-press' ),
			'value'  => DAY_IN_SECONDS
		),

		'week' => array(
			'single' => __( 'Week', 'drip-press' ),
			'plural' => __( 'Weeks', 'drip-press' ),
			'blank'  => __( 'Week(s)', 'drip-press' ),
			'value'  => WEEK_IN_SECONDS
		),

	);

	// Set our ranges.
	$ranges = apply_filters( Core\HOOK_PREFIX . 'drip_ranges', $ranges );

	// Return the whole thing if no single is requested.
	if ( empty( $single ) ) {
		return $ranges;
	}

	// Return the single piece.
	return array_key_exists( $single, $ranges ) ? $ranges[ $single ] : false;
}

/**
 * Get the appropriate label for a range.
 *
 * @param  string $range   Which range we want.
 * @param  string $format  The format. Single or plural.
 *
 * @return string
 */
function get_single_drip_label( $range = '', $format = 'plural' ) {

	// Get my array of ranges.
	$range_array   = get_drip_ranges( $range );

	// Bail without an array.
	if ( empty( $range_array ) ) {
		return false;
	}

	// Return the thing.
	return ! empty( $range_array[ $format ] ) ? $range_array[ $format ] : '';
}

/**
 * The display message for when something is pending.
 *
 * @param  integer $timestamp     The timestamp of the drip.
 * @param  string  $display       Where the message is being displayed.
 * @param  array   $message_args  Optional args we can pass.
 *
 * @return string
 */
function get_pending_message( $timestamp = 0, $display = 'content', $message_args = array() ) {

	// Bail without a drip date.
	if ( empty( $timestamp ) ) {
		return;
	}

	// Set an empty.
	$display_text   = '';

	// Handle the content type first.
	if ( 'content' === sanitize_text_field( $display ) ) {

		// Get our date all formatted.
		$formatted_date = date( Utilities\get_date_format(), $timestamp );

		// Set the text.
		$display_text   = sprintf( __( 'This content will be available to you on %s', 'drip-press' ), esc_attr( $formatted_date ) );
	}

	// Handle the list type second.
	if ( 'list' === sanitize_text_field( $display ) ) {

		// Get our date all formatted.
		$formatted_date = date( 'n/d', $timestamp );

		// Set the text.
		$display_text   = sprintf( __( 'Will become available on %s', 'drip-press' ), esc_attr( $formatted_date ) );
	}

	// Send it back filtered.
	return apply_filters( Core\HOOK_PREFIX . 'pending_message', $display_text, $timestamp, $display, $message_args );
}

/**
 * The display message for when something is completed.
 *
 * @param  integer $timestamp     The timestamp of the drip.
 * @param  boolean $wrap_text     Whether to wrap the text output or not.
 * @param  array   $message_args  Optional args we can pass.
 *
 * @return string
 */
function get_completed_message( $timestamp = 0, $wrap_text = false, $message_args = array() ) {

	// Bail without a drip date.
	if ( empty( $timestamp ) ) {
		return;
	}

	// Get our date all formatted.
	$formatted_date = date( Utilities\get_date_format(), $timestamp );

	// Construct our sentence.
	$display_text   = sprintf( __( 'This content was marked completed on %s', 'drip-press' ), esc_attr( $formatted_date ) );

	// Run it back filtered.
	$display_text   = apply_filters( Core\HOOK_PREFIX . 'completed_message', $display_text, $timestamp, $message_args );

	// Return it wrapped. Or not.
	return false !== $wrap_text ? '<p class="dppress-message dppress-message-completed">' . esc_attr( $display_text ) . '</p>' : $display_text;
}
