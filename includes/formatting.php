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
 * Construct and display the HTML list of drip items.
 *
 * @param  array   $list_items  The array of list items.
 * @param  string  $list_type   Which sort of list display we have.
 * @param  boolean $echo        Whether to echo or just return.
 *
 * @return HTML
 */
function display_drip_html_list( $list_items = array(), $list_type = 'widget', $echo = true ) {

	// Bail without any items to list.
	if ( empty( $list_items ) ) {
		return;
	}

	// Set my empty.
	$build  = '';

	// Wrap our list in some markup.
	$build .= '<ul class="drippress-list drippress-list-' . esc_html( $list_type ) . '">';

	// Now loop my list items.
	foreach ( $list_items as $item_id ) {

		// Check our drip status.
		$check  = Utilities\compare_drip_dates( $item_id );

		// Set our class based on the display.
		$class  = ! empty( $check['display'] ) ? 'drippress-item-show' : 'drippress-item-delay';

		// Wrap the single list item.
		$build .= '<li class="drippress-item ' . esc_attr( $class ) . '">';

		// If we have passed the drip check, show the name and link as normal. Otherwise message.
		if ( ! empty( $check['display'] ) ) {

			// Fetch the two pieces I want.
			$title  = get_the_title( $item_id );
			$link   = get_permalink( $item_id );

			// And make the link.
			$build .= '<a href="' . esc_url( $link ) . '">' . esc_attr( $title ) . '</a>';
		}

		// If we have a false display, show the message without a link.
		if ( empty( $check['display'] ) && ! empty( $check['message'] ) ) {
			$build .= esc_attr( $check['message'] );
		}

		// Close single list item.
		$build .= '</li>';
	}

	// Close the ordered list.
	$build .= '</ul>';

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $build;
	}

	// Echo my build.
	echo $build;
}

/**
 * Construct and display the dropdown for selecting terms.
 *
 * @param  string  $taxonomy    Which taxonomy we want.
 * @param  string  $field_name  The name field to apply to the dropdown.
 * @param  string  $field_id    The ID field to apply to the dropdown.
 * @param  string  $selected    An optional item to indicate selected.
 * @param  boolean $echo        Whether to echo it out or just return it.
 *
 * @return HTML
 */
function get_admin_term_dropdown( $taxonomy = '', $field_name = '', $field_id = '', $selected = '', $echo = true ) {

	// Bail without being given a taxonomy.
	if ( empty( $taxonomy ) ) {
		return;
	}

	// Set up the dropdown args.
	$dropdown_args  = array(
		'show_option_none'  => __( '(Select)', 'drip-press' ),
		'option_none_value' => '0',
		'orderby'           => 'name',
		'order'             => 'ASC',
		'hide_empty'        => 0,
		'echo'              => 0,
		'selected'          => esc_attr( $selected ),
		'name'              => esc_attr( $field_name ),
		'id'                => esc_attr( $field_id ),
		'class'             => 'widefat',
		'taxonomy'          => esc_attr( $taxonomy ),
		'hide_if_empty'     => true,
		'value_field'       => 'slug',
	);

	// Attempt to fetch the dropdown markup.
	$dropdown_setup = wp_dropdown_categories( $dropdown_args );

	// Set my label.
	$dropdown_label = '<label for="'. esc_attr( $field_id ) . '">' . __( 'Drip Term: ', 'drip-press' ) . '</label>';

	// Set my markup based on whether we have a drop.
	$dropdown_html  = ! empty( $dropdown_setup ) ? $dropdown_label . $dropdown_setup : __( 'There are no terms available.', 'drip-press' );

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $dropdown_html;
	}

	// Echo my build.
	echo $dropdown_html;
}

/**
 * Construct and display the dropdown for selecting a range.
 *
 * @param  string  $selected  An optional item to indicate selected.
 * @param  integer $count     How many things we have. Used for plurals.
 * @param  boolean $echo      Whether to echo it out or just return it.
 *
 * @return HTML
 */
function get_admin_range_dropdown( $selected = '', $count = 0, $echo = true ) {

	// Get our available ranges.
	$ranges_array   = Helpers\get_drip_ranges();

	// Bail without any ranges.
	if ( empty( $ranges_array ) ) {
		return;
	}

	// Set my empty.
	$build  = '';

	// Wrap the select tag.
	$build .= '<select class="widefat" name="dppress-meta-range" id="dppress-meta-range">';

		// Set our "blank" select option.
		$build .= ' <option value="0">' . __( '(Select)', 'drip-press' ) . '</option>';

		// Now loop out the array of ranges and do the things.
		foreach ( $ranges_array as $range_key => $range_values ) {

			// Determine our label.
			$label  = ! empty( $count ) && absint( $count ) > 1 ? $range_values['plural'] : $range_values['single'];

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
