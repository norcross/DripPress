<?php
/**
 * The functionality tied to the quick and bulk editor.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\QuickBulkEdit;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_quick_bulk_assets' );
add_filter( 'manage_post_posts_columns', __NAMESPACE__ . '\add_dummy_columns', 10, 2 );
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\dummy_column_data', 10, 2 );
add_filter( 'hidden_columns', __NAMESPACE__ . '\set_hidden_column', 10, 3 );
add_action( 'quick_edit_custom_box', __NAMESPACE__ . '\display_drip_fields_quickedit', 5, 2 );
add_action( 'bulk_edit_custom_box', __NAMESPACE__ . '\display_drip_fields_bulkedit', 5, 2 );
add_action( 'save_post', __NAMESPACE__ . '\save_drip_quickedit', 10, 2 );
add_action( 'wp_ajax_save_drip_bulkedit', __NAMESPACE__ . '\save_drip_bulkedit' );

/**
 * Load our admin side JS and CSS.
 *
 * @param $hook  Admin page hook we are current on.
 *
 * @return void
 */
function load_quick_bulk_assets( $hook ) {

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
 * Add our dummy columns (which will get hidden).
 *
 * @param  array $columns  All the columns.
 *
 * @return array $columns  The modified array of columns.
 */
function add_dummy_columns( $columns ) {

	// Add the dummy placeholder column.
	$columns['drippress-quick'] = __return_empty_string();
	$columns['drippress-bulk'] = __return_empty_string();

	// Return the resulting columns.
	return $columns;
}

/**
 * Set the drip values.
 *
 * @param  string  $column   The name of the column.
 * @param  integer $post_id  The post ID of the row.
 *
 * @return integer
 */
function dummy_column_data( $column, $post_id ) {

	// Get the metadata.
	$drip_meta  = Helpers\get_content_drip_meta( $post_id );

	// Start my column switch.
	switch ( $column ) {

		case 'drippress-quick':

			// Output the fields.
			echo '<input type="hidden" id="drippress-quick-count-val" class="drippress-quick-count-val" value="' . absint( $drip_meta['count'] ) . '">';
			echo '<input type="hidden" id="drippress-quick-range-val" class="drippress-quick-range-val" value="' . esc_attr( $drip_meta['range'] ) . '">';

			// And be done.
			break;

		// End all case breaks.
	}
}

/**
 * Add our dummy columns to the hidden items.
 *
 * @param  array   $hidden        An array of hidden columns.
 * @param  object  $screen        WP_Screen object of the current screen.
 * @param  boolean $use_defaults  Whether to show the default columns.
 *
 * @return array   $hidden        The modified array of columns.
 */
function set_hidden_column( $hidden, $screen, $use_defaults ) {

	// Check the screen ID before we set.
	if ( empty( $screen->id ) || 'edit-post' !== $screen->id ) {
		return $hidden;
	}

	// Return our updated array.
	return wp_parse_args( array( 'drippress-quick', 'drippress-bulk' ), $hidden );
}

/**
 * Display our custom fields for a drip.
 *
 * @param  string $column     The name of the column we're on.
 * @param  string $post_type  The post type.
 *
 * @return HTML
 */
function display_drip_fields_quickedit( $column, $post_type ) {

	// Bail if we aren't on the column we want.
	if ( empty( $column ) || 'drippress-quick' !== $column ) {
		return;
	}

	// Set my empty.
	$field  = '';

	// Open up the fieldset.
	$field .= '<fieldset id="inline-edit-col-drip-fields" class="inline-edit-col-right">';

		// Our spacer to make it line up.
		$field .= '<div class="inline-edit-spacer">&nbsp;</div>';

		// Wrap it in a div.
		$field .= '<div class="inline-edit-col column-' . esc_attr( $column ) . '">';

			// Add the clearfix group.
			$field .= '<div class="inline-edit-group wp-clearfix">';

				// Wrap the whole thing in a label.
				$field .= '<label class="inline-edit-drippress alignleft">';

					// Load the label portion.
					$field .= '<span class="inline-drippress-quick-row inline-drippress-quick-label">' . esc_html__( 'Drip Schedule', 'drip-press' ) . '</span>';

					// Load the fields portion.
					$field .= '<span class="inline-drippress-quick-row inline-drippress-quick-fields">';

						// Output the two fields themselves.
						$field .= '<input type="number" min="1" step="1" class="small-text" name="dppress-quick-count" id="dppress-quick-count" value="">';
						$field .= Formatting\get_admin_range_dropdown( '', 0, 'dppress-quick-range', false );

						// Include a hidden field to check on POST.
						$field .= '<input type="hidden" name="dppress-trigger" id="dppress-trigger" value="quick">';

						// Use nonce for verification.
						$field .= wp_nonce_field( Core\NONCE_PREFIX . 'quick_action', Core\NONCE_PREFIX . 'quick_name', false, false );

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
 * Save metadata for the drip scheduling.
 *
 * @param  integer $post_id  The individual post ID.
 * @param  object  $post     The entire post object.
 *
 * @return void
 */
function save_drip_quickedit( $post_id, $post ) {

	// Make sure we aren't using autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return;
	}

	// Check for the trigger field.
	if ( empty( $_POST[ 'dppress-trigger'] ) || 'quick' !== sanitize_text_field( $_POST[ 'dppress-trigger'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST[ Core\NONCE_PREFIX . 'quick_name'] ) || ! wp_verify_nonce( $_POST[ Core\NONCE_PREFIX . 'quick_name'], Core\NONCE_PREFIX . 'quick_action' ) ) {
		wp_die( __( 'Nonce failed. Why?', 'drip-press' ) );
	}

	// Make sure we have the cap.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( __( 'You do not have the capability to perform this action.', 'drip-press' ) );
    }

	// Run our post type confirmation.
	$confirm_type   = Utilities\confirm_supported_type( $post->post_type );

	// Bail if we don't have what we need.
	if ( ! $confirm_type ) {
		return;
	}

	// Now check for the individual pieces.
	$drip_count = ! empty( $_POST['dppress-quick-count'] ) ? absint( $_POST['dppress-quick-count'] ) : 0;
	$drip_range = ! empty( $_POST['dppress-quick-range'] ) ? sanitize_text_field( $_POST['dppress-quick-range'] ) : '';

	// Bail and purge if we didn't include any values.
	if ( empty( $drip_count ) || empty( $drip_range ) ) {

		// Set off our purge.
		Process\purge_single_post_meta( $post_id );

		// And be done.
		return;
	}

	// And update the meta.
	update_quick_bulk_drip_meta( $post_id, $drip_count, $drip_range );

	// Perhaps there is more here, but not right now.
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

		// And update the meta.
		update_quick_bulk_drip_meta( $post_id, $drip_count, $drip_range );
	}

	// And just die, because that is what the Codex said to do.
	die();
}

/**
 * Save the single meta for a quick or bulk edit.
 *
 * @param  integer $post_id     The post ID being saved.
 * @param  integer $drip_count  What our count is.
 * @param  string  $drip_range  The range we have.
 *
 * @return void
 */
function update_quick_bulk_drip_meta( $post_id = 0, $drip_count = 0, $drip_range = '' ) {

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
