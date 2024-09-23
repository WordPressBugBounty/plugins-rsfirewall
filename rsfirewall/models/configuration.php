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

class RSFirewall_Model_Configuration extends RSFirewall_Model {

	public function __construct() {
		/**
		 * Need to construct the parent here to initiate $this->form
		 */
		parent::__construct();

		/**
		 * Hook into admin_init to add our settings page
		 */
		add_action( 'admin_init', array( $this, 'init_config' ) );

		/*
		 * We need to add json file type to the mime_types filter, because the .json is not present by default
		 */
		add_filter('mime_types', array($this, 'mime_types'));
	}

	public function mime_types($types) {
		if (!isset($types['json'])) {
			$types['json'] = 'application/json';
		}

		return $types;
	}

	public function init_config() {
		foreach ( $this->form->sections as $section ) {
			// Add the calback for this section if specified
			$callback  	   = isset($section['callback']) ? array($this, $section['callback']) : array();
			// Determine if the label is hidden or not
			$section_label = (isset($section['hide_label']) && $section['hide_label']) ? '&shy;' : $section['label'];

			add_settings_section(
				$section['name'],
				$section_label,
				$callback,
				$section['name']
			);

			/** Load values from the database or use defaults from XML form */
			$options     = get_option( $section['name'] );
			$must_update = false;
			if ( $options === false ) {
				$must_update = true;
				$options     = array();
			}

			foreach ( $section['fields'] as $field ) {
				$value = NULL;
				if ( isset( $options[ $field['name'] ] ) ) {
					$value = $options[ $field['name'] ];
				} else {
					if ( $default = (string) $field['field']->attributes()->default ) {
						if ($field['name'] == 'dot_files') {
							$default = str_replace('\n', "\n", $default);
						}

						$value                     = $default;
						$options[ $field['name'] ] = $default;
					}
				}

				// For the "ignore_files_or_folders" option we need to take in consideration the values added by the System Check
				if ($field['name'] == 'ignore_files_or_folders' && $field['type'] != 'only_pro_field') {

					// get the ignored files and folders from the database, so that they can be present in the textarea
					$check_model = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Check', 'get_instance'));
					// build the ignore variable
					$check_model->_get_ignored();

					$ignored = $check_model->ignored;
					if(!empty($ignored)) {
						// what is already ignored in the database
						$already_ignored = array();
						foreach ($ignored as $type => $paths) {
							if (!empty($paths)) {
								$already_ignored = array_merge ($already_ignored, $paths);
							}
						}

						if (!empty($already_ignored)) {
							// break the current value stored in the options
							$value_options = RSFirewall_Helper::explode($value);

							// merge the two arrays
							$value = array_merge($already_ignored, $value_options);

							// filter the values to remove duplicates
							$value = array_unique($value);
							// rebuild as text value
							$value = implode("\n", $value);
						}
					}
				}

				// remove the disabled attribute from the continents/check_all/country blocking if there is a GeoIp support
				if (in_array($field['name'], array('blocked_continents', 'blocked_countries', 'blocked_countries_checkall')) && $field['field']->attributes()->disabled && (isset($this->geoIp_support) && $this->geoIp_support->works)) {
					$field['field']->attributes()->disabled = '';
				}

				add_settings_field(
					$field['name'],
					RSFirewall_Helper::call_user_func_pro_args(array('RSFirewall_Helper_Fields', 'label_for', $field['field'])),
					RSFirewall_Helper::buildWPCallback(array( 'RSFirewall_Helper_Fields', $field['type'] )),
					$section['name'],
					$section['name'],
					array(
						'section' => $section['name'],
						'field'   => $field['field'],
						'value'   => $value,
					)
				);
			}
			/**
			 * Update only if necessary
			 */
			if ( $must_update ) {
				update_option( $section['name'], $options );
			}

			// Register the settings with the sanitize_callback function
			register_setting(
				$section['name'],
				$section['name'],
				array($this, 'validate')
			);
		}

		// Show the current ip when adding
		add_action( 'admin_notices', array($this, 'admin_notice') );
	}

	protected function delete_file($file) {
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file($file);
		}
		
		$delete = apply_filters( 'wp_delete_file', $file );
		if ( ! empty( $delete ) ) {
			@unlink( $delete );
		}
	}

	protected function strip_ext($file)
	{
		return preg_replace('#\.[^.]*$#', '', $file);
	}
	
	 
	protected function get_extension($filename) {
        $parts 	= explode('.', $filename, 2);
        $ext	= '';
        if (count($parts) == 2) {
            $file = $parts[0];
            $ext  = $parts[1];
            // check for multiple extensions
            if (strpos($file, '.') !== false) {
                $parts = explode('.', $file);
                $last  = end($parts);
                if (strlen($last) <= 4) {
                    $ext = $last.'.'.$ext;
                }
            }
        }

        return strtolower($ext);
    }

	protected function process_import_cfg_file($filename, $tmp_file) {
		global $wpdb;
		// Check extension is .json
		$ext = wp_check_filetype($filename);

		// Not a valid extension
		if ($ext['ext'] != 'json')
		{
			throw new Exception(__('Please upload only .json files!', 'rsfirewall'));
		}

		// Check if the temporary file is readable
		if (!is_readable($tmp_file))
		{
			throw new Exception(sprintf(__('Uploaded file is not readable in server\'s temp directory: %s','rsfirewall'), $tmp_file));
		}

		// Get the contents of the json file and check if it's not empty
		$contents = file_get_contents($tmp_file);
		if (!$contents)
		{
			throw new Exception(__('No contents found in uploaded file.', 'rsfirewall'));
		}

		// Process the json found in the contents
		$contents = json_decode($contents, true);
		if ($contents === null)
		{
			throw new Exception(__('Could not decode JSON data from uploaded file.', 'rsfirewall'));
		}

		// Update paths
		if (isset($contents['root']))
		{
			if (!empty($contents['rsfirewall_system_check']['ignore_files_or_folders']))
			{
				$contents['rsfirewall_system_check']['ignore_files_or_folders'] = str_replace($contents['root'], ABSPATH, $contents['rsfirewall_system_check']['ignore_files_or_folders']);
			}
			if (!empty($contents['rsfirewall_active_scanner']['monitor_files']))
			{
				$contents['rsfirewall_active_scanner']['monitor_files'] = str_replace($contents['root'], ABSPATH, $contents['rsfirewall_active_scanner']['monitor_files']);
			}
		}


		foreach ($contents as $section => $data) {
			if ($section == 'root') {
				continue;
			}
			// We can not use update_option function, there is a problem with the sanitize_option function inside it (don't know why)

			// Check if the option is in the database
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", $section ) );
			$new_value = maybe_serialize($data);
			if ( is_null( $row ) ) {
				$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s)", $section,$new_value, 'yes' ) );
			} else {
				$old_value = $row->option_value;
				if ($old_value == $new_value) {
					$result = true;
				} else {
					// Skip the license code of the configuration if the option is not selected
					if ($section == 'rsfirewall_updates' && (int) $_POST['rsfirewall_import']['upload_license'] == 0) {
						$result = true;
					} // This option (update license code) do not import because the old value the desired value
					else if($section == 'rsfirewall_import'){
						$result = true;
					} else {
						$result = $wpdb->update($wpdb->options, array('option_value' => maybe_serialize($data)), array('option_name' => $section));
					}
				}
			}

			if (!$result) {
				throw new Exception(sprintf(__('Could not import the settings for the section: %s', 'rsfirewall'), $section));
				break;
			}
		}
	}

	public function validate( $section ) {
		global $wpdb;

		$blog_id = is_multisite() ? 'rsf_'.get_current_blog_id() : 'rsf_';
		$blog_id = md5($blog_id);

		if ( ! empty( $section['enable_backend'] ) ) {
			try {
				// verify that a password hasn't been set
				$backend_pass_options     = get_option( 'rsfirewall_backend_password' );
				$stop_checks = false;

				if (isset($backend_pass_options['type_password']) && !empty($backend_pass_options['type_password']) && (!strlen($section['type_password']) || !strlen($section['confirm_password']))) {
					$stop_checks = true;
				}

				if(!$stop_checks) {
					if (!strlen($section['type_password'])) {
						throw new Exception(esc_html__('Please provide a password.', 'rsfirewall'));
					}

					if (strlen($section['type_password']) < 6) {
						throw new Exception(esc_html__('Please provide a password containing at least 6 characters.', 'rsfirewall'));
					}

					if ($section['type_password'] !== $section['confirm_password']) {
						throw new Exception(esc_html__('Passwords do not match.', 'rsfirewall'));
					}

					// save them encrypted
					$encrypt = md5($section['type_password']);
					$section['type_password'] = $section['confirm_password'] = $encrypt;
				} else {
					// keep the current password already saved
					$section['type_password'] 	 = $backend_pass_options['type_password'];
					$section['confirm_password'] = $backend_pass_options['confirm_password'];
				}

			} catch ( Exception $e ) {
				$section['enable_backend'] = 0;
				add_settings_error( 'rsfirewall', 'password', $e->getMessage(), 'error' );
			}
		} else if (isset($section['enable_backend']) && $section['enable_backend'] === '0' && isset($_COOKIE['rsf_backend_login'.$blog_id])) {
			$backend_pass = RSFirewall_Core_Backend_Password::get_instance();
			$backend_pass->delete_login_cookie();
		}
		
		// this is a clean of the logout link independent of the enable backend password option
		if (isset($section['logout_redirect']) && strlen($section['logout_redirect']) > 0) {
			if(!wp_http_validate_url($section['logout_redirect'])) {
				$section['logout_redirect'] = '';
				add_settings_error( 'rsfirewall', 'password', sprintf(esc_html__('Please provide a full schema link for the logout redirect! (ex: %s)', 'rsfirewall'), get_site_url()), 'error' );
			}
		}
		
		if (isset($section['enable_admin_slug']) && !empty($section['enable_admin_slug']) && isset($section['admin_slug_text'])) {
			// clean a bit the slug
			$section['admin_slug_text'] = trim($section['admin_slug_text']);
			if (strlen($section['admin_slug_text']) == 0) {
				$section['enable_admin_slug'] = 0;
				add_settings_error( 'rsfirewall', 'slug', esc_html__('Please provide a text for the slug to enable it!'), 'error' );
			}
		}

		// Handle the hardening options
		if (isset($section['harden_uploads'])) {

			// Find all the hardening options and values
			$hardening_options = array();
			$hardening_defaults = get_option('rsfirewall_hardening');
			foreach ($section as $option => $option_value ) {
				if (strpos($option, 'harden_') == 0) {
					$hardening_options[] = $option;
				}
			}

			if (!empty($hardening_options)) {
				try {
					foreach ($hardening_options as $option) {
						if (method_exists($this, $option)) {
							call_user_func_array(array($this, $option), array(&$section[$option]));
						} else {
							// restore to the last saved value
							$section[$option] = isset($hardening_defaults[$option]) ? $hardening_defaults[$option] : 0;
						}
					}
				} catch (Exception $e) {
					$trace = $e->getTrace();
					$stuck_at = $trace[0]['function'];

					$change = false;
					foreach ($hardening_options as $option) {
						if ($option == $stuck_at) {
							$change = true;
						}

						if ($change) {
							$section[$option] = isset($hardening_defaults[$option]) ? $hardening_defaults[$option] : 0;
						}
					}
					add_settings_error('rsfirewall', 'hardening', $e->getMessage(), 'error');
				}
			}
		}

		if (isset($section['monitor_files'])) {
			$table = $wpdb->prefix . 'rsfirewall_hashes';

			$already_monitored = RSFirewall_Config::get( 'monitor_files' );
			if ($already_monitored != $section['monitor_files']) {
				/** Cleanup the table */
				$wpdb->delete( $table, array( 'type' => 'protect' ) );

				$values = RSFirewall_Helper::explode($section['monitor_files']);
				// Remove Duplicates
				$values = array_unique($values);

				foreach ($values as $i => $value)
				{
					$value = trim($value);
					if (!file_exists($value) || !is_readable($value))
					{
						unset($values[$i]);
						continue;
					}

					$wpdb->insert(
						$table,
						array(
							'file' => $value,
							'hash' => md5_file($value),
							'type' => 'protect',
							'flag' => '',
							'date' => current_time('mysql')
						)
					);
				}

				// Override the section value so that is saved without the duplicates
				$section['monitor_files'] = !empty($values) ? implode("\n", $values) : '';
			}
		}

		// When we disable the creation of new admin users, we need to remember which are the default ones
		if (!empty($section['disable_admin_creation'])) {
			$admin_users = RSFirewall_Helper_Users::getAdminUsers();

			update_option('rsfirewall_admin_users', $admin_users);
		}


		// Parse all the uploaded files
		try {
			$this->handle_uploads();
		} catch ( Exception $e ) {
			add_settings_error( 'rsfirewall', 'country_blocking', $e->getMessage(), 'error' );
		}

		if (get_class($this) != 'RSFirewall_Model_ConfigurationPro') {
			return apply_filters( 'validate_inputs', $section );
		} else {
			return $section;
		}
	}

	/**
	 * Callback function to harden uploads directory
	 *
	 * @param $section_value
	 * @throws Exception in case something goes wrong
	 */
	protected function harden_uploads($section_value) {
		$upload_path = RSFirewall_Helper::get_uploads_path();

		if (!empty($section_value)) {
			RSFirewall_Helper_Harden::harden_directory($upload_path);
		} else {
			RSFirewall_Helper_Harden::unharden_directory($upload_path);
		}
	}

	/**
	 * Callback function to harden wp-content directory
	 *
	 * @param $section_value
	 * @throws Exception in case something goes wrong
	 */
	protected function harden_wp_content($section_value) {

		if (!empty($section_value)) {
			RSFirewall_Helper_Harden::harden_directory(WP_CONTENT_DIR);
		} else {
			RSFirewall_Helper_Harden::unharden_directory(WP_CONTENT_DIR);
		}
	}

	/**
	 * Callback function to harden wp-includes directory
	 *
	 * @param $section_value
	 * @throws Exception in case something goes wrong
	 */
	protected function harden_wp_includes($section_value) {

		if (!empty($section_value)) {
			RSFirewall_Helper_Harden::harden_directory(ABSPATH . '/wp-includes');
		} else {
			RSFirewall_Helper_Harden::unharden_directory(ABSPATH . '/wp-includes');
		}
	}

	/**
	 * Callback function to enable/disable file editors
	 *
	 * @param $section_value
	 * @throws Exception in case something goes wrong
	 */
	protected function harden_editors($section_value) {
		$is_disabled = (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT);

		// if the editors are already disabled / enabled (in case of unharden) then skip this process
		if (($section_value == 1 && $is_disabled) || ($section_value == 0 && !$is_disabled)) {
			return;
		}

		$config_file = RSFirewall_Helper::get_config_path();

		if (!$config_file) {
			throw new Exception(esc_html__('The configuration file could not be located!', 'rsfirewall'));
		}

		if (!is_writable($config_file)) {
			throw new Exception(esc_html__('The configuration file is not writable!', 'rsfirewall'));
		}

		if (!is_readable($config_file)) {
			throw new Exception(esc_html__('The configuration file is not readable!', 'rsfirewall'));
		}

		$file_content 	= (string) file_get_contents($config_file);
		$lines 			= explode("\n", $file_content);
		$newlines 		= array();

		$has_constant  = (strpos($file_content, 'DISALLOW_FILE_EDIT') !== false);

		foreach ($lines as $line) {
			if ($section_value == 1) {
				/** if the constant is not defined add it */
				if (strpos($line, 'DB_COLLATE') !== false && !$has_constant) {
					$newlines[] = $line; // keep the DB_COLLATE line
					$newlines[] = '';
					$newlines[] = '/** Disable / Enable file edit using plugins / themes editors. */';
					$newlines[] = "define('DISALLOW_FILE_EDIT', true);";
				} else if (strpos($line, 'DISALLOW_FILE_EDIT') !== false){
					/** if the constant is defined modify it */
					$newlines[] = "define('DISALLOW_FILE_EDIT', true);";
				} else {
					$newlines[] = $line; // keep everything else
				}
			} else if (empty($section_value)) {
				if (strpos($line, 'DISALLOW_FILE_EDIT') !== false) {
					$newlines[] = "define('DISALLOW_FILE_EDIT', false);";
				} else {
					$newlines[] = $line; // keep everything else
				}
			}
		}

		// Change the file only if the content is built
		if (!empty($newlines)) {
			$file_content = implode("\n", $newlines);
			file_put_contents($config_file, $file_content, LOCK_EX);
		}
	}

	protected function handle_uploads() {
		//Redefine files in a more beautiful way
		$handler = RSFirewall_Helper_Files::get_instance();

		// handle json configuration file
		$config_file = $handler->get('rsfirewall_import');

		// if no country blocking files / configuration files are loaded then skip
		if (is_null($config_file)) {
			return false;
		}

		// Process the configuration file
		if (!empty($config_file) && strlen($config_file[0]['tmp_name'])) {
			$config_file = $config_file[0];

			// handle the file upload errors
			$this->handle_upload_file_errors($config_file);

			// Parse and check the uploaded json file
			$this->process_import_cfg_file($config_file['name'], $config_file['tmp_name']);

		}

		return true;
	}

	protected function handle_upload_file_errors($file = null) {
		if (is_null($file)) {
			return false;
		}

		if ($file['error']) {
			if ($file['error'] == UPLOAD_ERR_INI_SIZE)
			{
				throw new Exception( esc_html__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'rsfirewall' ) );
			}
			elseif ($file['error'] == UPLOAD_ERR_FORM_SIZE)
			{
				throw new Exception( esc_html__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', 'rsfirewall' ) );
			}
			elseif ($file['error'] == UPLOAD_ERR_PARTIAL)
			{
				throw new Exception( esc_html__( 'The uploaded file was only partially uploaded.', 'rsfirewall' ) );
			}
			elseif ($file['error'] == UPLOAD_ERR_NO_TMP_DIR)
			{
				throw new Exception( esc_html__( 'Missing a temporary folder.', 'rsfirewall' ) );
			}
			elseif ($file['error'] == UPLOAD_ERR_CANT_WRITE)
			{
				throw new Exception( esc_html__( 'Failed to write file to disk.', 'rsfirewall' ) );
			}
			elseif ($file['error'] == UPLOAD_ERR_EXTENSION)
			{
				throw new Exception( esc_html__( 'A PHP extension stopped the file upload.', 'rsfirewall' ) );
			}
		}

		return true;
	}

	public function get_administrator_users() {
		$results = array();

		if ( $users = get_users( array( 'role' => 'administrator', 'orderby' => 'nicename' ) ) ) {
			foreach ( $users as $user ) {
				$results[] = (object) array(
					'label'   => $user->display_name,
					'value'   => $user->ID,
					'checked' => false
				);
			}
		}

		return $results;
	}

	public function get_users_roles() {
		global $wp_roles;
		$all_roles = $wp_roles->roles;

		$results = array();

		foreach ($all_roles as $role => $details) {
			$results[] = (object) array(
				'label'   => translate_user_role($details['name']),
				'value'   => esc_attr($role),
				'checked' => false
			);
		}
		return $results;
	}

	/**
	 * Callback function for the country blocking section and 2FA
	 */
	public function only_pro_version() {
		$html = '<div id="country_block">';

		$html .= '<div class="alert alert-info">';
		$html .= '	<h4>' . __('This feature is not available in the free version of RSFirewall!', 'rsfirewall') . '</h4>';
		$html .= '	<p>' . esc_attr__('If you wish to use this feature please consider purchasing the full version of RSFirewall!', 'rsfirewall') . '</p>';
		$html .= '	<p><a href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html" class="button-primary">' . __('Purchase the full version of RSFirewall!', 'rsfirewall') . '</a></p>';
		$html .= '</div>';

		$html .= '</div>';

		echo $html;
	}

	public function export_configuration() {
		$values = array();
		foreach ($this->form->get_sections() as $section) {
			if (!isset($values[$section])) {
				$values[$section] = array();
			}

			$options = get_option($section);
			if (is_array($options))
			{
				$values[$section] = array_merge($values[$section], $options);
			}
		}

		$values['root'] = ABSPATH;

		return json_encode($values);
	}

	public function remove_whitelisted($files = array()) {
		foreach ($files as $file) {
			if (!RSFirewall_Helper_Harden::remove_file_from_whitelist($file['file'], ABSPATH.'/'.$file['folder'])) {
				throw new Exception(sprintf(__('The .htaccess from %s could not be written!','rsfirewall'), $file['folder']));
			}
		}

		return true;
	}

	public function add_whitelisted($file = '', $folder = '') {
		/**
		 * Filename checks
		 */

		// check the file to not be empty
		if (trim($file) == '') {
			throw new Exception(esc_html__('Please enter a filename!','rsfirewall'));
		}

		// do not include a full path (ex: folder1/folder2/file.php)
		if (strpos($file, '/') !== false) {
			throw new Exception(esc_html__('Please specify only the filename, not the full path!','rsfirewall'));
		}

		// alphanumeric and ".", "_", '-' characters accepted
		preg_match_all('#([^a-zA-Z\d-\.\-])+#', $file, $matches);

		if (!empty($matches[0])) {
			throw new Exception(esc_html__('The filename must contain only alphanumeric and ".", "_", "-" as strings!','rsfirewall'));
		}

		// check if file extension is specified
		$file_parts = explode('.', $file);
		if (count($file_parts) <= 1) {
			throw new Exception(esc_html__('No extension has been provided for the filename!','rsfirewall'));
		}

		// check if the extension is php
		$extension = array_pop($file_parts);
		if ($extension != 'php') {
			throw new Exception(esc_html__('Only PHP files are accepted!','rsfirewall'));
		}

		/**
		 * Folder checks
		 */

		$accepted_folders = array(
			'uploads' 		=> WP_CONTENT_DIR . '/uploads',
			'wp-content' 	=> WP_CONTENT_DIR,
			'wp-includes' 	=> ABSPATH . '/wp-includes',
		);

		// in case somehow is empty

		if (trim($folder) == '') {
			throw new Exception(esc_html__('The folder is not selected!','rsfirewall'));
		}

		if (!isset($accepted_folders[$folder])) {
			throw new Exception(esc_html__('The folder must be one of the select options!','rsfirewall'));
		}

		if (!RSFirewall_Helper_Harden::add_file_to_whitelist($file, $accepted_folders[$folder])) {
			throw new Exception(sprintf(__('The .htaccess from %s could not be written!','rsfirewall'), $file['folder']));
		}
	}

	/**
	 * Function to display notices
	 */
	public function admin_notice() {
		$screen_id    = RSFirewall_Helper::get_current_screen();

		if ($screen_id =='rsfirewall_page_rsfirewall_configuration') {
			?>
			<div class="notice notice-warning is-dismissible">
				<p><?php echo wp_kses_post(__('Your IP address is currently detected as '.RSFirewall_Helper::get_ip().'.', 'rsfirewall')); ?></p>
			</div>
			<?php
		}
	}
}