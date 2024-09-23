<?php
/**
 * @package        RSFirewall!
 * @copyright  (c) 2018 RSJoomla!
 * @link           https://www.rsjoomla.com
 * @license        GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}


class RSFirewall_Version
{
    public $version = '1.1.34';
    public $key = 'RSJFIREWALLWP';

    public function __construct() {}

    public static function get_instance() {
        static $inst;
        if (is_null($inst)) {
            $inst = new RSFirewall_Version();
        }

        return $inst;
    }

    public function activate_updates() {
        require_once RSFIREWALL_BASE . 'libraries/autoupdate.php';

        $args = array(
            'current_version' => $this->version,
            'slug'            => 'rsfirewall',
            'update_code'     => RSFirewall_Config::get('code'),
            'key'             => $this->key,
            'type'            => 'plugin'
        );
		
		// check if this is a lite version
        if (!file_exists(RSFIREWALL_BASE.'proversion/libraries/rsfirewall.php')) {
            $args['is_lite'] = true;
        }

        new RSFirewall_Autoupdate ( $args );
    }

    public function get_latest_version() {

        // the function may not be available
        if ( ! function_exists( 'plugins_api' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        }

        $args = array(
            'slug' => 'rsfirewall',
            'per_page' => 1,
            'fields' => array (
                'version'           => true,
                'versions'          => true,
                'short_description' => false,
                'description'       => false,
                'sections'          => false,
                'tested'            => false,
                'requires'          => false,
                'rating'            => false,
                'ratings'           => false,
                'downloaded'        => false,
                'downloadlink'      => false,
                'last_updated'      => false,
                'added'             => false,
                'tags'              => false,
                'compatibility'     => false,
                'homepage'          => false,
                'donate_link'       => false,
            )
        );

        $call_api = plugins_api('plugin_information', $args);

        if ( !is_wp_error( $call_api )) {
            if (is_array($call_api)) {
                $call_api = (object) $call_api;
            }

            // compatibility format
            $version = new stdClass();
            $version->new_version = $call_api->version;

            return $version;
        }

        return false;
    }
}