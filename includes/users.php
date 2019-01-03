<?php
/**
 * Our user drived functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Users;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;

/**
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\set_user_drip_progress' );
add_action( 'manage_users_custom_column', __NAMESPACE__ . '\user_columns_data', 10, 3 );
add_filter( 'manage_users_columns', __NAMESPACE__ . '\user_columns_display' );

/**
 * Store the progress of a user when they submit something.
 *
 * @return void
 */
function set_user_drip_progress() {

	// Don't run on the admin.
	if ( is_admin() ) {
		return;
	}

	// Do the constants check.
	$constants  = Utilities\check_constants_for_process();

	// Bail out if we hit a constant.
	if ( ! $constants ) {
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

	// Now store the new value.
	Process\set_single_user_drip_progress( $post_id, $user_id );

	// And process the URL redirect.
	wp_redirect( esc_url( get_permalink( $post_id ) ), 302 );
	exit();
}

/**
 * Get our custom user data for columns.
 *
 * @param  mixed   $value    The value being passed.
 * @param  string  $column   The name of the column.
 * @param  integer $user_id  Which user ID we have.
 *
 * @return mixed
 */
function user_columns_data( $value, $column, $user_id ) {

	// Handle the column switch.
	switch ( $column ) {

		// Handle our registration length.
		case 'user-registered':

			// Set the length text.
			$signup_stamp   = Utilities\get_user_signup_date( $user_id, true );

			// Set my format.
			$signup_format  = apply_filters( Core\HOOK_PREFIX . 'user_signup_display_format', 'Y/m/d' );

			// And set it as a value.
			$value  = '<p class="user-registered"><abbr title="' . date( 'Y/m/d g:i:s a', $signup_stamp ) . '">' . date( $signup_format, $signup_stamp ) . '</abbr></p>';

 			// And break.
 			break;

		// End all case breaks.
	}

	// Return the column value.
	return $value;
}

/**
 * Add our custom column to the display.
 *
 * @param  array $columns  The current array of columns.
 *
 * @return array
 */
function user_columns_display( $columns ) {

	// Add our column if it doesn't already appear.
	if ( ! isset( $columns['user-registered'] ) ) {

		// Set the posts as a variable.
		$posts_column   = $columns['posts'];

		// Unset the posts.
		unset( $columns['posts'] );

		// Add our new one.
		$columns['user-registered'] = __( 'Registered', 'drip-press' );

		// Add back the posts.
		$columns['posts']   = $posts_column;
	}

	// Return the array of columns.
	return $columns;
}
