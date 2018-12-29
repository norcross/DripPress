<?php
/**
 * Our activation call.
 *
 * @package DripPress
 */

// Declare our namespace.
namespace DripPress\Activate;

// Set our alias items.
use DripPress as Core;
use DripPress\Process as Process;

/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function activate() {

	// Run the initial usermeta setup.
	Process\set_user_signup_meta();
	Process\set_initial_drip_sort();

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'activate_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( Core\FILE, __NAMESPACE__ . '\activate' );
