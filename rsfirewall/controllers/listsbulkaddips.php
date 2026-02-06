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

class RSFirewall_Controller_ListsBulkAddIps extends RSFirewall_Controller
{
    public function bulk_add_ips()
    {
        $this->model->bulk_add_ips();
    }
}