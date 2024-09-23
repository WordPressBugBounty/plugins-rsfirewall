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

/**
 * Class RSFirewall_Controller_Check
 *
 */
class RSFirewall_Controller_Check extends RSFirewall_Controller {
	/**
	 * RSFirewall_System_Check_Controller constructor.
	 *
	 * @since 1.0.0
	 */
	private $class_used;

	public function __construct() {
		parent::__construct();

		$this->class_used = get_class($this);
	}

	public function enqueue_scripts($pagenow){
		if ($pagenow != 'rsfirewall_page_rsfirewall_check') {
			return;
		}

		// Load modal JS files
		RSFirewall_Helper::load_rsmodal('dependencies.js', $this->version);

		wp_enqueue_script( 'knob', RSFIREWALL_URL . 'assets/js/vendors/jquery.knob.js', array( 'jquery' ), $this->version, false );

		wp_register_script( 'rsfirewall_check', RSFIREWALL_URL . 'assets/js/rsfirewall_check.js', array(
			'jquery',
			'rsfirewall_diff'
		), $this->version, false );

		$params = array(
			'view_contents_nonce'  			=> wp_create_nonce( 'view_contents' ),
			'step_check_nonce'  			=> wp_create_nonce( 'step_check' ),
			'save_grade_nonce'  			=> wp_create_nonce( 'save_grade' ),
			'admin_username_fix_nonce' 	 	=> wp_create_nonce( 'admin_username_fix' ),
			'php_configuration_fix_nonce'   => wp_create_nonce( 'php_configuration_fix' ),
			'ignore_hashes_nonce'  			=> wp_create_nonce( 'ignore_hashes' ),
			'fix_delete_revisions_nonce'    => wp_create_nonce( 'fix_delete_revisions' ),
			'remove_ignored_none'    		=> wp_create_nonce( 'remove_ignored' ),
		);

		if ($this->class_used != 'RSFirewall_Controller_CheckPro') {
			wp_localize_script('rsfirewall_check', 'rsfirewall_check_locale', RSFirewall_i18n::get_locale('rsfirewall_check'));
			wp_localize_script('rsfirewall_check', 'rsfirewall_check_security', $params);
			wp_enqueue_script('rsfirewall_check');
		} else {
			return $params;
		}
	}

	public function enqueue_styles($pagenow) {
		if ($pagenow != 'rsfirewall_page_rsfirewall_check') {
			return;
		}

		// Load modal CSS files
		RSFirewall_Helper::load_rsmodal('dependencies.css', $this->version);
	}

	/**
	 * Checks your current WordPress version.
	 */
	public function wp_version_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$data    = new stdClass();
		$success = true;

		try {
			if ( !$result = $this->model->wp_version_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			if ( $result['api_response'] == 'latest' ) {
				$data->result  = true;
				$data->message = sprintf( esc_html__( 'Your WordPress version ( %s ) is up to date.', 'rsfirewall' ), $result['current_version'] );
			} else {
				$data->result  = false;
				$data->message = sprintf( esc_html__( 'Your WordPress version ( %s ) is outdated. We strongly recommend updating to %s', 'rsfirewall' ), $result['current_version'], $result['latest_version'] );
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks your current RSFirewall! version.
	 */
	public function rsfirewall_version_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$data    = new stdClass();
		$success = true;

		try {
			if ( ! $result = $this->model->rsfirewall_version_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			if ( $result['api_response'] > - 1 ) {
				$data->result  = true;
				$data->message = sprintf( esc_html__( 'RSFirewall! version ( %s ) is up to date.', 'rsfirewall' ), $result['current_version'] );
			} else {
				$data->result  = false;
				$data->message = sprintf( esc_html__( 'Your RSFirewall! version ( %s ) is outdated. We strongly recommend updating to %s.', 'rsfirewall' ), $result['current_version'], $result['latest_version'] );
				$data->details = wp_kses_post( __('A new version of RSFirewall! has been released. You can see the complete list of changes in the official plugin changelog found <a href="https://www.rsjoomla.com/support/documentation/rsfirewall-wordpress/changelog/rsfirewall-for-wordpress-changelog.html" target="_blank">here</a>.', 'rsfirewall') );
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks your database password
	 */
	public function weak_database_pswrd_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;
		try {
			if ( ! $result = $model->weak_database_pswrd_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = true;
			$data->message = esc_html__( 'Your Database Password is OK.', 'rsfirewall' );

			if ( $result['weak_password'] ) {
				$data->result  = false;
				$data->message = $result['message'];
				$data->details = $result['details'];
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks if you have a weak username
	 */
	public function admin_username_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;
		try {
			if ( ! $result = $model->admin_username_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = true;
			$data->message = $result['message'];

			$details = '';
			if ( ! empty( $result['users'] ) ) {
				$data->result = false;
				foreach ( $result['users'] as $user ) {
					$details .= $user['details'] . '</br>';
				}
				$data->details = $details;
				$data->message = $result['message'];
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks if you have a dangerous username
	 */
	public function admin_unwanted_username_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;
		try {
			if ( ! $result = $model->admin_unwanted_username_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = true;
			$data->message = $result['message'];

			$details = '';
			if ( ! empty( $result['users'] ) ) {
				$data->result = false;
				foreach ( $result['users'] as $user ) {
					$details .= $user['details'] . '</br>';
				}
				$data->details = $details;
				$data->message = $result['message'];
			}

		} catch ( Exception $e ) {
			$success         = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks if you have SEF links enabled
	 */
	public function sef_enabled_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;
		try {
			if ( ! $result = $model->sef_enabled_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = $result['sef_enabled'];
			$data->message = $result['message'];

			if ( ! $data->result ) {
				$data->details = $result['details'];
			}

		} catch ( Exception $e ) {
			$success       = false;
			$data->message = $e->getMessage();
		}

		$this->show_response( $success, $data );
	}

	/**
	 * Checks for the post/page revisions
	 */
	public function revisions_check() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;
		try {
			if ( ! $result = $model->revisions_check() ) {
				throw new Exception ( esc_html__( 'RSFirewall! Encountered an error', 'rsfirewall' ) );
			}

			$data->result  = $result['result'];
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
	 * Checks the allow_url_include directive from PHP ini
	 */
	public function check_allow_url_include() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		$data->result  = ! $model->check_allow_url_include();
		$data->message = $data->result ? esc_html__( 'allow_url_include is Off', 'rsfirewall' ) : esc_html__( 'allow_url_include is On', 'rsfirewall' );

		$this->show_response( $success, $data );
	}

	/**
	 * Checks the open_basedir directive from PHP ini
	 */
	public function check_open_basedir() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		$data->result  = ! $model->check_open_basedir();
		$data->message = $data->result ? esc_html__( 'open_basedir is used.', 'rsfirewall' ) : esc_html__( 'open_basedir is not used.', 'rsfirewall' );

		$this->show_response( $success, $data );
	}

	/**
	 * Checks the disable functions directives from PHP ini
	 */
	public function check_disable_functions() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		$result = $model->check_disable_functions();

		$data->result  = $result['result'];
		$data->message = $result['message'];

		$this->show_response( $success, $data );
	}

	/**
	 * Checks if safe_mode is used
	 */
	public function check_safe_mode() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		$data->result  = ! $model->check_safe_mode();
		$data->message = $data->result ? esc_html__( 'safe_mode is off.', 'rsfirewall' ) : esc_html__( 'safe_mode is on', 'rsfirewall' );

		$this->show_response( $success, $data );
	}

	/**
	 * Checks if register_globals is used
	 */
	public function check_register_globals() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('step_check');

		$model   = $this->model;
		$data    = new stdClass();
		$success = true;

		$data->result  = ! $model->check_register_globals();
		$data->message = $data->result ? esc_html__( 'register_globals is off.', 'rsfirewall' ) : esc_html__( 'register_globals is on', 'rsfirewall' );

		$this->show_response( $success, $data );
	}

	/**
	 * Saves the scan score(grade) to the database for further use
	 */
	public function save_grade() {
		// Check the nonce for this action
		RSFirewall_Helper::check_nonce('save_grade');

		$this->model->save_grade();
		die;
	}
}