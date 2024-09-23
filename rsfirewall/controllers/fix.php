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

class RSFirewall_Controller_Fix extends RSFirewall_Controller {
	/**
	 * @var bool|int
	 * @since 1.0.0
	 */
	protected $folder_permissions = 755;
	/**
	 * @var bool|int
	 * @since 1.0.0
	 */
	protected $file_permissions = 644;

	public function __construct() {
        parent::__construct();

		$this->folder_permissions = RSFirewall_Config::get( 'folder_permissions' );
		$this->file_permissions   = RSFirewall_Config::get( 'file_permissions' );
	}

	/**
	 * Changes usernames
	 *
	 * @param
	 *
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function admin_username_fix() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('admin_username_fix');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			if ( !$result = $model->admin_username_fix( $args ) ) {
				throw new Exception( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall'));
			}

			$data->result  = true;
			$data->message = $result['message'];

			if ( $result['weak_username'] ) {
				$data->result  = false;
				$data->details = $result['details'];
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Deletes users
	 *
	 * @param
	 *
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function delete_admin_user() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('delete_admin_user');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			$model->delete_admin_user( $args );

			$data->result  = true;
			$data->message = esc_html__( 'This administrator has successfully been deleted!', 'rsfirewall' );

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Attempts to fix the PHP ini by creating a local php.ini file
	 *
	 * @since 1.0.0
	 */
	public function php_configuration_fix() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('php_configuration_fix');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			if ( ! $result = $model->php_configuration_fix() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = $result['success'];
			$data->message = $result['message'];

			if ( ! empty( $result['details'] ) ) {
				$data->details = $result['details'];
			}

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Function that writes in the rsfirewall_ignored table.
	 *
	 * @since 1.0.0
	 */
	public function ignore_files() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('ignore_files');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			$result = $model->ignore_stuff( $args, 'files', 'rsfirewall_ignored' );


			$data->result  = $result['result'];
			$data->message = $result['message'];

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Function to ignore certain hashes from the system check
	 *
	 * @since 1.0.0
	 *
	 */
	public function ignore_hashes() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('ignore_hashes');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			$result = $model->ignore_stuff( $args, 'hashes', 'rsfirewall_hashes' );

			$data->result  = $result['result'];
			$data->message = $result['message'];

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Set the Folder Permissions where needed
	 *
	 * @since 1.0.0
	 */
	public function fix_folder_permissions() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('fix_folder_permissions');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$success = true;
		$folders = array();
		$data    = new stdClass();

		if ( empty( $args['folders'] ) ) {
			$data->result  = false;
			$data->message = esc_html__( 'No folders selected', 'rsfirewall' );
			$this->show_response( $success, $data );
		}

		foreach ( $args['folders'] as $file ) {
			$folders[] = stripslashes( $file );
		}

		try {
			$data->result = true;
			$data->results = $model->set_permissions($folders, $this->folder_permissions);
			$data->message = esc_html__('Folder permissions have been set!', 'rsfirewall');
		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Set the File Permissions where needed
	 *
	 * @since 1.0.0
	 */
	public function fix_file_permissions() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('fix_file_permissions');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$success = true;
		$files   = array();

		$data = new stdClass();

		if ( empty( $args['files'] ) ) {
			$data->result  = false;
			$data->message = esc_html__( 'No files selected', 'rsfirewall' );
			$this->show_response( $success, $data );
		}

		foreach ( $args['files'] as $file ) {
			$files[] = stripslashes( $file );
		}

		try {
			$data->result  = true;
			$data->results = $model->set_permissions( $files, $this->file_permissions );
			$data->message = esc_html__( 'File permissions have been set!', 'rsfirewall' );
		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Delete the post revisions
	 *
	 * @since 1.0.0
	 *
	 */
	public function fix_delete_revisions() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('fix_delete_revisions');

		$model   = $this->model;
		$success = true;
		$data    = new stdClass();

		try {
			$result = $model->fix_delete_revisions();

			$data->result  = $result['result'];
			$data->message = $result['message'];

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Remove the ip from the blocklist
	 *
	 * @since 1.0.0
	 */
	public function remove_ip() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('remove_ip');

		$args 	 = $this->get_current_args();
		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		try {
			$result = $model->remove_ip( $args['ip'] );

			$data->result  = $result['result'];
			$data->message = $result['message'];

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}



		$this->show_response( $success, $data );

	}

	/**
	 * Function to get the current arguments sent by post/ get
	 *
	 * @param string - type
	 *
	 * @return array
	 */
	protected function get_current_args($type = 'post') {
		$args = array();

		if ($type == 'post' && isset($_POST['args'])) {
			$args = $this->sanitize_args($_POST['args']);
		} else if ($type == 'get' && isset($_GET['args'])) {
			$args = $this->sanitize_args($_GET['args']);
		}

		return $args;
	}

	/**
	 * Function to sanitize all the arguments
	 *
	 * @param array - args
	 *
	 * @return array
	 */
	protected function sanitize_args($args) {
		$sanitized_args = array();

		if (!empty($args))
		{
			foreach ($args as $key => $argument)
			{
				if (!is_array($argument)) {
					$sanitized_args[$key] = sanitize_text_field($argument);
				}
				else
				{
					$sanitized_args[$key] = $this->sanitize_args($argument);
				}
			}
		}

		return $sanitized_args;
	}
}