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
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\set_user_drip_progress' );

/**
 * Store the progress of a user when they submit something.
 *
 * @return void
 */
function set_user_drip_progress() {

	// Make sure we aren't using autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Bail out if running an ajax.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// Make sure we have our prompt button.
	if ( empty( $_POST['dppress-prompt-button'] ) || 'complete' !== sanitize_text_field( $_POST['dppress-prompt-button'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST[ Core\NONCE_PREFIX . 'status_name'] ) || ! wp_verify_nonce( $_POST[ Core\NONCE_PREFIX . 'status_name'], Core\NONCE_PREFIX . 'status_action' ) ) {
		wp_die( __( 'Nonce failed. Why?', 'drip-press' ) );
	}

	// Make sure we have our IDs.
	if ( empty( $_POST['dppress-prompt-post-id'] ) || empty( $_POST['dppress-prompt-user-id'] ) ) {
		return false;
	}

	// Set my IDs.
	$post_id    = absint( $_POST['dppress-prompt-post-id'] );
	$user_id    = absint( $_POST['dppress-prompt-user-id'] );

	// Handle the setup.
	$setup_args = array( $post_id => current_time( 'timestamp' ) );

	// Attempt to get the array of all the statuses they've done.
	$maybe_has  = get_user_meta( $user_id, Core\META_PREFIX . 'drip_status', true );

	// If it's empty, add it. otherwise, merge it.
	$status_set = empty( $maybe_has ) ? $setup_args : $setup_args + $maybe_has;

	// Now store the new value.
	update_user_meta( $user_id, Core\META_PREFIX . 'drip_status', $status_set );

	// And process the URL redirect.
	wp_redirect( esc_url( get_permalink( $post_id ) ), 302 );
	exit();
}

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
	$table  = $wpdb->prefix . 'posts';

	// Set up our query.
	$setup  = $wpdb->prepare("
		SELECT   ID
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
 * Get all the content that has a drip applied to it.
 *
 * @param  boolean $format  Whether to format the return or just give back all the objects.
 *
 * @return array
 */
function get_all_dripped_content( $format = true ) {

	// Set up the args to get content.
	$setup_args = array(
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
	$table  = $wpdb->usermeta;

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
