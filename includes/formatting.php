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
	$class  = 'dppress-meta-range' === sanitize_text_field( $name ) ? 'widefat' : 'quick-inline';

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

/**
 * Construct and display the button for marking completed.
 *
 * @param  integer $post_id  The post ID we are displaying for.
 * @param  string  $label    What the button label should be.
 * @param  boolean $echo     Whether to echo it out or just return it.
 *
 * @return HTML
 */
function get_shortcode_completed_button( $post_id = 0, $label = '', $echo = true ) {

	// Bail without any post ID.
	if ( empty( $post_id ) || ! is_user_logged_in() ) {
		return;
	}

	// Set my empty.
	$build  = '';

	// Open up the div.
	$build .= '<div class="dppress-completed-prompt-wrap">';

		// Wrap the form.
		$build .= '<form class="dppress-prompt-form" method="post" action="' . esc_url( get_permalink( $post_id ) ) . '">';

			// Include the button.
			$build .= '<p class="dppress-prompt-button-wrap">';
				$build .= '<button class="dppress-prompt-button" name="dppress-prompt-button" type="submit" value="complete">' . esc_html( $label ) . '</button>';
			$build .= '</p>';

			// Include the post ID and user ID.
			$build .= '<input name="dppress-prompt-post-id" type="hidden" value="' . absint( $post_id ) . '">';
			$build .= '<input name="dppress-prompt-user-id" type="hidden" value="' . absint( get_current_user_id() ) . '">';

			// Use nonce for verification.
			$build .= wp_nonce_field( Core\NONCE_PREFIX . 'status_action', Core\NONCE_PREFIX . 'status_name', false, false );

		// Close up the form.
		$build .= '</form>';

	// Close up the div.
	$build .= '</div>';

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $build;
	}

	// Echo my build.
	echo $build;
}

/**
 * Construct and display the list of drip content.
 *
 * @param  array   $content  The content we are displaying.
 * @param  string  $title    An optional title heading.
 * @param  boolean $echo     Whether to echo it out or just return it.
 *
 * @return HTML
 */
function get_drip_content_list_with_progress( $content = array(), $title = '', $echo = true ) {

	// Bail without content.
	if ( empty( $content ) ) {
		return;
	}

	// Set my empty for the build.
	$build  = '';

	// Wrap it in a div.
	$build .= '<div class="dppress-user-progress-wrap">';

		// Maybe a title?
		$build .= ! empty( $title ) ? '<h3>' . esc_html( $title ) . '</h3>' : '';

		// Wrap it in a list.
		$build .= '<ul>';

		// Now loop my content and start parsing it out.
		foreach ( $content as $content ) {

			// Check for the access on the content.
			$maybe  = Utilities\compare_drip_signup_dates( $content['ID'] );

			// If we have a false return (which means missing data) then skip.
			if ( ! $maybe ) {
				continue;
			}

			// Grab our status.
			$status = Helpers\get_user_content_status( $content['ID'] );

			// Set our class based on being accessible.
			$class  = 'dppress-content-list-item';
			$class .= ! empty( $maybe['display'] ) ? ' dppress-content-accessible' : ' dppress-content-restricted';
			$class .= false !== $status ? ' dppress-content-completed' : '';

			// Now the output.
			$build .= '<li class="' . esc_attr( $class ) . '">';

			// Check for the display flag.
			if ( ! empty( $maybe['display'] ) ) {

				// Output the main link.
				$build .= '<a href="' . esc_url( $content['permalink'] ) . '">' . esc_html( $content['post_title'] ) . '</a>';

				// Add the checkmark if it's done.
				if ( false !== $status ) {

					// Get our completed text for the hover title.
					$hover  = ! empty( $maybe['message'] ) ? wp_strip_all_tags( $maybe['message'], true ) : '';

					// And output the checkmark.
					$build .= ' <span title="' . esc_attr( $hover ) . '" class="dppress-content-icon dppress-content-checkmark-complete">&#x2713;</span>';
				}
			}

			// Check for empty, which means it'll show when.
			if ( empty( $maybe['display'] ) ) {

				// Output the title.
				$build .= '<span class="dppress-content-view-title">' . esc_html( $content['post_title'] ) . '</span>';

				// Handle our message if we have one.
				if ( ! empty( $maybe['message'] ) ) {
					$build .= ' <span class="dppress-content-view-date">' . wp_kses_post( $maybe['message'] ) . '</span>';
				}
			}

			// Close the list item.
			$build .= '</li>';
		}

		// Close the list.
		$build .= '</ul>';

	// Close up the div.
	$build .= '</div>';

	// Return the build if not echo'd.
	if ( ! $echo ) {
		return $build;
	}

	// Echo my build.
	echo $build;
}
