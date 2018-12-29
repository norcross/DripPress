<?php
/**
 * Our basic formatting functions.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress\Formatting;

// Set our alias items.
use DripPress as Core;
use DripPress\Helpers as Helpers;
use DripPress\Utilities as Utilities;

/**
 * Get a written display of the drip length.
 *
 * @param  integer $post_id  The post ID we're checking for.
 * @param  string  $default  The default text we can show.
 * @param  boolean $echo     Whether to echo or just return.
 *
 * @return string
 */
function display_drip_length( $post_id = 0, $default = '', $echo = true ) {

	// Bail without any post ID.
	if ( empty( $post_id ) ) {
		return $default;
	}

	// Get the stored values that we (hopefull) have.
	$stored_values  = Helpers\get_content_drip_meta( $post_id );

	// Bail if not set to live or no data.
	if ( empty( $stored_values['live'] ) || empty( $stored_values['count'] ) || empty( $stored_values['range'] ) ) {
		return $default;
	}

	// Figure out if it's single or plural.
	$maybe_plural  = ! empty( $stored_values['count'] ) && absint( $stored_values['count'] ) > 1 ? 'plural': 'single';

	// Get my counting label.
	$count_label   = Helpers\get_single_drip_label( $stored_values['range'], $maybe_plural );

	// Combine the data items return.
	$count_display = absint( $stored_values['count'] ) . ' ' . esc_html( $count_label );

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $count_display;
	}

	// Echo my build.
	echo $count_display;
}

/**
 * Construct and display the dropdown for selecting a range.
 *
 * @param  string  $selected  An optional item to indicate selected.
 * @param  integer $count     How many things we have. Used for plurals.
 * @param  string  $name      The field name and ID to use.
 * @param  boolean $echo      Whether to echo it out or just return it.
 *
 * @return HTML
 */
function get_admin_range_dropdown( $selected = '', $count = 0, $name = '', $echo = true ) {

	// Get our available ranges.
	$ranges_array   = Helpers\get_drip_ranges();

	// Bail without any ranges.
	if ( empty( $ranges_array ) ) {
		return;
	}

	// Set my class based on the name.
	$class  = 'dppress-quick-range' === sanitize_text_field( $name ) ? 'quick-inline' : 'widefat';

	// Set my empty.
	$build  = '';

	// Wrap the select tag.
	$build .= '<select class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';

		// Set our "blank" select option.
		$build .= ' <option value="0">' . __( '(Select)', 'drip-press' ) . '</option>';

		// Now loop out the array of ranges and do the things.
		foreach ( $ranges_array as $range_key => $range_values ) {

			// Determine our label.
			$label  = empty( $count ) ? $range_values['blank'] : ( absint( $count ) === 1 ? $range_values['single'] : $range_values['plural'] );

			// And make the thing.
			$build .= '<option value="' . esc_attr( $range_key ) . '" ' . selected( $selected, $range_key, false ) . '>' . esc_html( $label ) . '</option>';
		}

	// Close out the select tag.
	$build .= '</select>';

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $build;
	}

	// Echo my build.
	echo $build;
}
