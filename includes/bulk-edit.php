<?php
/**
 * The functionality tied to the bulk editor.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\BulkEdit;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_bulkedit_assets' );
add_action( 'bulk_edit_custom_box', __NAMESPACE__ . '\display_drip_fields_bulkedit', 5, 2 );
add_action( 'wp_ajax_save_drip_bulkedit', __NAMESPACE__ . '\save_drip_bulkedit' );

/**
 * Load our admin side JS and CSS.
 *
 * @param $hook  Admin page hook we are current on.
 *
 * @return void
 */
function load_bulkedit_assets( $hook ) {

	// Get our screen item.
	$screen = Utilities\check_admin_screen();

	// Make sure we're only on the post table.
	if ( empty( $screen['post_type'] ) || 'edit' !== sanitize_text_field( $screen['base'] ) ) {
		return;
	}

	// Run our post type confirmation.
	$confirm_type   = Utilities\confirm_supported_type( $screen['post_type'] );

	// Bail if we don't have what we need.
	if ( ! $confirm_type ) {
		return;
	}

	// Set my handle.
	$handle = 'drippress-bulk-quick';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our CSS file.
	wp_enqueue_style( $handle, Core\ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );

	// And our JS.
	wp_enqueue_script( $handle, Core\ASSETS_URL . '/js/' . $file . '.js', array( 'jquery' ), $vers, true );
}

/**
 * Display our custom fields for a drip.
 *
 * @param  string $column     The name of the column we're on.
 * @param  string $post_type  The post type.
 *
 * @return HTML
 */
function display_drip_fields_bulkedit( $column, $post_type ) {

	// Bail if we aren't on the column we want.
	if ( empty( $column ) || 'drippress-bulk' !== $column ) {
		return;
	}

	// Set my empty.
	$field  = '';

	// Open up the fieldset.
	$field .= '<fieldset id="inline-edit-col-drip-bulk-fields" class="inline-edit-col-right">';

		// Our spacer to make it line up.
		$field .= '<div class="inline-edit-spacer">&nbsp;</div>';

		// Wrap it in a div.
		$field .= '<div class="inline-edit-col column-' . esc_attr( $column ) . '">';

			// Add the clearfix group.
			$field .= '<div class="inline-edit-group wp-clearfix">';

				// Wrap the whole thing in a label.
				$field .= '<label class="inline-edit-drippress alignleft">';

					// Load the label portion.
					$field .= '<span class="inline-drippress-bulk-row inline-drippress-bulk-label">' . esc_html__( 'Drip Schedule', 'drip-press' ) . '</span>';

					// Load the fields portion.
					$field .= '<span class="inline-drippress-bulk-row inline-drippress-bulk-fields">';

						// Output the two fields themselves.
						$field .= '<input type="number" min="1" step="1" class="small-text" name="dppress-bulk-count" id="dppress-bulk-count" value="">';
						$field .= Formatting\get_admin_range_dropdown( '', 0, 'dppress-bulk-range', false );

						// Include a hidden field to check on POST.
						$field .= '<input type="hidden" name="dppress-bulk-trigger" id="dppress-bulk-trigger" value="bulk">';

						// Use nonce for verification.
						$field .= wp_nonce_field( Core\NONCE_PREFIX . 'bulk_action', Core\NONCE_PREFIX . 'bulk_name', false, false );

					$field .= '</span>';

				// Close the label.
				$field .= '</label>';

			// Close up the div.
			$field .= '</div>';

		// Close up the div.
		$field .= '</div>';

	// Close the fieldset.
	$field .= '</fieldset>';

	// And echo it out.
	echo $field; // WPCS: XSS ok.
}

/**
 * Handle saving the bulk edit drips.
 *
 * @return void
 */
function save_drip_bulkedit() {

	// Only run this on the admin side.
	if ( ! is_admin() ) {
		die();
	}

	// Bail out if running an autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// Bail if we are doing a REST API request.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	update_option( 'asdasda', $_POST );

	// Check for the specific action.
	if ( empty( $_POST['action'] ) || 'save_drip_bulkedit' !== sanitize_key( $_POST['action'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], Core\NONCE_PREFIX . 'bulk_action' ) ) {
		wp_die( __( 'Nonce failed. Why?', 'drip-press' ) );
	}

	// Make sure we have all the required pieces.
	if ( empty( $_POST['post_ids'] ) || empty( $_POST['count'] ) || empty( $_POST['range'] ) ) {
		return;
	}

	// Sanitize all the $POSTed values.
	$post_ids   = array_map( 'absint', $_POST['post_ids'] );
	$drip_count = absint( $_POST['count'] );
	$drip_range = sanitize_text_field( $_POST['range'] );

	// Loop each post ID.
	foreach ( $post_ids as $post_id ) {

		// Since this is the bulk edit, we aren't doing the same checks.
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

	// And just die, because that is what the Codex said to do.
	die();
}
