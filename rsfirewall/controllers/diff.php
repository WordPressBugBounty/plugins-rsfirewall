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

class RSFirewall_Controller_Diff extends RSFirewall_Controller
{
    public function download_file() {
        $model = $this->model;
        $file  = $this->model->get_file();

        $result = $model->download_original_file( $file );
        echo json_encode($result);

        exit();
    }

    public function enqueue_scripts($pagenow){
        // need to load this script also on the check page, because it is a dependency to the check script
        if ($pagenow != 'rsfirewall_page_rsfirewall_diff' && $pagenow != 'rsfirewall_page_rsfirewall_check') {
            return;
        }
        wp_register_script( 'rsfirewall_diff', RSFIREWALL_URL . 'assets/js/rsfirewall_diff.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( 'rsfirewall_diff',  'rsfirewall_diff_locale', RSFirewall_i18n::get_locale( 'rsfirewall_diff' ) );
        wp_enqueue_script( 'rsfirewall_diff' );
    }

    public function view_differences(){
        // Check the nonce for this action

        RSFirewall_Helper::check_nonce('view_contents');

        $this->display();
        exit();
    }
}