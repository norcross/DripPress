<?php
/**
 * The functionality tied to the WP-CLI stuff.
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress;

// Set our alias items.
use DripPress\Process as Process;

// Pull in the CLI items.
use WP_CLI;
use WP_CLI_Command;

/**
 * Extend the CLI command class with our own.
 */
class Commands extends WP_CLI_Command {

	/**
	 * Run our purge command.
	 *
	 * ## OPTIONS
	 *
	 * [--type]
	 * : What type of meta to purge.
	 * ---
	 * default: none
	 * options:
	 *   - content
	 *   - user
	 *   - all
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp drip-press purge
	 *
	 * @when after_wp_load
	 */
	function purge( $args = array(), $assoc_args = array() ) {

		// Parse out the associatives.
		$parsed = wp_parse_args( $assoc_args, array(
			'type' => '',
		));

		// Bail without a type.
		if ( empty( $parsed['type'] ) ) {
			WP_CLI::error( __( 'No meta type was declared.', 'drip-press' ) );
		}

		// Set our intital update value.
		$update = 0;

		// Handle the type switch.
		switch ( $parsed['type'] ) {

			// Handle the user meta.
			case 'user':
			case 'users':

				// Run the purge.
				$update = Process\purge_user_signup_meta();

	 			// And break.
	 			break;

			// Handle the content meta.
			case 'posts':
			case 'content':

				// Run the purge.
				$update = Process\purge_content_drip_meta();

	 			// And break.
	 			break;

			// Handle the combo meta.
			case 'all':

				// Run the twp purges.
				$users  = Process\purge_user_signup_meta();
				$drips  = Process\purge_content_drip_meta();

				// Add them up.
				$update = absint( $users ) + absint( $drips );

	 			// And break.
	 			break;

			// End all case breaks.
		}

		// Show the result and bail.
		WP_CLI::success( sprintf( _n( '%d row has been deleted.', '%d rows have been deleted.', absint( $update ), 'drip-press' ), absint( $update ) ) );
		WP_CLI::halt( 0 );
	}

	/**
	 * This is a placeholder function for testing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp drip-press runtests
	 *
	 * @when after_wp_load
	 */
	function runtests() {
		// This is blank, just here when I need it.
		// Show the result and bail.
		WP_CLI::success( 'Good job you did the thing' );
		WP_CLI::halt( 0 );
	}

	// End all custom CLI commands.
}
