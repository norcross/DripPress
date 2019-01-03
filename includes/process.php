<?php
/**
 * Some specific processing functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Process;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;

/**
 * Take the user signup date and set it as meta.
 *
 * @param  boolean $run_global  Whether to run this on ALL users.
 *
 * @return void
 */
function set_user_signup_meta( $run_global = false ) {

	// Check the global flag and set the meta accordingly.
	$do_global  = false !== $run_global ? array() : array( 'key' => Core\META_PREFIX . 'signup_stamp', 'value'   => 'bug #23268', 'compare' => 'NOT EXISTS' );

	// Set the args for a user lookup.
	$user_args  = array(
		'fields'     => array( 'ID', 'user_registered' ),
		'meta_query' => array( $do_global ),
	);

	// Now attempt to get the users.
	$user_query = get_users( $user_args );

	// Bail without any users.
	if ( empty( $user_query ) ) {
		return false;
	}

	// Set the clean array to create our meta values.
	$user_setup = wp_list_pluck( $user_query, 'user_registered', 'ID' );

	// Now loop and update the timestamp usermeta.
	foreach ( $user_setup as $user_id => $registered_date ) {

		// Set our timestamp and then set it to midnight.
		$signup_stamp   = strtotime( 'today', strtotime( $registered_date ) );

		// Update the stamps.
		update_user_meta( $user_id, Core\META_PREFIX . 'signup_stamp', absint( $signup_stamp ) );
	}

	// And we are done.
	return count( $user_query );
}

/**
 * Set the initial value for a drip sort.
 */
function set_initial_drip_sort() {

	// Call the global database.
	global $wpdb;

	// Set my table name.
	$table_name = $wpdb->prefix . 'posts';

	// Set up our query.
	$setup_args = $wpdb->prepare("
		SELECT   ID
		FROM     $table_name
		WHERE    post_status = '%s'
		ORDER BY post_date DESC
	", esc_sql( 'publish' ) );

	// Process the query.
	$post_query = $wpdb->get_col( $setup_args );

	// Bail without any users.
	if ( empty( $post_query ) ) {
		return false;
	}

	// Now loop my query and set the initial meta.
	foreach ( $post_query as $post_id ) {
		update_post_meta( $post_id, Core\META_PREFIX . 'drip', 0 );
	}

	// And we are done.
	return count( $post_query );
}

/**
 * Store the single user drip progress.
 *
 * @param  integer $post_id  The post ID being saved.
 * @param  integer $user_id  The ID of the user doing the saving.
 *
 * @return void
 */
function set_single_user_drip_progress( $post_id = 0, $user_id = 0 ) {

	// Make sure we have each one.
	if ( empty( $post_id ) || empty( $user_id ) ) {
		return;
	}

	// Handle the setup.
	$setup_args = array( $post_id => current_time( 'timestamp' ) );

	// Attempt to get the array of all the statuses they've done.
	$maybe_has  = get_user_meta( $user_id, Core\META_PREFIX . 'drip_status', true );

	// If it's empty, add it. otherwise, merge it.
	$status_set = empty( $maybe_has ) ? $setup_args : $setup_args + $maybe_has;

	// Now store the new value.
	update_user_meta( $user_id, Core\META_PREFIX . 'drip_status', $status_set );
}

/**
 * Save the single meta for content.
 *
 * @param  integer $post_id     The post ID being saved.
 * @param  integer $drip_count  What our count is.
 * @param  string  $drip_range  The range we have.
 *
 * @return void
 */
function set_single_drip_meta( $post_id = 0, $drip_count = 0, $drip_range = '' ) {

	// Make sure we have each one.
	if ( empty( $post_id ) || empty( $drip_count ) || empty( $drip_range ) ) {
		return;
	}

	// Update the three single meta keys.
	update_post_meta( $post_id, Core\META_PREFIX . 'live', 1 );
	update_post_meta( $post_id, Core\META_PREFIX . 'count', $drip_count );
	update_post_meta( $post_id, Core\META_PREFIX . 'range', $drip_range );

	// Now get the range value for the drip.
	$drip_seconds   = Helpers\get_values_from_range( $drip_range, 'seconds' );

	// Set my drip length.
	$drip_length    = absint( $drip_count ) === 1 ? absint( $drip_seconds ) : absint( $drip_seconds ) * absint( $drip_count );

	// And update the meta.
	update_post_meta( $post_id, Core\META_PREFIX . 'drip', $drip_length );
}

/**
 * Get just the count of content set live.
 *
 * @return integer
 */
function get_dripped_content_count() {

	// Call the global database.
	global $wpdb;

	// Set my table name.
	$table_name = $wpdb->prefix . 'postmeta';

	// Set up our query.
	$setup_args = $wpdb->prepare("
		SELECT   post_id
		FROM     $table_name
		WHERE    meta_key = '%s'
		AND      meta_value = '%d'
	", esc_sql( Core\META_PREFIX . 'live' ), absint( 1 ) );

	// Process the query.
	$post_query = $wpdb->get_col( $setup_args );

	// Return the count (or zero).
	return ! empty( $post_query ) && ! is_wp_error( $post_query ) ? count( $post_query ) : 0;
}

/**
 * Get all the content that has a drip applied to it.
 *
 * @param  boolean $format  Whether to format the return or just give back all the objects.
 * @param  array   $custom  Any custom args to include in the query.
 *
 * @return array
 */
function get_all_dripped_content( $format = true, $custom = array() ) {

	// Set up the args to get content.
	$basic_args = array(
		'nopaging'    => true,
		'post_type'   => 'post',
		'post_status' => 'publish',
		'meta_query'  => array(
			array(
				'key'   => Core\META_PREFIX . 'live',
				'value' => 1,
			)
		),
		'meta_key'    => Core\META_PREFIX . 'drip',
		'order'       => 'ASC',
		'orderby'     => 'meta_value_num',
	);

	// Attempt to merge any custom.
	$setup_args = ! empty( $custom ) ? wp_parse_args( $custom, $basic_args ) : $basic_args;

	// Get the items we have.
	$drip_items = get_posts( $setup_args );

	// Bail if nothing exists or a WP_Error.
	if ( empty( $drip_items ) || is_wp_error( $drip_items ) ) {
		return false;
	}

	// Return the drip items without formatting.
	if ( ! $format ) {
		return $drip_items;
	}

	// Set an empty for our return data.
	$items  = array();

	// Now loop my items and start parsing.
	foreach ( $drip_items as $index => $post_object ) {

		// Get my drip meta.
		$drip_meta  = Helpers\get_content_drip_meta( $post_object->ID );

		// First add the main postdata.
		$post_data  = (array) $post_object;

		// Now add the meta.
		$items[ $index ] = ! empty( $drip_meta ) ? $post_data + $drip_meta : $post_data;

		// And add our permalink.
		$items[ $index ]['permalink'] = get_permalink( $post_object->ID );
	}

	// And return the array of items.
	return $items;
}

/**
 * The database function for delete DB keys on users.
 *
 * @return integer
 */
function purge_user_signup_meta() {

	// Call global DB class.
	global $wpdb;

	// Set our table.
	$table_name = $wpdb->usermeta;

	// Prepare my query.
	$setup_args = $wpdb->prepare("
		DELETE FROM $table
		WHERE meta_key LIKE '%s'
		",
		esc_sql( Core\META_PREFIX . '%' )
	);

	// Run SQL query.
	$user_query = $wpdb->query( $setup_args );

	// Send it back.
	return ! empty( $user_query ) ? absint( $user_query ) : 0;
}

/**
 * The database function for delete DB keys on content.
 *
 * @return integer
 */
function purge_content_drip_meta() {

	// Call global DB class.
	global $wpdb;

	// Set our table.
	$table_name = $wpdb->postmeta;

	// Prepare my query.
	$setup_args = $wpdb->prepare("
		DELETE FROM $table
		WHERE meta_key LIKE '%s'
		",
		esc_sql( Core\META_PREFIX . '%' )
	);

	// Run SQL query.
	$post_query = $wpdb->query( $setup_args );

	// Send it back.
	return ! empty( $post_query ) ? absint( $post_query ) : 0;
}

/**
 * Purge the post meta tied to a single ID.
 *
 * @param  integer $post_id  The post ID to delete from.
 * @param  boolean $reset    Whether to reset the drip value.
 *
 * @return void
 */
function purge_single_post_meta( $post_id = 0, $reset = true ) {

	// Bail without a post ID.
	if ( empty( $post_id ) ) {
		return;
	}

	// Delete all the standard stuff.
	delete_post_meta( $post_id, Core\META_PREFIX . 'live' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'count' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'range' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'drip' );

	// Reset the drip if requested.
	if ( $reset ) {
		update_post_meta( $post_id, Core\META_PREFIX . 'drip', 0 );
	}
}
