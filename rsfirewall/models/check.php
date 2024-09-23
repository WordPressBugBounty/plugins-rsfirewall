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
 * Class RSFirewall_Model_Check
 *
 */
class RSFirewall_Model_Check extends RSFirewall_Model {
	const DS = DIRECTORY_SEPARATOR;

	/**
	 * @var bool
	 */
	protected $log = false;

	protected $calling_class = '';

	public function __construct() {
		parent::__construct();

		// Calling class
		$this->calling_class = get_class($this);

		// Enable logging
		if ( RSFirewall_Config::get( 'enable_logging' ) && is_writable( RSFIREWALL_BASE ) ) {
			$this->log = true;
		}
	}

	/**
	 * @param string $type 'all' or the name of the steps set.
	 *
	 * @return array        Array collection of steps.
	 */
	public function get_steps( $type = 'all' ) {
		$server_check = array(
			'check_allow_url_include' => array(
				'name'        => 'check_allow_url_include',
				'importance'  => 'medium',
				'description' => esc_html__( 'PHP Directive: allow_url_include', 'rsfirewall' )
			),
			'check_open_basedir'      => array(
				'name'        => 'check_open_basedir',
				'importance'  => 'medium',
				'description' => esc_html__( 'PHP Directive: open_basedir', 'rsfirewall' )
			),
			'check_disable_functions' => array(
				'name'        => 'check_disable_functions',
				'importance'  => 'medium',
				'description' => esc_html__( 'PHP Directive: disable_functions', 'rsfirewall' )
			),
		);

		if ( version_compare( phpversion(), '5.4.0', '<' ) ) {
			$server_check['check_safe_mode']        = array(
				'name'        => 'check_safe_mode',
				'importance'  => 'medium',
				'description' => esc_html__( 'PHP Directive: safe_mode', 'rsfirewall' )
			);
			$server_check['check_register_globals'] = array(
				'name'        => 'check_register_globals',
				'importance'  => 'medium',
				'description' => esc_html__( 'PHP Directive: register_globals', 'rsfirewall' )
			);
		};

		$system_check = array(
			'wp_version_check'           => array(
				'name'        => 'wp_version_check',
				'importance'  => 'high',
				'description' => esc_html__( 'Checking if you have the latest WordPress version installed', 'rsfirewall' )
			),
			'rsfirewall_version_check'   => array(
				'name'        => 'rsfirewall_version_check',
				'importance'  => 'high',
				'description' => esc_html__( 'Checking if you have the latest RSFirewall! version', 'rsfirewall' )
			),
			'weak_database_pswrd_check'  => array(
				'name'        => 'weak_database_pswrd_check',
				'importance'  => 'high',
				'description' => esc_html__( 'Checking if you have a weak database password', 'rsfirewall' )
			),
			'admin_username_check'       => array(
				'name'        => 'admin_username_check',
				'importance'  => 'high',
				'description' => esc_html__( 'Checking administrator users for common username.', 'rsfirewall' )
			),
			'admin_unwanted_username_check'       => array(
				'name'        => 'admin_unwanted_username_check',
				'importance'  => 'high',
				'description' => esc_html__( 'Checking administrator users for compromised accounts.', 'rsfirewall' )
			),
			'sef_enabled_check'          => array(
				'name'        => 'sef_enabled_check',
				'importance'  => 'low',
				'description' => esc_html__( 'Checking if you have Search Engine Friendly URLs enabled', 'rsfirewall' )
			),
			'revisions_check'            => array(
				'name'        => 'revisions_check',
				'importance'  => 'low',
				'description' => esc_html__( 'Check your database for post revisions', 'rsfirewall' )
			)
		);

		if ( $type == 'all' ) {
			return array_merge( $system_check, $server_check );
		}

		switch($type) {
			case 'all':
				return array_merge( $system_check, $server_check );
			break;

			case 'system_check':
				return $system_check;
			break;

			case 'server_check':
				return $server_check;
			break;

			default:
				return array();
			break;
		}
	}

	/**
	 * @param      $data
	 * @param bool $error
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	protected function add_log_entry( $data, $error = false ) {
		if ( ! $this->log ) {
			return false;
		}

		static $path;
		if ( ! $path ) {
			$path = RSFIREWALL_BASE . '/logs/rsfirewall.php';

			if (!file_exists($path) || !filesize($path)) {
				file_put_contents( $path, "<?php die; ?>\n" );
			}
		}
		$prepend = gmdate( 'Y-m-d H:i:s ' );
		if ( $error ) {
			$prepend .= '** ERROR ** ';
		}

		file_put_contents( $path, $prepend . $data . "\n", FILE_APPEND );

		return true;
	}

	/**
	 * Checks your current WordPress version.
	 *
	 */
	public function wp_version_check($skip_log = false) {
		if ($skip_log) {
			$this->add_log_entry('System Check started.');
		}

		global $wp_version;

		/* Force re-check */
		wp_version_check( array(), true );

		$updates = get_site_transient( 'update_core' );
		if ( ! empty( $updates ) && ! empty( $updates->updates ) ) {
			return array(
				'current_version' => $wp_version,
				'latest_version'  => $updates->updates[0]->current,
				'api_response'    => $updates->updates[0]->response
			);
		}

		return false;
	}

	/**
	 * Checks your current RSFirewall! version.
	 */
	public function rsfirewall_version_check() {
		$latest 		 = $this->get_latest_firewall();
		$current_version = RSFirewall_Version::get_instance();

		return array(
			'current_version' => $current_version->version,
			'latest_version'  => $latest->new_version.(isset($latest->ispro) ? esc_html__(' - Pro Version') : ''),
			'api_response'    => isset($latest->ispro) ? -1 : version_compare( $current_version->version, $latest->new_version )
		);
	}


	public function get_latest_firewall() {
		$code = RSFirewall_Config::get('code');
		if ( $code && strlen( $code ) == 20) {
			require_once RSFIREWALL_BASE . 'libraries/autoupdate.php';

			$autoupdate = new RSFirewall_Autoupdate (array(), false);
			$version = $autoupdate->get_url('version');
			if ($version) {
				$version->ispro = true;
			}
		} else {
			$version_class = RSFirewall_Version::get_instance();
			$version = $version_class->get_latest_version();
		}

		if ($version) {
			return $version;
		} else {
			throw new Exception(esc_html__( 'Could not connect to the server and retrieve the version!', 'rsfirewall' ));
		}
	}

	/**
	 * Checks your database password
	 */
	public function weak_database_pswrd_check() {
		$errors = array();
		if ( DB_PASSWORD == '' ) {
			$errors[] = esc_html__( 'You do not have any password set for your database.', 'rsfirewall' );
		}

		if ( strlen( DB_PASSWORD ) < 8 ) {
			$errors[] = esc_html__( 'Your password is too short. We recommend at least 8 characters.', 'rsfirewall' );
		}

		if ( ! preg_match( "#[0-9]+#", ( DB_PASSWORD ) ) ) {
			$errors[] = esc_html__( 'Your password does not include numbers, we recommend using at least one.', 'rsfirewall' );
		}

		if ( ! preg_match( "#[a-zA-Z]+#", ( DB_PASSWORD ) ) ) {
			$errors[] = esc_html__( 'Your password does not include any letters, we recommend using at least one.', 'rsfirewall' );
		}

		$return = array(
			'weak_password' => false,
			'message'       => esc_html__( 'Your database password is ok.', 'rsfirewall' )
		);

		if ( count( $errors ) > 0 ) {
			$return['weak_password'] = true;
			$return['message']       = sprintf( esc_html__( 'Your database password is too simple - "%s".', 'rsfirewall' ), DB_PASSWORD );
			$return['details']       = implode( '<br />', $errors );
		}

		return $return;
	}

	/**
	 * Checks if you have a weak username
	 *
	 * @todo Use RSFirewall_Controller_Configuration->get_administrator_users() - maybe add some params there for
	 *       searching?
	 */
	public function admin_username_check() {
		$args = array(
			'role' => array( 'administrator' )
		);

		$common   = array(
			'admin',
			'administrator',
			'wp-admin'
		);
		$users    = get_users( $args );
		$response = array(
			'message' => esc_html__( 'No common username found', 'rsfirewall' ),
			'users'   => array()
		);

		foreach ( $users as $user ) {
			if ( in_array( $user->user_login, $common ) ) {
				$response['message'] = esc_html__( 'Common username found in your database.', 'rsfirewall' );
				$response['users'][] = array(
					'id'           => $user->ID,
					'email'        => $user->user_email,
					'display_name' => $user->display_name,
					'details'      => sprintf( wp_kses_post( __('<p class="rsf_check_user">The username: "%s" with email address: "%s" has a too common username. Please consider changing it. %s %s</p>', 'rsfirewall') ), $user->user_login, $user->user_email, '<input type="text" class="rsfirewall-username-change" data-value="'.$user->user_login.'" value="'.$user->user_login.'"/>', '<button class="rsfirewall-fix-action" onclick="RSFirewall.System.Fix.changeAdminUsername(this)">'. esc_html__( 'Change', 'rsfirewall' ) . '</button>')
				);
			}
		}

		return $response;
	}

	public function admin_unwanted_username_check() {
		$args = array(
			'role' => array( 'administrator' )
		);

		$users    = get_users( $args );
		$response = array(
			'message' => esc_html__( 'No dangerous username found.', 'rsfirewall' ),
			'users'   => array()
		);

		// unwanted admins
		$unwanted = RSFirewall_Core_Vulnerabilities::$unwanted_admins;

		foreach ( $users as $user ) {
			foreach ($unwanted as $u_admin) {
				if (stripos($user->user_login, $u_admin) !== false) {
					$response['message'] = esc_html__( 'Dangerous username found in your database.', 'rsfirewall' );
					$response['users'][] = array(
						'id'           => $user->ID,
						'email'        => $user->user_email,
						'display_name' => $user->display_name,
						'details'      => sprintf( wp_kses_post( __('<p class="rsf_check_user">The username: "%s" with email address: "%s" contains "%s" in it\'s name and it is reported as dangerous. Please consider changing it. %s %s. Or delete it %s</p>', 'rsfirewall') ), $user->user_login, $user->user_email,$u_admin, '<input type="text" class="rsfirewall-username-change" data-value="'.$user->user_login.'" value="'.$user->user_login.'"/>', '<button class="rsfirewall-fix-action" onclick="RSFirewall.System.Fix.changeAdminUsername(this)">'. esc_html__( 'Change', 'rsfirewall' ) . '</button>', '<button class="rsfirewall-fix-action" data-iduser="'.$user->ID.'" onclick="RSFirewall.System.Fix.deleteAdminUser(this)">'. esc_html__( 'Delete', 'rsfirewall' ) . '</button>')
					);

					break;
				}
			}
		}

		return $response;
	}

	/**
	 * Checks if you have SEF links enabled
	 */
	public function sef_enabled_check() {
		$permalink = get_option( 'permalink_structure' );

		$result = array(
			'sef_enabled' => true,
			'message'     => esc_html__( 'Search Engine Friendly URLs enabled.', 'rsfirewall' )
		);

		if ( empty( $permalink ) ) {
			$result['sef_enabled'] = false;
			$result['message']     = esc_html__( 'Search Engine Friendly URLs Disabled.', 'rsfirewall' );
			$result['details']     = esc_html__( 'WordPress offers you the ability to create a custom URL structure for your permalinks and archives. Custom URL structures can improve the aesthetics, usability, and forward-compatibility of your links. You can change them <a href="' . admin_url( 'options-permalink.php' ) . '/wp-admin/options-permalink.php">here</a>.', 'rsfirewall' );
		}

		return $result;
	}
	
	/**
	 * Checks for the post/page revisions
	 */
	public function revisions_check() {
		global $wpdb;
		$query   = 'SELECT ID FROM ' . $wpdb->prefix . 'posts WHERE post_type = "revision"';
		$results = $wpdb->get_results( $query );
		if ( $results == NULL ) {
			return array(
				'result'  => true,
				'message' => esc_html__( 'Your WordPress database does not contain any revisions.', 'rsfirewall' ),
			);
		}

		return array(
			'result'  => false,
			'message' => sprintf( esc_html__( 'Your WordPress database contains %d revisions.', 'rsfirewall' ), count( $results ) ),
			'details' => sprintf( wp_kses_post( __('You can opt to clear the revisions by clicking this button: %s', 'rsfirewall') ), '<button class="rsfirewall-fix-action" onclick="RSFirewall.System.Fix.deleteRevisions(this)" id="rsfirewall_revisions_delete_trigger">'. esc_html__( 'Clear', 'rsfirewall' ) . '</button>')
		);
	}

	/**
	 * Checks the allow_url_include directive from php.ini
	 */
	public function check_allow_url_include() {
		return $this->compareINI( 'allow_url_include' );
	}

	/**
	 * Checks the disable_functions directives from PHP ini
	 */
	public function check_disable_functions() {
		$disable_functions = $this->getINI( 'disable_functions' );

		$recommended_functions = array(
			'system',
			'shell_exec',
			'passthru',
			'exec',
			'phpinfo',
			'popen',
			'proc_open'
		);

		$used_functions = array();

		if ( $disable_functions ) {
			$disable_functions = explode( ',', $disable_functions );
			foreach ( $disable_functions as $disable_function ) {
				$disable_function = strtolower(trim($disable_function));
				if ( in_array( $disable_function, $recommended_functions ) ) {
					$used_functions[] = $disable_function;
				}
			}
			
			$used_functions = array_unique($used_functions);

			if ( $used_functions && count( $used_functions ) == count( $recommended_functions ) ) {
				$return['result']  = true;
				$return['message'] = esc_html__( 'disable_functions is used.', 'rsfirewall' );
			} else {
				$unused_functions  = array_diff( $recommended_functions, $used_functions );
				$return['result']  = false;
				$return['message'] = sprintf( esc_html__( 'disable_functions is missing the following: %s', 'rsfirewall' ), implode( ', ', $unused_functions ) );
			}
		} else {
			$return['result']  = false;
			$return['message'] = sprintf( esc_html__( 'disable_functions is not used. Please add the following: %s', 'rsfirewall' ), implode( ', ', $recommended_functions ) );
		}

		return $return;
	}

	/**
	 * Checks the safe_mode directive from PHP ini
	 */
	public function check_safe_mode() {
		return $this->compareINI( 'safe_mode' );
	}

	/**
	 * Checks the safe_mode directive from PHP ini
	 */
	public function check_register_globals() {
		return $this->compareINI( 'register_globals' );
	}

	/**
	 * Checks the open_basedir directive from PHP ini
	 */
	public function check_open_basedir() {
		return $this->compareINI( 'open_basedir', '' );
	}

	public function compareINI( $name, $against = '1' ) {
		return $this->getINI( $name ) == $against;
	}

	public function getINI( $name ) {
		return ini_get( $name );
	}


	protected function is_writable($dir) {
		if (function_exists('wp_is_writable')) {
			return wp_is_writable($dir);
		}

		return is_writable($dir);
	}

	/**
	 * @return int|string
	 */
	protected function get_memory_limit_in_bytes() {
		$memory_limit = $this->getINI( 'memory_limit' );
		switch ( substr( $memory_limit, - 1 ) ) {
			case 'K':
				$memory_limit = (int) $memory_limit * 1024;
				break;

			case 'M':
				$memory_limit = (int) $memory_limit * 1024 * 1024;
				break;

			case 'G':
				$memory_limit = (int) $memory_limit * 1024 * 1024 * 1024;
				break;
		}

		return $memory_limit;
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

	
	/**
	 * Removes any comments from the php files
	 */
	protected function clean_content($content) {
		// Verify that the token_get_all function exists, if not than we cannot clean the content
		if (! function_exists('token_get_all')) {
			 return $content;
		}
		// define the necessary constants
		static $defined_constants;

		if (is_null($defined_constants)) {
			if (!defined('T_ML_COMMENT')) {
				define('T_ML_COMMENT', T_COMMENT);
			} else {
				define('T_DOC_COMMENT', T_ML_COMMENT);
			}

			$defined_constants = true;
		}

		$tokens = token_get_all($content);
		$ret = "";
		foreach ($tokens as $token) {
			if (is_string($token)) {
				$ret.= $token;
			} else {
				list($id, $text) = $token;

				switch ($id) {
					case T_COMMENT:
					case T_ML_COMMENT:
					case T_DOC_COMMENT:
						break;

					default:
						$ret.= $text;
						break;
				}
			}
		}
		return trim(str_replace(array('<?','?>'),array('',''),$ret));
	}

	/**
	 * Check for malware patterns and if the files are suppose to be where they are
	 */
	public function signatures_check( $file ) {
		static $signatures;
		if ( ! is_array( $signatures ) ) {
			$signatures = $this->load_signatures();
		}

		if ( empty( $signatures ) ) {
			throw new Exception ( esc_html__( 'There were no signatures found in your database', 'rsfirewall' ) );
		}

		$ext = $this->get_extension($file);

		if ($ext == 'php') {
			if (!is_readable($file)) {
				$this->add_log_entry("[checkSignatures] Error reading '$file'.", true);
				return false;
			}


			$bytes = filesize($file);
			// More than 1 Megabyte
			if ($bytes >= 1048576) {
				$this->add_log_entry("[checkSignatures] File '$file' is {$this->readable_filesize($bytes)}.", true);
				return false;
			}

			$this->add_log_entry("[checkSignatures] Opening '$file' ({$this->readable_filesize($bytes)}) for reading.");

			$contents = file_get_contents($file);
			$md5 = md5($contents);
		}

		$basename = basename( $file );
		$dirname  = dirname( $file );
		$ds       = $this->get_ds();

		foreach ( $signatures as $signature ) {
			if (strpos($signature->type, 'regex') === 0 && $ext == 'php')
			{
				$flags = str_replace( 'regex', '', $signature->type );
				// let's use a clean content (without comments) - so that false positives may reduce
				$clean_contents = $this->clean_content($contents);
				if ( preg_match( '#' . $signature->signature . '#' . $flags, $clean_contents, $match ) ) {
					$this->add_log_entry( "[checkSignatures] Malware found ({$signature->reason})" );

					return array( 'match' => esc_html( $match[0] ), 'reason' => $signature->reason );
				}
			}
			elseif ($signature->type == 'filename')
			{
				if ( preg_match( '#' . $signature->signature . '#i', $basename, $match ) ) {
					$this->add_log_entry( "[checkSignatures] Malware found ({$signature->reason})" );

					return array( 'match' => esc_html( $match[0] ), 'reason' => $signature->reason );
				}
			}
			elseif ($signature->type == 'md5' && $ext == 'php')
			{
				if ($signature->signature === $md5) {

					$this->add_log_entry( "[checkSignatures] Malware found ({$signature->reason})" );

					return array( 'match' => $signature->signature, 'reason' => $signature->reason );
				}
			}
		}

		if ($ext == 'php') {
			// Checking for base64 inside index.php
			if (in_array($basename, array('index.php'))) {
				if (preg_match('#base64\_decode\((.*?)\)#is', $contents, $match)) {

					$this->add_log_entry("[checkSignatures] Malware found (Base64 encoding found in files)");

					return array('match' => $match[0], 'reason' => esc_html__('Base64 encoding found in files', 'rsfirewall'));
				}
			}

			// Check if there are php files in root
			if ( $dirname == RSFIREWALL_SITE ) {
				$ignored_files = array(
					'index.php',
					'wp-activate.php',
					'wp-blog-header.php',
					'wp-comments-post.php',
					'wp-config.php',
					'wp-config-sample.php',
					'wp-cron.php',
					'wp-links-opml.php',
					'wp-load.php',
					'wp-login.php',
					'wp-mail.php',
					'wp-settings.php',
					'wp-signup.php',
					'wp-trackback.php',
					'xmlrpc.php'
				);
				if ( ! in_array( $basename, $ignored_files ) ) {

					$this->add_log_entry( "[checkSignatures] Malware found (Suspicious file found in root directory of your WordPress installation)" );

					return array(
						'match'  => $basename,
						'reason' => esc_html__( 'Suspicious file found in root directory of your WordPress installation', 'rsfirewall' )
					);
				}
			}

			// Check if there are php files in the /uploads folder
			if ( strpos( $dirname, RSFIREWALL_SITE . $ds . 'wp-content' . $ds . 'uploads' ) === 0 ) {

				$this->add_log_entry( "[checkSignatures] Malware found (Suspicious file found in '.$dirname . RSFIREWALL_SITE . $ds . 'wp-content' . $ds . 'uploads')" );

				return array(
					'match'  => $basename,
					'reason' => sprintf( esc_html__( 'Suspicious file found in %s', 'rsfirewall' ), $dirname . RSFIREWALL_SITE . $ds . 'wp-content' . $ds . 'uploads' )
				);
			}

			// Check if there are php files in the wp-includes/images folder
			if ( strpos( $dirname, RSFIREWALL_SITE . $ds . 'wp-includes' . $ds . 'images' ) === 0 ) {

				$this->add_log_entry( "[checkSignatures] Malware found (Suspicious file found in '.$dirname . RSFIREWALL_SITE . $ds . 'wp-includes' . $ds . 'images')" );

				return array(
					'match'  => $basename,
					'reason' => sprintf( esc_html__( 'Suspicious file found in %s', 'rsfirewall' ), $dirname . RSFIREWALL_SITE . $ds . 'wp-includes' . $ds . 'images' )
				);
			}

			// Check if there are php files in the wp-admin/images folder
			if ( strpos( $dirname, RSFIREWALL_SITE . $ds . 'wp-admin' . $ds . 'images' ) === 0 ) {

				$this->add_log_entry( "[checkSignatures] Malware found (Suspicious file found in '.$dirname . RSFIREWALL_SITE . $ds . 'wp-admin' . $ds . 'images')" );

				return array(
					'match'  => $basename,
					'reason' => sprintf( esc_html__( 'Suspicious file found in %s', 'rsfirewall' ), $dirname . RSFIREWALL_SITE . $ds . 'wp-admin' . $ds . 'images' )
				);
			}

			$folders = array(
				'wp-content',
				'wp-content'.$ds.'themes',
				'wp-includes'.$ds.'css',
				'wp-admin'.$ds.'js',

			);

			$folders_exceptions = array(
				'wp-content' => 'index.php',
				'wp-content'.$ds.'themes' => 'index.php'
			);

			foreach ($folders as $folder) {
				if ($dirname == RSFIREWALL_SITE.$ds.$folder) {

					if (isset($folders_exceptions[$folder]) && $basename == $folders_exceptions[$folder]) {
						continue;
					}

					$this->add_log_entry( "[checkSignatures] Malware found (Suspicious file found in 'RSFIREWALL_SITE.$ds.$folder')" );

					return array(
						'match'  => $basename,
						'reason' => sprintf( esc_html__( 'Suspicious file found in %s', 'rsfirewall' ), RSFIREWALL_SITE.$ds.$folder )
					);
				}
			}
		} else {
			if ($basename[0] == ' ')
			{
				return array('match' => $basename, 'reason' => sprintf(esc_html__('Suspicious filename found in %s. Files with spaces in the front of the filename are usually placed by attackers to avoid being detected.', 'rsfirewall'), $dirname));
			}

			$ignoredDotFiles = $this->get_dot_files();

			if ($basename[0] == '.' && !in_array(strtolower($basename), $ignoredDotFiles))
			{
				return array('match' => $basename, 'reason' => sprintf(esc_html__('Suspicious filename found in %s. Files with a dot in front of them are usually hidden by the operating system.', 'rsfirewall'), $dirname));
			}
		}


		if ($this->calling_class == 'RSFirewall_Model_Check') {
			$this->add_log_entry("[checkSignatures] File $basename appears to be clean. Moving on to next...");
		}

		return false;
	}

	/**
	 * get the dot files that are specified by the user
	 */
	protected function get_dot_files()
	{
		return array(
			'.htaccess',
			'.htpasswd',
			'.htusers',
			'.htgroups',
			'.gitignore',
			'.gitkeep',
			'.gitattributes',
			'.mailmap',
			'.php_cs.dist',
			'.php_cs'
		);
	}

	/**
	 * Loads the signatures from the database
	 */
	protected function load_signatures() {
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'rsfirewall_signatures';

		$signatures = $wpdb->get_results( $query, OBJECT );
		
		foreach ($signatures as $signature)
		{
			$signature->signature = base64_decode($signature->signature);
		}

		$check_md5 = RSFirewall_Config::get('check_md5', 1);
		
		if ($check_md5) {
			// Load MD5 signatures
			$file = RSFIREWALL_BASE . 'assets/sigs/php.csv';

			if (file_exists($file) && is_readable($file))
			{
				$lines = file($file, FILE_IGNORE_NEW_LINES);
				foreach ($lines as $line)
				{
					list($hash, $desc) = explode(',', $line);
					$signatures[] = (object) array(
						'signature' => $hash,
						'type' 		=> 'md5',
						'reason' 	=> $desc
					);
				}
			}
		}

		return $signatures;
	}

	protected function readable_filesize( $bytes, $decimals = 2 ) {
		$size   = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
		$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

		return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$size[ $factor ];
	}

	public function get_ds() {
		return self::DS;
	}

	/**
	 * Grabs the files of a folder
	 */
	public function get_files( $folder, $recurse = false, $sort = true, $fullpath = true, $ignore = array(), $output_error = false ) {
		if ( ! is_dir( $folder ) ) {
			$this->add_log_entry( "[get_files] $folder is not a valid folder!", true );

			if ($output_error) {
				return sprintf(__('%s is not a valid folder', 'rsfirewall'), $folder);
			}
			return false;
		}

		$arr = array();

		try {
			$handle = @opendir( $folder );
			if ($handle) {
				while (($file = readdir($handle)) !== false) {
					if ($file != '.' && $file != '..' && !in_array($file, $ignore)) {
						$dir = $folder . self::DS . $file;
						if (is_file($dir)) {
							if ($fullpath) {
								$arr[] = $dir;
							} else {
								$arr[] = $file;
							}
						} elseif (is_dir($dir) && $recurse) {
							$arr = array_merge($arr, $this->get_files($dir, $recurse, $sort, $fullpath, $ignore));
						}
					}
				}
				closedir($handle);
			}  else {
				if ($output_error) {
					return sprintf(__('Cannot open folder %s!', 'rsfirewall'), $folder);
				}
				return false;
			}
		} catch ( Exception $e ) {
			if ($output_error) {
				return sprintf($e->getMessage(), $folder);
			}
			return false;
		}
		if ( $sort ) {
			asort( $arr );
		}

		return $arr;
	}

	/**
	 * Grabs the folders from the path
	 * /**
	 * @param      $folder
	 * @param bool $recurse
	 * @param bool $sort
	 * @param bool $fullpath
	 * @param bool $output_error
	 *
	 * @return array|bool
	 */
	public function get_folders( $folder, $recurse = false, $sort = true, $fullpath = true, $output_error = false ) {
		if ( ! is_dir( $folder ) ) {
			$this->add_log_entry( "[get_folders] $folder is not a valid folder!", true );

			if ($output_error) {
				return sprintf(__('%s is not a valid folder', 'rsfirewall'), $folder);
			}
			return false;
		}

		$arr = array();

		try {
			$handle = @opendir($folder);
			if ($handle) {
				while (($file = readdir($handle)) !== false) {
					if ($file != '.' && $file != '..') {
						$dir = $folder . self::DS . $file;
						if (is_dir($dir)) {
							if ($fullpath) {
								$arr[] = $dir;
							} else {
								$arr[] = $file;
							}
							if ($recurse) {
								$arr = array_merge($arr, $this->get_folders($dir, $recurse, $sort, $fullpath));
							}
						}
					}
				}
				closedir($handle);
			}  else {
				if ($output_error) {
					return sprintf(__('Cannot open folder %s!', 'rsfirewall'), $folder);
				}
				return false;
			}
		} catch ( Exception $e ) {
			if ($output_error) {
				return sprintf($e->getMessage(), $folder);
			}
			return false;
		}
		if ( $sort ) {
			asort( $arr );
		}

		return $arr;
	}

	/**
	 * Grab the parent
	 *
	 * @param $path
	 *
	 * @return string
	 */
	protected function get_parent( $path ) {
		$parts = explode( self::DS, $path );
		array_pop( $parts );

		return implode( self::DS, $parts );
	}



	public function save_grade() {
		$grade = isset($_POST['grade']) ? sanitize_text_field($_POST['grade']) : null;

		if (!is_null($grade)) {
			update_option('rsfirewall_grade', $grade);

			// Store the current local timestamp
			$current_time = current_time( 'timestamp');
			update_option('rsfirewall_system_check_last_run', $current_time);

			$this->add_log_entry( "System check finished: ".$grade );
		}
	}
}