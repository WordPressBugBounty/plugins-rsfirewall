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


class RSFirewall_Helper_Users {
    protected static $users = null;

    public static function getAdminUsers() {
        if (!is_array(self::$users)) {
            self::$users = array();

            $args = array(
                'role'   => 'administrator',
                'fields' => 'ID'
            );

            self::$users = get_users($args);
        }

        return self::$users;
    }
}