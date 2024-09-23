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

class RSFirewall_Controller_Configuration extends RSFirewall_Controller
{
    public function enqueue_scripts($pagenow){
        if ($pagenow != 'rsfirewall_page_rsfirewall_configuration') {
            return;
        }

        wp_enqueue_script( 'selectize', RSFIREWALL_URL . 'assets/js/vendors/selectize.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( 'switchery', RSFIREWALL_URL . 'assets/js/vendors/switchery.min.js', array( 'jquery' ), $this->version, false );

        // Load modal JS files
        RSFirewall_Helper::load_rsmodal('dependencies.js', $this->version);


        // Add specific sections script
        $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

        switch($section) {
            case 'rsfirewall_system_check':
            case 'rsfirewall_core_scanner':
                $params = array(
                    'file_manager_nonce'              => wp_create_nonce( 'open_file_manager' ),
                    // Language variables
                    'modal_select_items_button_text'  => __('Add selected items', 'rsfirewall')
                );

                wp_localize_script( 'rsfirewall', 'rsfirewall_file_manager', $params );
            break;
            case 'rsfirewall_hardening':
                $params = array(
                    'whitelist_php_files_nonce'       => wp_create_nonce( 'whitelist_php_files' ),
                    'modal_please_make_selection'     => __('Please make a selection first!', 'rsfirewall'),
                    'modal_no_noce_detected'          => __('You have tempered with the modal layout and removed the security element!', 'rsfirewall'),
                    'modal_no_file_specified'         => __('Please enter a filename!', 'rsfirewall'),
                );

                wp_localize_script( 'rsfirewall', 'rsfirewall_whitelist_php_files', $params );
            break;
        }

    }

    public function enqueue_styles($pagenow) {
        if ($pagenow != 'rsfirewall_page_rsfirewall_configuration') {
            return;
        }

        wp_enqueue_style( 'selectize', RSFIREWALL_URL . 'assets/js/vendors/selectize.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'switchery', RSFIREWALL_URL . 'assets/js/vendors/switchery.min.css', array(), $this->version, 'all' );

        // Load modal CSS files
        RSFirewall_Helper::load_rsmodal('dependencies.css', $this->version);
    }

    public function export_configuration() {
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="configuration_'.parse_url(get_site_url(), PHP_URL_HOST).'.json"');

        echo $this->model->export_configuration();

        exit();
    }
	
	public function delete_disabled() {
        if (file_exists(RSFIREWALL_BASE.'.disabled')) {
            if(unlink(RSFIREWALL_BASE.'.disabled')) {
                set_transient( 'global_admin_notice',  wp_kses_post(__( 'The <strong><em>.disabled</em></strong> file has been <strong>successfully removed</strong>.', 'rsfirewall' )), 5);
            } else {
                set_transient( 'global_admin_notice', wp_kses_post(__( 'The <strong><em>.disabled</em></strong> file <strong>could not be removed</strong>!<br/> Please remove it manually.', 'rsfirewall' )), 5);
            }
        }

        wp_redirect( wp_get_referer() );
        exit();
    }

    // Safelist PHP Files tasks
    public function whitelist_php_form_list($skip_nonce = false, $action_message = false, $postfields = array()) {
        if (!$skip_nonce) {
            // Check the nonce for this action
            RSFirewall_Helper::check_nonce('whitelist_php_files');
        }

        // Get the current whitelisted files from all hardened directories
        $this->whitelisted_files = RSFirewall_Helper_Harden::whitelisted_php_files();

        // Build the select folder option
        $this->harden_folders = RSFirewall_Helper::check_hardened_directories();

        // Add the action message if any
        $this->action_message = $action_message;

        // Set the post fields for the form
        $this->postfields = $postfields;

        $this->display('whitelist_form');
        exit();
    }

    public function delete_whitelisted_files() {
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('delete_whitelisted');

        $message = new stdClass();
        $message->type = 'danger';

        if (!isset($_POST['files'])) {
            $message->message = esc_html__('There are no files to be removed selected!', 'rsfirewall');
        } else {
            try {
                // clean the post data
                $sanitized_files = array();
                foreach($_POST['files'] as $i => $file) {
                    $sanitized_files[] = array(
                        'file'   => sanitize_text_field($file['file']),
                        'folder' => sanitize_text_field($file['folder'])
                    );
                }
                $this->model->remove_whitelisted($sanitized_files);
                $message->type = 'success';
                $message->message = esc_html__('The selected PHP files have been removed from the safelist!', 'rsfirewall');
            } catch(Exception $e) {
                $message->message = esc_html__('There was a problem: ', 'rsfirewall').$e->getMessage();
            }
        }

        $this->whitelist_php_form_list(true, $message);
    }

    public function add_file_to_whitelist() {
        // Check the nonce for this action
        RSFirewall_Helper::check_nonce('add_to_whitelist');

        $message = new stdClass();
        $message->type = 'danger';

        $postfields = array();
        $file   = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
        $folder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : '';

        try {
            $this->model->add_whitelisted($file, $folder);

            // set the success message
            $message->type      = 'success';
            $message->message   = esc_html__('The file has been added to the proper safelist!', 'rsfirerwall');
        } catch (Exception $e) {
            // set the post field in case of eny error
            $postfields['file']     = $file;
            $postfields['folder']   = $folder;

            // the proper error message
            $message->message = esc_html__('There was a problem: ', 'rsfirewall').$e->getMessage();
        }

        $this->whitelist_php_form_list(true, $message, $postfields);
    }
}