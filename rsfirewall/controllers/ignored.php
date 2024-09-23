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

class RSFirewall_Controller_Ignored extends RSFirewall_Controller
{
    public function view_content(){
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('view_contents');


        $this->display();
        exit();
    }

    public function remove() {
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('remove_ignored');

        $file_id = isset($_POST['ignored_file_id']) ? (int) sanitize_text_field($_POST['ignored_file_id']) : 0;

        $data    = new stdClass();
        $success = true;

        try {
            if ( empty($file_id) ) {
                throw new Exception ( esc_html__( 'The file id is empty!', 'rsfirewall' ) );
            }

            $this->model->remove($file_id);

        } catch ( Exception $e ) {
            $success         = false;
            $data->message = $e->getMessage();
        }

        $this->show_response( $success, $data );
    }
}