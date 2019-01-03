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
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_admin_assets', 11 );
add_action( 'pre_get_posts', __NAMESPACE__ . '\drip_sorting_request', 1 );
add_filter( 'views_edit-post', __NAMESPACE__ . '\post_table_views' );
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\post_columns_data', 10, 2 );
add_filter( 'manage_edit-post_columns', __NAMESPACE__ . '\post_columns_display' );
add_filter( 'manage_edit-post_sortable_columns', __NAMESPACE__ . '\post_columns_sortable' );

/**
 * Load our CSS and JS when needed.
 *
 * @param  string $hook  The page hook we're on.
 *
 * @return void
 */
function load_admin_assets( $hook ) {

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
 * Handle the sorting if they requested it via drip.
 *
 * @param  object $query  The query being looked at.
 *
 * @return void
 */
function drip_sorting_request( $query ) {

	// Bail if not the admin.
	if ( ! is_admin() ) {
		return;
	}

	// Check for the post filter.
	if ( ! empty( $_GET['drip_live'] ) ) {

		// Build the meta query.
		$meta_query = array(
			'key'   => Core\META_PREFIX . 'live',
			'value' => 1,
		);

		// Now set them as args.
		$query->set( 'meta_query', array( $meta_query ) );
	}

	// Modify the query if we requested orderby.
	if ( ! empty( $query->get( 'orderby' ) ) && 'drip_length' === sanitize_text_field( $query->get( 'orderby' ) ) ) {
		$query->set( 'meta_key', Core\META_PREFIX . 'drip' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}

/**
 * Include our custom views for dripped content.
 *
 * @param  array $views  The existing array of views.
 *
 * @return array
 */
function post_table_views( $views ) {

	// Then create our link.
	$dripped_link   = add_query_arg( array( 'post_type' => 'post', 'drip_live' => 1 ) );

	// Go get our dripped count.
	$dripped_count  = Process\get_dripped_content_count();

	// Set the class.
	$dripped_class  = ! empty( $_GET['drip_live'] ) ? 'current' : '';

	// Now include this in my views.
	$views['drip']  = '<a class="' . esc_attr( $dripped_class ) . '" href="' . esc_url_raw( $dripped_link ) . '">' . __( 'Dripped', 'drip-press' ) . ' <span class="count">(' . absint( $dripped_count ) . ')</span></a>';

	// And return them.
	return $views;
}

/**
 * Set up the data for our custom columns.
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

			// Set the length text.
			$drip_length    = Formatting\display_drip_length( $post_id, '<em>' . __( 'not set', 'drip-press' ) . '</em>' );

			// And echo it out.
			echo '<p class="drip-length">' . wp_kses_post( $drip_length ) . '</p>';

 			// And break.
 			break;

		// End all case breaks.
	}
}

/**
 * Add our custom column to the display.
 *
 * @param  array $columns  The current array of columns.
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

/**
 * Set our drip length to be sortable.
 *
 * @param  array $columns  The current array of columns.
 *
 * @return array
 */
function post_columns_sortable( $columns ) {

	// Add our column if it doesn't already appear.
	if ( ! isset( $columns['drip-length'] ) ) {
		$columns['drip-length'] = 'drip_length';
	}

	// Return the array of columns.
	return $columns;
}
