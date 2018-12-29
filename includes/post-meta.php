<?php
/**
 * Our post meta specific functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\PostMeta;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_action( 'add_meta_boxes', __NAMESPACE__ . '\load_metaboxes' );
add_action( 'save_post', __NAMESPACE__ . '\save_drip_meta', 10, 2 );

/**
 * Load our metaboxes for supported types.
 *
 * @return void
 */
function load_metaboxes() {

	// Get our supported post types.
	$supported_types    = Helpers\get_supported_types();

	// Bail if we have no types to support.
	if ( empty( $supported_types ) ) {
		return;
	}

	// Now add our side metabox.
	foreach ( $supported_types as $post_type ) {
		add_meta_box( 'dppress-schd-box', __( 'Drip Schedule', 'drip-press' ), __NAMESPACE__ . '\display_drip_schedule_box', $post_type, 'side', 'core' );
	}
}

/**
 * Set up the side metabox.
 *
 * @param  object $post  The post object we are currently working with.
 *
 * @return HTML
 */
function display_drip_schedule_box( $post ) {

	// Get the stored meta.
	$stored_values  = Helpers\get_content_drip_meta( $post->ID );

	// Initial checkbox to load the rest.
	echo '<p class="dppress-on-field">';
		echo '<input id="dppress-live" type="checkbox" name="dppress-live" value="1" ' . checked( $stored_values['live'], 1, false ) . ' />';
		echo '<label for="dppress-live">' . __( 'Drip this content', 'drip-press' ) . '</label>';
	echo '</p>';

	// Unordered list of secondary choices.
	echo '<ul class="dppress-data">';

		// Show the count field.
		echo '<li class="dppress-secondary-field dppress-count-field">';

			// Output the title portion.
			echo '<span class="field-title">' . __( 'Count', 'drip-press' ) . '</span>';

			// Output the input portion.
			echo '<span class="field-input">';
				echo '<input type="number" min="1" step="1" class="widefat" name="dppress-meta-count" id="dppress-meta-count" value="' . absint( $stored_values['count'] ) . '">';
			echo '</span>';

		// Close the count field.
		echo '</li>';

		// Show the range dropdown.
		echo '<li class="dppress-secondary-field dppress-range-field">';

			// Output the title portion.
			echo '<span class="field-title">' . __( 'Range', 'drip-press' ) . '</span>';

			// Output the dropdown.
			echo '<span class="field-input">';
				echo Formatting\get_admin_range_dropdown( $stored_values['range'], $stored_values['count'], 'dppress-meta-range', false );
			echo '<span>';

		// Close the count field.
		echo '</li>';

	// Close the secondary list.
	echo '</ul>';

	// Set the hidden fields with the same values so we can check for changes.
	echo '<input type="hidden" name="dppress-meta-count-alt" id="dppress-meta-count-alt" value="' . absint( $stored_values['count'] ) . '">';
	echo '<input type="hidden" name="dppress-meta-range-alt" id="dppress-meta-range-alt" value="' . esc_attr( $stored_values['range'] ) . '">';

	// Include a hidden field to check on POST.
	echo '<input type="hidden" name="dppress-trigger" id="dppress-trigger" value="go">';

	// And our hidden value for the drip value.
	echo '<input type="hidden" name="dppress-drip" id="dppress-drip" value="' . absint( $stored_values['drip'] ) . '">';

	// Use nonce for verification.
	echo wp_nonce_field( Core\NONCE_PREFIX . 'meta_action', Core\NONCE_PREFIX . 'meta_name', false, false );
}

/**
 * Save metadata for the drip scheduling.
 *
 * @param  integer $post_id  The individual post ID.
 * @param  object  $post     The entire post object.
 *
 * @return void
 */
function save_drip_meta( $post_id, $post ) {

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

	// Check for the trigger field.
	if ( empty( $_POST[ 'dppress-trigger'] ) || 'go' !== sanitize_text_field( $_POST[ 'dppress-trigger'] ) ) {
		return;
	}

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST[ Core\NONCE_PREFIX . 'meta_name'] ) || ! wp_verify_nonce( $_POST[ Core\NONCE_PREFIX . 'meta_name'], Core\NONCE_PREFIX . 'meta_action' ) ) {
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

	// Bail and purge if we didn't check the box.
	if ( empty( $_POST['dppress-live'] ) ) {

		// Set off our purge.
		Process\purge_single_post_meta( $post_id );

		// And be done.
		return;
	}

	// Now check for the individual pieces.
	$drip_count = ! empty( $_POST['dppress-meta-count'] ) ? absint( $_POST['dppress-meta-count'] ) : 0;
	$drip_range = ! empty( $_POST['dppress-meta-range'] ) ? sanitize_text_field( $_POST['dppress-meta-range'] ) : '';

	// Bail and purge if we didn't include any values.
	if ( empty( $drip_count ) || empty( $drip_range ) ) {

		// Set off our purge.
		Process\purge_single_post_meta( $post_id );

		// And be done.
		return;
	}

	// Set our live flag.
	update_post_meta( $post_id, Core\META_PREFIX . 'live', 1 );

	// Set a flag to indicate a changed value.
	$reset_drip = false;

	// Now check for the alt values.
	$alt_count  = ! empty( $_POST['dppress-meta-count-alt'] ) ? absint( $_POST['dppress-meta-count-alt'] ) : 0;
	$alt_range  = ! empty( $_POST['dppress-meta-range-alt'] ) ? sanitize_text_field( $_POST['dppress-meta-range-alt'] ) : '';

	// Check the hidden count value to see if this is a new setting.
	if ( empty( $alt_count ) || ! empty( $alt_count ) && absint( $alt_count ) !== absint( $drip_count ) ) {

		// Change my drip flag.
		$reset_drip = true;

		// And set the meta key.
		update_post_meta( $post_id, Core\META_PREFIX . 'count', $drip_count );
	}

	// Check the hidden range value to see if this is a new setting.
	if ( empty( $alt_range ) || ! empty( $alt_range ) && esc_attr( $alt_range ) !== esc_attr( $drip_range ) ) {

		// Change my drip flag.
		$reset_drip = true;

		// And set the meta key.
		update_post_meta( $post_id, Core\META_PREFIX . 'range', $drip_range );
	}

	// Now handle dealing with the drip.
	if ( empty( $_POST['dppress-drip'] ) || false !== $reset_drip ) {

		// Now get the range value for the drip.
		$drip_seconds   = Helpers\get_values_from_range( $drip_range, 'seconds' );

		// Set my drip length.
		$drip_length    = absint( $drip_count ) === 1 ? absint( $drip_seconds ) : absint( $drip_seconds ) * absint( $drip_count );

		// And update the meta.
		update_post_meta( $post_id, Core\META_PREFIX . 'drip', $drip_length );
	}

	// Perhaps there is more here, but not right now.
}

