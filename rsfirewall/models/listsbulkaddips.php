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

class RSFirewall_Model_ListsBulkAddIps extends RSFirewall_Post {
	
	protected $lists_model;

    public function getPrefix()
    {
        return $this->prefix;
    }

	public function __construct()
    {
		parent::__construct();
		$this->lists_model = RSFirewall_Model_Lists::get_instance();
	}

    /**
	 * Function to bulk add IPs to the list
	 */
	public function bulk_add_ips()
    {
		$back_url = admin_url( 'edit.php?post_type='.$this->prefix.'lists');
		$back_url_form = admin_url( 'admin.php?page=rsfirewall_lists_bulk_add_ips');

		// Check if the form is submitted
		if (!isset($_POST['data_bulk']))
        {
			$this->set_message(__('No data received!', 'rsfirewall'));
			RSFirewall_Helper::redirect($back_url_form);
			return;
		}

		$ips_text = sanitize_textarea_field($_POST['data_bulk']['rsfirewall_bulk_ips']);
		$type = sanitize_text_field($_POST['data_bulk']['rsfirewall_bulk_type']);
		$reason = sanitize_textarea_field($_POST['data_bulk']['rsfirewall_bulk_reason']);

		// Normalize line endings
		$ips_text = str_replace("\r\n", "\n", $ips_text);
		
		// Split IPs by new line
		$ips = array_filter(array_map('trim', explode("\n", $ips_text)));

		if (empty($ips))
        {
			$this->set_message(__('No IP addresses provided!', 'rsfirewall'));
			RSFirewall_Helper::redirect($back_url_form);
			return;
		}

		$added_count = 0;
		$error_count = 0;
		$error_messages = array();

		foreach ($ips as $ip)
        {
			// Check if the IP is valid using Lists model's check_ip method
			if ($this->lists_model->check_ip($ip, 0, $type, false, false))
            {
				// Create a new post for this IP
				$post_data = array(
					'post_type'   => $this->prefix.'lists',
					'post_status' => 'publish',
					'post_title'  => $ip
				);

				$post_id = wp_insert_post($post_data);

				if ($post_id && !is_wp_error($post_id))
                {
					// Add post meta
					add_post_meta($post_id, 'rsfirewall_ip', $ip);
					add_post_meta($post_id, 'rsfirewall_type', $type);
					add_post_meta($post_id, 'rsfirewall_reason', $reason);
					$added_count++;
				} 
                else
                {
					$error_count++;
					$error_messages[] = sprintf(__('Failed to add IP: %s', 'rsfirewall'), $ip);
				}
			}
            else
            {
				$error_count++;
				$error_messages[] = sprintf(__('Invalid or duplicate IP: %s', 'rsfirewall'), $ip);
			}
		}

		// Set success message
		if ($added_count > 0)
        {
			$message = sprintf(_n('%d IP address has been added successfully!', '%d IP addresses have been added successfully!', $added_count, 'rsfirewall'), $added_count);
			$this->set_message($message, 'success');
		}

		// Set error message if any
		if ($error_count > 0)
        {
			$error_summary = sprintf(_n('%d IP address could not be added.', '%d IP addresses could not be added.', $error_count, 'rsfirewall'), $error_count);
			$this->set_message($error_summary . ' ' . __('Please check the IPs and try again.', 'rsfirewall'));

			// Store form data in transient to preserve values
			set_transient('rsf_bulk_add_ips_form_data', array(
				'rsfirewall_bulk_ips' => $ips_text,
				'rsfirewall_bulk_type' => $type,
				'rsfirewall_bulk_reason' => $reason
			), 60); // Keep for 60 seconds

			RSFirewall_Helper::redirect($back_url_form);
		}

		// Redirect to the list
		RSFirewall_Helper::redirect($back_url);
	}

	/**
	 * Get saved form data from transient
	 */
	public function get_saved_form_data()
    {
		$data = get_transient('rsf_bulk_add_ips_form_data');
		if ($data)
        {
			delete_transient('rsf_bulk_add_ips_form_data');
			return $data;
		}

		return array(
			'rsfirewall_bulk_ips' => '',
			'rsfirewall_bulk_type' => '0',
			'rsfirewall_bulk_reason' => ''
		);
	}
}