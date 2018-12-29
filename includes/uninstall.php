<?php
/**
 * Our uninstall call.
 *
 * @package DripPress
 */

// Declare our namespace.
namespace DripPress\Uninstall;

// Set our aliases.
use DripPress as Core;
use DripPress\Process as Process;

/**
 * Delete various options when uninstalling the plugin.
 *
 * @return void
 */
function uninstall() {

	// Purge the initial usermeta setup.
	Process\purge_user_signup_meta();

	// Purge the stored post meta.
	Process\purge_content_drip_meta();

	// Include our action so that we may add to this later.
	do_action( Core\HOOK_PREFIX . 'uninstall_process' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_uninstall_hook( Core\FILE, __NAMESPACE__ . '\uninstall' );
