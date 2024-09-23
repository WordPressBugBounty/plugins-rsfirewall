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

class RSFirewall_Model_Ignored extends RSFirewall_Model
{
    public function get_files()
    {
        global $wpdb;
        $wpdb->show_errors();
        $table = $wpdb->prefix . 'rsfirewall_hashes';

        $results = $wpdb->get_results(
            "SELECT *
			 FROM $table
			 WHERE `type` = 'ignore'
			",
            OBJECT
        );

        return $results;
    }

    public function remove($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rsfirewall_hashes';

        $result = $wpdb->delete($table, array('id' => $id));

        if (is_bool($result) && !$result) {
            throw new Exception ( esc_html__( 'The file could not be removed!', 'rsfirewall' ) );
        }

        return $result;

    }
}