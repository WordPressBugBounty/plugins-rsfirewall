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

class RSFirewall_Controller_Folders extends RSFirewall_Controller
{
    public function open_file_manager() {
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('open_file_manager');

        $this->model->open_file_manager();

        $this->display();
        exit();
    }

}