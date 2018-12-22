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
				echo '<input type="text" class="widefat" name="dppress-meta-count" id="dppress-meta-count" value="' . absint( $stored_values['count'] ) . '">';
			echo '</span>';

		// Close the count field.
		echo '</li>';

		// Show the range dropdown.
		echo '<li class="dppress-secondary-field dppress-range-field">';

			// Output the title portion.
			echo '<span class="field-title">' . __( 'Range', 'drip-press' ) . '</span>';

			// Output the dropdown.
			echo '<span class="field-input">';
				echo Formatting\get_admin_range_dropdown( $stored_values['range'], $stored_values['count'], false );
			echo '<span>';

		// Close the count field.
		echo '</li>';

	// Close the secondary list.
	echo '</ul>';

	// Use nonce for verification.
	echo wp_nonce_field( Core\NONCE_PREFIX . 'schd_action', Core\NONCE_PREFIX . 'schd_name', false, false );

	// And our hidden value for sorting.
	echo '<input type="hidden" name="dppress-sort" id="dppress-sort" value="' . absint( $stored_values['sort'] ) . '">';
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

	// Do our nonce check. ALWAYS A NONCE CHECK.
	if ( empty( $_POST[ Core\NONCE_PREFIX . 'schd_name'] ) || ! wp_verify_nonce( $_POST[ Core\NONCE_PREFIX . 'schd_name'], Core\NONCE_PREFIX . 'schd_action' ) ) {
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

	// Set the sorting time.
	$set_timestamp  = ! empty( $_POST['dppress-sort'] ) ? $_POST['dppress-sort'] : current_time( 'timestamp', 1 );

	// Bail and purge if we didn't check the box.
	if ( empty( $_POST['dppress-live'] ) ) {

		// Set the drip sort time for sorting purposes
		update_post_meta( $post_id, Core\META_PREFIX . 'sort', $set_timestamp );

		// Purge the rest.
		Process\purge_single_post_meta( $post_id );

		// And be done.
		return;
	}

	// Now check for the individual pieces.
	$drip_count = ! empty( $_POST['dppress-meta-count'] ) ? absint( $_POST['dppress-meta-count'] ) : 0;
	$drip_range = ! empty( $_POST['dppress-meta-range'] ) ? sanitize_text_field( $_POST['dppress-meta-range'] ) : '';

	// Bail and purge if we didn't include any values.
	if ( empty( $drip_count ) || empty( $drip_range ) ) {

		// Set the drip sort time for sorting purposes
		update_post_meta( $post_id, Core\META_PREFIX . 'sort', $set_timestamp );

		// Purge the rest.
		Process\purge_single_post_meta( $post_id );

		// And be done.
		return;
	}

	// Now set some metadata.
	update_post_meta( $post_id, Core\META_PREFIX . 'live', 1 );
	update_post_meta( $post_id, Core\META_PREFIX . 'count', $drip_count );
	update_post_meta( $post_id, Core\META_PREFIX . 'range', $drip_range );

	// Attempt to calculate the drip value.
	$calculate_drip = Utilities\calculate_content_drip( $drip_count, $drip_range );

	// If we don't have a drip, set our timestamp and delete any drip value.
	if ( empty( $calculate_drip ) ) {

		// Set our timestamp.
		update_post_meta( $post_id, Core\META_PREFIX . 'sort', $set_timestamp );

		// Delete the drip value.
		delete_post_meta( $post_id, Core\META_PREFIX . 'drip' );
	}

	// If we have a calculated value, store that.
	if ( ! empty( $calculate_drip ) ) {

		// Set my updated sort time.
		$update_sort_stamp  = absint( $set_timestamp ) + absint( $calculate_drip );

		// Update my two meta keys.
		update_post_meta( $post_id, Core\META_PREFIX . 'sort', $update_sort_stamp );
		update_post_meta( $post_id, Core\META_PREFIX . 'drip', $calculate_drip );
	}

}

