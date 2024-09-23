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

class RSFirewall_Controller_File extends RSFirewall_Controller
{
    public function view_content(){
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('view_contents');


        $this->display();
        exit();
    }
}