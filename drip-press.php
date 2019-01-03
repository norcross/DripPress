<?php
/**
 * Plugin Name: DripPress
 * Plugin URI:  https://github.com/norcross/drip-press
 * Description: Control content visibility based on user signup date
 * Version:     0.2.0
 * Author:      Andrew Norcross
 * Author URI:  http://andrewnorcross.com
 * Text Domain: drip-press
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package DripPress
 */

// Call our namepsace.
namespace DripPress;

// Call our CLI namespace.
use WP_CLI;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Define our version.
define( __NAMESPACE__ . '\VERS', '0.2.0' );

// Define our file base.
define( __NAMESPACE__ . '\BASE', plugin_basename( __FILE__ ) );

// Plugin Folder URL.
define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );

// Plugin root file.
define( __NAMESPACE__ . '\FILE', __FILE__ );

// Set our assets directory constant.
define( __NAMESPACE__ . '\ASSETS_URL', URL . 'assets' );

// Set our actions and meta key prefix.
define( __NAMESPACE__ . '\HOOK_PREFIX', 'dppress_' );
define( __NAMESPACE__ . '\META_PREFIX', 'dppress_' );
define( __NAMESPACE__ . '\NONCE_PREFIX', 'dppress_nonce_' );

// Files that go on both ends.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/utilities.php';
require_once __DIR__ . '/includes/formatting.php';
require_once __DIR__ . '/includes/process.php';
require_once __DIR__ . '/includes/ajax.php';
require_once __DIR__ . '/includes/users.php';

// Load the activation and deactivation items.
require_once __DIR__ . '/includes/activate.php';
require_once __DIR__ . '/includes/deactivate.php';
require_once __DIR__ . '/includes/uninstall.php';

// Include the front-end files.
if ( ! is_admin() ) {
	require_once __DIR__ . '/includes/front-end.php';
	require_once __DIR__ . '/includes/shortcodes.php';
}

// Include the admin specific ones.
if ( is_admin() ) {
	require_once __DIR__ . '/includes/admin.php';
	require_once __DIR__ . '/includes/quick-bulk.php';
	require_once __DIR__ . '/includes/post-meta.php';
}

// Check that we have the constant available.
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	// Load our commands file.
	require_once __DIR__ . '/includes/commands.php';

	// And add our command.
	WP_CLI::add_command( 'drip-press', Commands::class );
}
