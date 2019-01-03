<?php
/**
 * Our shortcodes.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Shortcodes;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;
use DripPress\Formatting as Formatting;
use DripPress\Process as Process;

/**
 * Start our engines.
 */
add_shortcode( 'drip-complete', __NAMESPACE__ . '\shortcode_completed' );
add_shortcode( 'user-progress', __NAMESPACE__ . '\shortcode_user_progress' );

/**
 * Handle the basic 'completed' button.
 *
 * @param  array $atts  Attributes.
 *
 * @return mixed
 */
function shortcode_completed( $atts, $content = null ) {

	// Don't run on non-logged in users. Maybe?
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Parse my attributes.
	$args   = shortcode_atts( array(
		'label' => __( 'Mark Completed', 'drip-press' ),
	), $atts );

	// Check the user status.
	$maybe_completed    = Helpers\get_user_content_status( get_the_ID() );

	// If the status is not false, show the completed message.
	if ( false !== $maybe_completed ) {
		return Helpers\get_completed_message( $maybe_completed, true );
	}

	// Return the button instead.
	return Formatting\get_shortcode_completed_button( get_the_ID(), $args['label'], false );
}

/**
 * Handle the display of the user drip list.
 *
 * @param  array $atts  Attributes.
 *
 * @return mixed
 */
function shortcode_user_progress( $atts, $content = null ) {

	// Don't run on non-logged in users. Maybe?
	if ( ! is_user_logged_in() ) {
		return;
	}

	// Parse my attributes.
	$args   = shortcode_atts( array(
		'title' => '',
	), $atts );

	// Get all the available dripped content.
	$drip_content   = Process\get_all_dripped_content();

	// Bail without having any content to display.
	if ( ! $drip_content ) {
		return;
	}

	// Send back our formatted list.
	return Formatting\get_drip_content_list_with_progress( $drip_content, $args['title'], false );
}
