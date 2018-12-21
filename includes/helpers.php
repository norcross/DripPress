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
	$setup  = array(
		'live'  => get_post_meta( $post_id, Core\META_PREFIX . 'live', true ),
		'count' => get_post_meta( $post_id, Core\META_PREFIX . 'count', true ),
		'range' => get_post_meta( $post_id, Core\META_PREFIX . 'range', true ),
		'drip'  => get_post_meta( $post_id, Core\META_PREFIX . 'drip', true ),
		'sort'  => get_post_meta( $post_id, Core\META_PREFIX . 'sort', true ),
	);

	// Return our entire array if requested.
	if ( empty( $single ) ) {
		return $setup;
	}

	// Return the single key or false.
	return isset( $setup[ $single ] ) ? $setup[ $single ] : false;
}

/**
 * Get a list of items to display.
 *
 * @param  string  $term   Which term we wanna look up.
 * @param  string  $tax    The taxonomy it belongs to.
 * @param  integer $count  How many to retrieve.
 * @param  string  $types  What post type(s) to look.
 *
 * @return array
 */
function get_drip_list( $term, $tax = 'post_tag', $count = 5, $types = 'post' ) {

	// Set post types to array if passed.
	$types  = ! is_array( $types ) ? explode( ',', $types ) : $types;

	// Set up the args for my query.
	$setup  = array(
		'fields'			=> 'ids',
		'post_type'			=> $types,
		'posts_per_page'	=> absint( $count ),
		'meta_key'			=> Core\META_PREFIX . 'sort',
		'order'				=> 'ASC',
		'orderby'			=> 'meta_value_num',
		'tax_query'			=> array(
			array(
				'taxonomy'	=> $tax,
				'field'		=> 'slug',
				'terms'		=> $term
			),
		)
	);

	// Allow the args to be filtered.
	$setup  = apply_filters( Core\HOOK_PREFIX . 'drip_list_args', $setup, $term );

	// Fetch my posts.
	$query  = get_posts( $setup );

	// Return what we've got.
	return ! empty( $query ) && ! is_wp_error( $query ) ? $query : false;
}

/**
 * Fetch the content publish date for use in the drip comparison with available filter.
 *
 * @param  integer $post_id  Current post ID being checked.
 *
 * @return string  $date     Signup date in UNIX time.
 */
function get_published_datestamp( $post_id = 0 ) {

	// Check for current post if no ID is passed.
	$post_id    = ! empty( $post_id ) ? $post_id : get_the_ID();

	// Bail if no post ID can be found.
	if ( empty( $post_id ) ) {
		return;
	}

	// First check for GMT.
	$stored_publish = get_post_field( 'post_date_gmt', $post_id, 'raw' );

	// Fetch the local post date if GMT is missing.
	if ( ! $stored_publish ) {
		$stored_publish = get_post_field( 'post_date', $post_id, 'raw' );
	}

	// Send it back filtered.
	return apply_filters( Core\HOOK_PREFIX . 'publish_datestamp', strtotime( $stored_publish ), $post_id );
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
			'value'  => HOUR_IN_SECONDS
		),

		'day' => array(
			'single' => __( 'Day', 'drip-press' ),
			'plural' => __( 'Days', 'drip-press' ),
			'value'  => DAY_IN_SECONDS
		),

		'week' => array(
			'single' => __( 'Week', 'drip-press' ),
			'plural' => __( 'Weeks', 'drip-press' ),
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
 * Fetch the drip date (if calculated), or attempt to make the calculation.
 *
 * @param  integer $post_id  The post ID we wanna check.
 *
 * @return mixed
 */
function get_drip_value( $post_id = 0 ) {

	// Just bail if no ID.
	if ( empty( $post_id ) ) {
		return false;
	}

	// Get the stored meta.
	$stored_values  = get_content_drip_meta( $post_id );

	// Bail without any meta to work with.
	if ( empty( $stored_values ) ) {
		return false;
	}

	// Return the drip if already calculated.
	if ( ! empty( $stored_values['drip'] ) ) {
		return $stored_values['drip'];
	}

	// No calculated value, get the meta and attempt to do it.
	return Utilities\calculate_content_drip( $stored_values['count'], $stored_values['range'] );
}

/**
 * Handle the actual calculation of the drip date to be used.
 *
 * @param  integer $post_id  Current post ID being checked.
 * @param  integer $user_id  Current user ID being checked.
 *
 * @return string  $date     Drip date in UNIX time.
 */
function build_drip_date( $post_id = 0, $user_id = 0 ) {

	// Bail if we dont have a user ID and a post ID.
	if ( empty( $post_id ) || empty( $user_id ) ) {
		return false;
	}

	// Try to get our calculated drip value.
	$drip_value = get_drip_value( $post_id );

	// Bail without a drip value.
	if ( ! $drip_value ) {
		return false;
	}

	// Get our user signup date.
	$user_date	= Utilities\get_user_signup_date( $user_id );

	// Add the drip duration to the user signup date.
	$drip_date  = absint( $user_date ) + absint( $drip_value );

	// Send it back filtered.
	return apply_filters( 'dppress_drip_date', $drip_date, $post_id, $user_id );
}

/**
 * The display message for when something is pending.
 *
 * @param  integer $timestamp  The timestamp of the drip.
 * @param  integer $post_id    Which item we're checking.
 *
 * @return string
 */
function get_pending_message( $timestamp = 0, $post_id = 0 ) {

	// Bail without a drip date.
	if ( empty( $timestamp ) ) {
		return;
	}

	// Get our date all formatted.
	$formatted_date = date( Utilities\get_date_format(), $timestamp );

	// set a default
	$display_text   = sprintf( __( 'This content will be available %s', 'drip-press' ), esc_attr( $formatted_date ) );

	// Send it back filtered.
	return apply_filters( Core\HOOK_PREFIX . 'pending_message', $display_text, $timestamp, $post_id );
}
