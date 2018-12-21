<?php
/**
 * Our admin-focused functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Admin;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\scripts_styles', 11 );
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\post_columns_data', 10, 2 );
add_filter( 'manage_edit-post_columns', __NAMESPACE__ . '\post_columns_display' );

/**
 * Load our CSS and JS when needed.
 *
 * @param  string $hook  The page hook we're on.
 *
 * @return void
 */
function scripts_styles( $hook ) {

	// Run our post type confirmation.
	$confirm_type   = Utilities\confirm_supported_type( Utilities\check_admin_screen( 'type' ) );

	// Bail if we don't have what we need.
	if ( ! $confirm_type ) {
		return;
	}

	// Set my handle.
	$file_handle    = 'drippress-admin';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file_build     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $file_handle : $file_handle . '.min';

	// Set a version for whether or not we're debugging.
	$file_version   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : Core\VERS;

	// Load our CSS file.
	wp_enqueue_style( $file_handle, Core\ASSETS_URL . '/css/' . $file_build . '.css', false, $file_version, 'all' );

	// And our JS.
	wp_enqueue_script( $file_handle, Core\ASSETS_URL . '/js/' . $file_build . '.js', array( 'jquery' ), $file_version, true );
}

/**
 * Set up the data for our custom columnms.
 *
 * @param  string  $column   The name of the column we're checking.
 * @param  integer $post_id  The post ID from the row.
 *
 * @return mixed
 */
function post_columns_data( $column, $post_id ) {

	// Handle the column switch.
	switch ( $column ) {

		// Handle our drip length.
		case 'drip-length':

			// Set the label.
			$label  = Formatting\display_drip_length( $post_id, __( 'not set', 'drip-press' ) );

			// And echo it out.
			echo '<p class="drip-length">' . esc_html( $label ) . '</p>';

 			// And break.
 			break;

		// End all case breaks.
	}
}

/**
 * Add our custom column to the display.
 *
 * @return array
 */
function post_columns_display( $columns ) {

	// Add our column if it doesn't already appear.
	if ( ! isset( $columns['drip-length'] ) ) {
		$columns['drip-length'] = __( 'Drip Length', 'drip-press' );
	}

	// Return the array of columns.
	return $columns;
}
