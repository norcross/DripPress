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
				'key'     => Core\META_PREFIX . 'signup_date',
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
	$user_array = wp_list_pluck( $user_query, 'user_registered', 'ID' );

	// Now loop and update the timestamp usermeta.
	foreach ( $user_array as $user_id => $registered_date ) {
		update_user_meta( $user_id, Core\META_PREFIX . 'signup_date', strtotime( $registered_date ) );
	}

	// And we are done.
	return true;
}

/**
 * The database function for delete DB keys.
 *
 * @return void
 */
function purge_user_signup_meta() {

	// Call global DB class.
	global $wpdb;

	// Set our table.
	$table  = $wpdb->postmeta;

	// Prepare my query.
	$setup  = $wpdb->prepare("
		DELETE FROM $table
		WHERE meta_key = %s",
		esc_sql( Core\META_PREFIX . 'signup_date' )
	);

	// Run SQL query.
	$query = $wpdb->query( $setup );

	// Send it back.
	return $query;
}

/**
 * Purge the post meta tied to a single ID.
 *
 * @param  integer $post_id       The post ID to delete from.
 * @param  boolean $include_sort  Whether to also delete the sort data.
 *
 * @return void
 */
function purge_single_post_meta( $post_id = 0, $include_sort = false ) {

	// Bail without a post ID.
	if ( empty( $post_id ) ) {
		return;
	}

	// Delete all the standard stuff.
	delete_post_meta( $post_id, Core\META_PREFIX . 'live' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'count' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'range' );
	delete_post_meta( $post_id, Core\META_PREFIX . 'drip' );

	// Delete the sort info if also requested.
	if ( ! empty( $include_sort ) ) {
		delete_post_meta( $post_id, Core\META_PREFIX . 'sort' );
	}
}
