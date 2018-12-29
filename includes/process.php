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

/**
 * Take the user signup date and set it as meta.
 *
 * @return void
 */
function set_user_signup_meta() {

	// Set the args for a user lookup.
	$user_args  = array(
		'fields'     => array( 'ID', 'user_registered' ),
		'meta_query' => array(
			array(
				'key'     => Core\META_PREFIX . 'signup_stamp',
				'value'   => 'bug #23268',
				'compare' => 'NOT EXISTS',
			),
		),
	);

	// Now attempt to get the users.
	$user_query = get_users( $user_args );

	// Bail without any users.
	if ( empty( $user_query ) ) {
		return;
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
	return true;
}

/**
 * Set the initial value for a drip sort.
 */
function set_initial_drip_sort() {

	// Call the global database.
	global $wpdb;

	// Set my table name.
	$table  = $wpdb->prefix . 'postmeta';

	// Set up our query.
	$setup  = $wpdb->prepare("
		SELECT   post_id
		FROM     $table
		WHERE    post_status = '%s'
		ORDER BY post_date DESC
	", esc_sql( 'publish' ) );

	// Process the query.
	$query  = $wpdb->get_col( $setup );

	// Bail without any users.
	if ( empty( $query ) ) {
		return;
	}

	// Now loop my query and set the initial meta.
	foreach ( $query as $post_id ) {
		update_post_meta( $post_id, Core\META_PREFIX . 'drip', 0 );
	}

	// And we are done.
	return true;
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
	$table  = $wpdb->usermeta;

	// Prepare my query.
	$setup  = $wpdb->prepare("
		DELETE FROM $table
		WHERE meta_key = %s
		",
		esc_sql( Core\META_PREFIX . 'signup_stamp' )
	);

	// Run SQL query.
	$query = $wpdb->query( $setup );

	// Send it back.
	return ! empty( $query ) ? absint( $query ) : 0;
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
	$table  = $wpdb->postmeta;

	// Prepare my query.
	$setup  = $wpdb->prepare("
		DELETE FROM $table
		WHERE meta_key LIKE '%s'
		",
		esc_sql( Core\META_PREFIX . '%' )
	);

	// Run SQL query.
	$query = $wpdb->query( $setup );

	// Send it back.
	return ! empty( $query ) ? absint( $query ) : 0;
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
