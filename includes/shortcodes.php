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

/**
 * Start our engines.
 */
add_shortcode( 'drippress', __NAMESPACE__ . '\shortcode_display' );

/**
 * Display weekly list.
 *
 * @param  array $atts  Attributes.
 *
 * @return mixed
 */
function shortcode_display( $atts, $content = null ) {

	// Parse my attributes.
	$args   = shortcode_atts( array(
		'term'  => '',
		'tax'   => 'post_tag',
		'count' => 5,
		'types' => 'post',
	), $atts );

	// Bail without a declared term or taxonomy.
	if ( empty( $args['term'] ) || empty( $args['tax'] ) ) {
		return;
	}

	// Make sure we didn't just use the word "tag",
	$taxonomy   = ! empty( $args['tax'] ) && 'tag' === sanitize_text_field( $args['tax'] ) ? 'post_tag' : sanitize_text_field( $args['tax'] );

	// Attempt to get all our list items.
	$list_items = Helpers\get_drip_list( $args['term'], $taxonomy, absint( $args['count'] ), $args['types'] );

	// Bail if nothing for the list exists.
	if ( empty( $list_items ) ) {
		return;
	}

	// Fetch the HTML and return it.
	return Formatting\display_drip_html_list( $list_items, 'shortcode', false );
}
