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

class RSFirewall_Helper
{
	public static $error_type;
	public static $message;
	public static $prefix = RSFIREWALL_POSTS_PREFIX;

	/**
	 * @return array
	 */
	public static function get_statistics() {
		$args = array(
			'post_type'      => self::$prefix.'threats',
			'post_status'    => 'publish',
			'posts_per_page' => '500',
		);

		$results = new WP_Query( $args );
		wp_reset_postdata();

		$geoip = false;
		if (class_exists('RSFirewall_GeoIp')) {
			$geoip = RSFirewall_GeoIp::get_instance();
		}

		$date = array();
		$ip   = array();
		if ( $results ) {
			foreach ( $results->posts as $result ) {
				$date[] = get_the_date( "m/Y", $result->ID );
				if ($geoip) {
					$ip[]   = $geoip->get_country_code( get_post_meta( $result->ID, 'rsfirewall_ip', true ) );
				}
			}
		}

		$return = array(
			'by_date' => json_encode( array_count_values( $date ) )
		);

		if (!empty($ip)) {
			$ip = array_filter($ip);
			$return['by_ip'] = strtolower( json_encode( array_count_values( $ip ) ) );
		}

		return $return;
	}

	/**
	 * Helper function to get an ip
	 *
	 * @since 1.0.0
	 */
	public static function get_ip() {
		static $ip;

		if (is_null($ip)) {
			$ip =  RSFirewall_IP::get();
		}

		return $ip;
	}

	/**
	 *
	 * @return int
	 */
	public static function count_offenders($ip = null) {
		global $wpdb;
		if (is_null($ip)) {
			$ip = self::get_ip();
		}

		$wpdb->show_errors();
		$table   = $wpdb->prefix . 'rsfirewall_offenders';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM $table
				WHERE ip = %s",
				$ip
			),
			ARRAY_N
		);

		return count( $results );
	}

	/**
	 * Function to display notices in the admin area
	 *
	 * @since 1.0.0
	 */
	public static function display_admin_notice() {
		if (is_null(self::$error_type) || is_null(self::$message)) {
			return false;
		}

		return '<div class="'.self::$error_type.'"><p>'.self::$message.'</p></div>';
	}

	/**
	 * @param $array
	 *
	 * @return mixed
	 */


	public static function get_array_key( $array ) {
		$keys = array_keys( $array );

		return $keys[0];
	}

	/**
	 * Function to add files that need to be protected in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param $files
	 *
	 * @return bool and notice
	 */
	public static function monitor_these( $files ) {
		global $wpdb;

		$wpdb->show_errors();
		$table = $wpdb->prefix . 'rsfirewall_protected';

		$object = array();

		if ( empty( $files ) ) {
			$wpdb->delete(
				$table,
				array(
					'additional' => 'protected'
				)
			);

			return false;
		}

		$files = explode( ',', $files );

		foreach ( $files as $file ) {
			$path = RSFIREWALL_SITE . '/' . trim( $file );
			if ( file_exists( $path ) && is_readable( $path ) && ! is_dir( $path ) ) {
				$object[] = array(
					'object'     => trim( $file ),
					'hash'       => md5_file( $path ),
					'type'       => 'file',
					'additional' => 'protected',
					'noticed'    => 0
				);
			}
		}

		if ( empty( $object ) ) {
			return false;
		}

		$all_files = '';
		foreach ( $object as $protected ) {
			$exists = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT *
					 FROM $table
					 WHERE object = %s",
					$protected['object']
				),
				OBJECT
			);

			if ( ! empty( $exists ) ) {
				continue;
			}
			$all_files .= ' ' . $protected['object'];

			$wpdb->insert(
				$table,
				array(
					'object'     => $protected['object'],
					'hash'       => $protected['hash'],
					'type'       => $protected['type'],
					'additional' => $protected['additional'],
					'noticed'    => $protected['noticed'],
				)
			);
		}

		return true;
	}

	/**
	 * Function to get the current screen id.
	 *
	 * @since 1.0.0
	 *
	 *
	 * @return string screen id
	 */
	public static function get_current_screen() {
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';

		return $screen_id;
	}

	/**
	 * Function used for redirect.
	 *
	 * @since 1.0.0
	 */

	public static function redirect($url) {
		wp_redirect( $url );
		exit();
	}


	/**
	 * Helper function needed to load the modal (dependencies/output).
	 *
	 * @param $load -> type to load (dependencies or output)
	 * @param $version -> the rsfirewall version
	 *
	 * @since 1.0.0
	 */
	public static function load_rsmodal($load = null, $version = false) {
		if (is_null($load) || !is_string($load)) {
			return;
		}

		if (strpos($load, '.') !== false) {
			list($type, $dependency) = explode('.', $load, 2);
		} else {
			$type = $load;
		}

		switch ($type) {
			case 'dependencies':
				if ($dependency == 'js') {
					wp_enqueue_script( 'rsmodal', RSFIREWALL_URL . 'assets/js/rsmodal.js', array( 'jquery' ), $version, false );
				}

				if ($dependency == 'css') {
					wp_enqueue_style( 'rsmodal', RSFIREWALL_URL . 'assets/css/rsmodal.css', array(), $version, 'all' );
				}
			break;

			case 'output':
				$template_file =  RSFIREWALL_BASE . 'templates/rsmodal.php';
				if (file_exists($template_file)) {
					include $template_file;
				}
			break;
		}
	}

	/**
	 * Helper function needed to escape.
	 *
	 * @param $string -> the string that needs to be excaped
	 *
	 * @return string escaped
	 */

	public static function escape($string){
		return htmlentities($string, ENT_COMPAT, 'utf-8');
	}

	public static function check_nonce($action, $param = 'security') {
		// Check the nonce for this action
		if (!check_ajax_referer( $action, $param )) {
			wp_die(__('Unauthorised access!', 'rsfirewall'));
		}
	}

	/**
	 * Helper function needed to transform an URL to UTF-8 safe version of PHP parse_url function.
	 *
	 * @param $url| string
	 *
	 * @return array | false if badly formed URL
	 */
	public static function parse_url($url)
	{
		$result = false;

		// Build arrays of values we need to decode before parsing
		$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%24', '%2C', '%2F', '%3F', '%23', '%5B', '%5D');
		$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "$", ",", "/", "?", "#", "[", "]");

		// Create encoded URL with special URL characters decoded so it can be parsed
		// All other characters will be encoded
		$encodedURL = str_replace($entities, $replacements, urlencode($url));

		// Parse the encoded URL
		$encodedParts = parse_url($encodedURL);

		// Now, decode each value of the resulting array
		if ($encodedParts)
		{
			// make it array to avoid deprecated warning
			$result = array();
			
			foreach ($encodedParts as $key => $value)
			{
				$result[$key] = urldecode(str_replace($replacements, $entities, $value));
			}
		}

		return $result;
	}


	/**
	 * Function to call stored cached value of a function, if not present then execute and store the output of the function into the cache
	*/
	public static function cache_call($name, $callback, $lifetime = array('transient' => 30, 'cache' => 24)) {
		$args = func_get_args();
		// unset the first two arguments, because they are the callback and the lifetime
		unset($args[0]);
		unset($args[1]);
		unset($args[2]);

		// in case the $lifetime argument is not passed correctly
		if (!isset($lifetime['transient']) || !isset($lifetime['cache'])) {
			$lifetime = array(
				'transient' => 30,
				'cache' => 24
			);
		}
		
		// make the name unique binding the arguments
		if (!empty($args)) {
			$hash = serialize($args);
			$name .= md5($hash);
		}

		$return = wp_cache_get( $name );
		if ( false === $return ) {
			// Check the transient if the chache is not present
			$return = get_transient( $name );

			if ( false === $return ) {
				$return = call_user_func_array($callback, $args);

				set_transient( $name, $return, (int) $lifetime['transient'] * MINUTE_IN_SECONDS );
			}

			// Set the cache fo further use either from the transient or the post
			wp_cache_set( $name, $return, 'default',  (int) $lifetime['cache'] * MINUTE_IN_SECONDS );
		}
		return $return;

	}

	/**
	 * Explode by new lines
	 */

	public static function explode($string)
	{
		$string = str_replace(array("\r\n", "\r"), "\n", $string);

		return explode("\n", $string);
	}

	/**
	 * Helper function to detect which folders are hardened
	 */
	public static function check_hardened_directories() {
		static $check_harden_folders;

		if (is_null($check_harden_folders)) {
			$path = rtrim(ABSPATH, DIRECTORY_SEPARATOR);
			// check which directory is hardened
			$check_harden_folders = array(
				'uploads'     => RSFirewall_Helper_Harden::is_hardened($path.'/wp-content/uploads'),
				'wp-content'  => RSFirewall_Helper_Harden::is_hardened($path.'/wp-content'),
				'wp-includes' => RSFirewall_Helper_Harden::is_hardened($path.'/wp-includes')
			);
		}

		return $check_harden_folders;
	}
	
	/**
	 * Function to detect the correct config file path
	 */
	public static function get_config_path()
	{
		$filename = ABSPATH . '/wp-config.php';

		/* check one directory up */
		if (!file_exists($filename)) {
			$filename = ABSPATH . '/../wp-config.php';
		}

		return realpath($filename);
	}

	/**
	 * Helper function to parse XML fields of type select
	 */
	public static function select_options($options)
	{
		$results = array();

		if ($options instanceof SimpleXMLElement)
		{
			foreach ($options as $option)
			{
				$label      = (string) $option;
				$value      = (string) $option->attributes()->value;
				$checked    = $option->attributes()->checked;

				if (!strlen($value)) {
					$value = $label;
				}

				$results[] = (object) array(
					'value'     => $value,
					'label'     => $label,
					'checked'   => $checked
				);
			}
		}

		return $results;
	}
	
	/**
	 * Helper function get the uploads dir path
	 */
	public static function get_uploads_path()
	{
		$upload = wp_upload_dir(null, false);

		// In case some error is present will use the standard path
		if ($upload['error']) {
			return WP_CONTENT_DIR . '/uploads';
		}

		return $upload['basedir'];
	}

	// Pro Version related functions
	public static function call_user_func_pro($args = array()) {
		if (count($args) != 2) {
			return null;
		}
		static $result = array();
		$hash = implode('',$args);
		$hash = md5($hash);

		if (!isset($result[$hash])) {
			$class_name = $args[0];
			$function = $args[1];

			if (class_exists($class_name . 'Pro') && is_callable( array( $class_name. 'Pro', $function ) )) {
				$result[$hash] = call_user_func(array($class_name . 'Pro', $function));
			} else if (class_exists($class_name) && is_callable( array( $class_name, $function ) )) {
				$result[$hash] = call_user_func(array($class_name, $function));
			} else {
				$result[$hash] = null;
			}
		}

		return $result[$hash];
	}

	public static function class_exists_pro($class){
		if (class_exists($class.'Pro')) {
			return true;
		} else if (class_exists($class)) {
			return true;
		}

		return false;
	}

	// this cannot be cached in static
	public static function call_user_func_pro_args($args = array()) {
		if (empty($args) || count($args) < 2) {
			return null;
		}

		$class_name = $args[0];
		$function = $args[1];

		unset($args[0]);
		unset($args[1]);

		if (class_exists($class_name. 'Pro') && is_callable(array( $class_name. 'Pro', $function ))) {
			return call_user_func_array(array($class_name . 'Pro', $function), $args);
		} else if (class_exists($class_name) && is_callable( array( $class_name, $function ) )) {
			return call_user_func_array(array($class_name, $function), $args);
		} else {
			return null;
		}
	}

	public static function buildWPCallback($args = array()) {
		static $pro_classes = array();

		$class_name = $args[0];
		$callback = $args[1];

		if (!isset($pro_classes[$class_name])) {
			if (class_exists($class_name . 'Pro')) {
				$pro_classes[$class_name] = $class_name . 'Pro';
			} else {
				$pro_classes[$class_name] = $class_name;
			}
		}

		return array($pro_classes[$class_name], $callback);

	}

	public static function removeProPart($string) {
		static $result = array();

		if (!isset($result[$string])) {
			$compare = substr($string, -3);
			$compare = strtolower($compare);
			if ($compare === 'pro') {
				$result[$string] = substr($string, 0, -3);
			} else {
				$result[$string] = $string;
			}

		}

		return $result[$string];
	}
}