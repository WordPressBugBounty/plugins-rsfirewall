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

class RSFirewall_Controller_Dashboard extends RSFirewall_Controller
{
    public function enqueue_scripts($pagenow){
        if ($pagenow != 'toplevel_page_rsfirewall') {
            return;
        }

        // Load modal JS files
        RSFirewall_Helper::load_rsmodal('dependencies.js', $this->version);

        wp_enqueue_script( 'flot', RSFIREWALL_URL . 'assets/js/vendors/jquery.flot.min.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( 'flot-categories', RSFIREWALL_URL . 'assets/js/vendors/jquery.flot.categories.min.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( 'flot-resize', RSFIREWALL_URL . 'assets/js/vendors/jquery.flot.resize.min.js', array( 'jquery' ), $this->version, false );

        wp_register_script( 'rsfirewall_diff', RSFIREWALL_URL . 'assets/js/rsfirewall_diff.js', array(
            'jquery',
            'rsfirewall'
        ), $this->version, false );

        $params = array(
            'view_contents_nonce' => wp_create_nonce( 'view_contents' ),
            'ignore_hashes_nonce' => wp_create_nonce( 'ignore_hashes' ),
        );


        wp_localize_script( 'rsfirewall_diff',  'rsfirewall_diff_locale', RSFirewall_i18n::get_locale( 'rsfirewall_diff' ) );
        wp_localize_script( 'rsfirewall_diff',  'rsfirewall_check_security', $params);
        wp_enqueue_script( 'rsfirewall_diff' );
    }

    public function enqueue_styles($pagenow) {
        if ($pagenow != 'toplevel_page_rsfirewall') {
            return;
        }

        // Load modal CSS files
        RSFirewall_Helper::load_rsmodal('dependencies.css', $this->version);
    }
}