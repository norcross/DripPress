<?php
/**
 * Our Ajax related items.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Ajax;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_action( 'wp_ajax_save_drip_bulkedit', __NAMESPACE__ . '\save_drip_bulkedit' );
add_action( 'wp_ajax_dppress_set_user_status', __NAMESPACE__ . '\set_user_status' );

/**
 * Handle saving the bulk edit drips.
 *
 * @return void
 */
function save_drip_bulkedit() {

	// Only run this on the admin side.
	if ( ! is_admin() ) {
		wp_die( __( 'You are not supposed to be running this here.', 'drip-press' ) );
	}

	// Do the constants check.
	$constants  = check_constants_for_ajax();

	// Bail out if we hit a constant.
	if ( ! $constants ) {
		return;
	}

	// Check for the specific action.
	if ( empty( $_POST['action'] ) || 'save_drip_bulkedit' !== sanitize_key( $_POST['action'] ) ) {
		return;
	}

	// Check for the trigger.
	if ( empty( $_POST['trigger'] ) || 'bulk' !== sanitize_key( $_POST['trigger'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], Core\NONCE_PREFIX . 'bulk_action' ) ) {
		wp_die( __( 'Nonce failed. Why?', 'drip-press' ) );
	}

	// Make sure we have post IDs before going any farther.
	if ( empty( $_POST['post_ids'] ) ) {
		return;
	}

	// Sanitize the post IDs before going further.
	$post_ids   = array_map( 'absint', $_POST['post_ids'] );

	// If we triggered the clear flag, handle that.
	if ( 'true' === sanitize_text_field( $_POST['clear'] ) ) {

		// Loop each post ID and purge.
		foreach ( $post_ids as $post_id ) {
			Process\purge_single_post_meta( $post_id );
		}

		// And just die, because that is what the Codex said to do.
		die();
	}

	// Now we have values and such.
	if ( empty( $_POST['count'] ) || empty( $_POST['range'] ) ) {
		return;
	}

	// Pull and clean up the values.
	$drip_count = absint( $_POST['count'] );
	$drip_range = sanitize_text_field( $_POST['range'] );

	// Loop each post ID.
	foreach ( $post_ids as $post_id ) {
		Process\set_single_drip_meta( $post_id, $drip_count, $drip_range );
	}

	// And just die, because that is what the Codex said to do.
	die();
}

/**
 * Set the user status when they click.
 *
 * @return void
 */
function set_user_status() {

	// Only run this on a logged in user.
	if ( ! is_user_logged_in() ) {
		wp_die( __( 'You are not supposed to be running this.', 'drip-press' ) );
	}

	// Do the constants check.
	$constants  = check_constants_for_ajax();

	// Bail out if we hit a constant.
	if ( ! $constants ) {
		return;
	}

	// Check for the specific action.
	if ( empty( $_POST['action'] ) || 'dppress_set_user_status' !== sanitize_key( $_POST['action'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], Core\NONCE_PREFIX . 'status_action' ) ) {
		wp_die( __( 'Nonce failed. Why?', 'drip-press' ) );
	}

	// Check for the actual values.
	if ( empty( $_POST['post_id'] ) || empty( $_POST['user_id'] ) ) {

		// Build our return.
		$return_setup   = array(
			'errcode' => 'missing-required-values',
			'message' => __( 'Some required values are missing. Please try later.', 'drip-press' ),
		);

		// And handle my JSON return.
		wp_send_json_error( $return_setup );
	}

	// Now store the new value.
	Process\set_single_user_drip_progress( absint( $_POST['post_id'] ), absint( $_POST['user_id'] ) );

	// Build our return.
	$return_setup   = array(
		'errcode' => null,
		'message' => __( 'This was marked as completed.', 'drip-press' ),
		'markup'  => '<p class="dppress-message dppress-message-completed">' . Helpers\get_completed_message( current_time( 'timestamp' ) ) . '</p>'
	);

	// And handle my JSON return.
	wp_send_json_success( $return_setup );
}

/**
 * Check the constants we know about during an Ajax call.
 *
 * @return boolean
 */
function check_constants_for_ajax() {

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

	// Passed them all. Return true.
	return true;
}

/**
 * Build and process our Ajax error handler.
 *
 * @param  string $error_code  The error code in question.
 * @param  string $error_text  Optional error message text.
 *
 * @return void
 */
function send_ajax_error( $error_code = '', $error_text = '' ) {

	// Build our return.
	$return_setup   = array(
		'errcode' => esc_attr( $error_code ),
		'message' => esc_attr( $error_text ),
	);

	// And handle my JSON return.
	wp_send_json_error( $return_setup );
}
