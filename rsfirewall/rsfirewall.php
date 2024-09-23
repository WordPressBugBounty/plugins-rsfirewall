<?php
/**
 * @package        RSFirewall!
 * @copyright  (c) 2018-2023 RSJoomla!
 * @link           https://www.rsjoomla.com
 * @license        GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

/*
Plugin Name: RSFirewall!
Plugin URI: https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html
Description: Based on the success of the most popular firewall for Joomla!, RSFirewall! is now available to protect your WordPress website as well.
Version: 1.1.34
Author: RSJoomla!
Author URI: https://www.rsjoomla.com
License: GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define constants
define( 'RSFIREWALL_BASE', plugin_dir_path( __FILE__ ) );
define( 'RSFIREWALL_URL', plugin_dir_url( __FILE__ ) );
define( 'RSFIREWALL_REL_PATH', basename( dirname( __FILE__ ) )) ;
define( 'RSFIREWALL_SITE', rtrim(ABSPATH, '\\/') );
define( 'RSFIREWALL_POSTS_PREFIX', 'rsf_' );

require_once RSFIREWALL_BASE.'libraries/version.php';

/**
 * The code that runs during plugin activation.
 */
function activate_rsfirewall() {
	if (file_exists(RSFIREWALL_BASE.'proversion/installer/installer.php')) {
		require_once RSFIREWALL_BASE.'proversion/installer/installer.php';
	} else {
		require_once RSFIREWALL_BASE . 'installer/installer.php';
	}

	require_once RSFIREWALL_BASE . 'helpers/rsfirewall.php';
	RSFirewall_Installer::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_rsfirewall() {
	if (file_exists(RSFIREWALL_BASE.'proversion/installer/installer.php')) {
		require_once RSFIREWALL_BASE.'proversion/installer/installer.php';
	} else {
		require_once RSFIREWALL_BASE . 'installer/installer.php';
	}
	RSFirewall_Installer::deactivate();
}

/**
 * The code that runs during plugin deactivation.
 */
function uninstall_rsfirewall() {
	if (file_exists(RSFIREWALL_BASE.'proversion/installer/installer.php')) {
		require_once RSFIREWALL_BASE.'proversion/installer/installer.php';
	} else {
		require_once RSFIREWALL_BASE . 'installer/installer.php';
	}
	RSFirewall_Installer::uninstall();
}

register_activation_hook( __FILE__, 'activate_rsfirewall' );
register_deactivation_hook( __FILE__, 'deactivate_rsfirewall' );
register_uninstall_hook( __FILE__, 'uninstall_rsfirewall' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once RSFIREWALL_BASE . 'libraries/autoloader.php';
require_once RSFIREWALL_BASE . 'libraries/rsfirewall.php';

// Load the proversion too if exists
if (file_exists(RSFIREWALL_BASE.'proversion/libraries/rsfirewall.php')) {
	require_once RSFIREWALL_BASE . 'proversion/libraries/rsfirewall.php';
}


/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */

function run_rsfirewall()
{
	$rsfirewall = RSFirewall_Helper::call_user_func_pro(array('RSFirewall', 'get_instance'));

    /**
     * Load language files
     */
    $rsfirewall->set_locale();

    /**
     * Define updates hooks
     */
	$rsfirewall->enable_updates();


    /**
     * Define global hooks (both front and admin)
     */
    $rsfirewall->define_global_hooks();

    /**
     * Define admin hooks
     */
    $rsfirewall->define_admin_hooks();

    /**
     * Start the buffer and callback function
     */
	if (method_exists($rsfirewall, 'setup_buffer')) {
		$rsfirewall->setup_buffer();
	}

    /**
     * Create admin menu
     */
    if (is_admin())
    {
		RSFirewall_Installer::upgrade();
		
        $rsfirewall->add_menu_pages();
		$rsfirewall->add_settings_link();
    }
}

if (file_exists(RSFIREWALL_BASE.'proversion/installer/installer.php')) {
	require_once RSFIREWALL_BASE.'proversion/installer/installer.php';
} else {
	require_once RSFIREWALL_BASE . 'installer/installer.php';
}

if (RSFirewall_Installer::check_version(false)) {
	run_rsfirewall();
}