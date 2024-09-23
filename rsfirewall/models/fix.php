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

class RSFirewall_Model_Fix extends RSFirewall_Model {
	const DS = DIRECTORY_SEPARATOR;

	/**
	 * Changes usernames
	 *
	 * @param $args     string      username
	 *
	 * @since 1.0.0
	 * @throws Exception
	 *
	 * @return array
	 */
	public function admin_username_fix( $args ) {
		$errors    = array();
		$arguments = array(
			'search' => $args['new_user'],
		);

		$common = array(
			'admin',
			'administrator',
			'wp-admin'
		);

		$users = get_users( $arguments );

		if ( ! empty( $users ) ) {
			$error = esc_html__( 'An identical username already exists.', 'rsfirewall' );
			foreach ( $users as $user ) {
				if ( $args['new_user'] === $user->user_login ) {
					$errors[] = $error;
				}
			}
		}

		if ( $args['old_user'] == $args['new_user'] ) {
			$errors[] = esc_html__( 'Username should not be identical.', 'rsfirewall' );
		}

		if ( $args['new_user'] == '' ) {
			$errors[] = esc_html__( 'Username can\'t be an empty string.', 'rsfirewall' );
		}

		if ( strlen( $args['new_user'] ) < 5 ) {
			$errors[] = esc_html__( 'Your username is too short. We recommend at least 5 characters.', 'rsfirewall' );
		}

		if ( in_array( $args['new_user'], $common ) ) {
			$errors[] = esc_html__( 'The username is too common', 'rsfirewall' );
		}


		// Do not add by mistake an unwanted admin username
		$unwanted = RSFirewall_Core_Vulnerabilities::$unwanted_admins;
		foreach ($unwanted as $u_admin) {
			if (stripos($args['new_user'], $u_admin) !== false) {
				$errors[] = esc_html__( 'The username is reported as dangerous! Please reconsider.', 'rsfirewall' );
				break;
			}
		}


		if ( count( $errors ) > 0 ) {
			$return['weak_username'] = true;
			$return['message']       = esc_html__( 'We recommend the following adjustments to the username:', 'rsfirewall' );
			$return['details']       = implode( '<br />', $errors );

			return $return;
		}

		$return = array(
			'weak_username' => false,
			'message'       => esc_html__( 'The username was changed succesfully!', 'rsfirewall' )
		);

		global $wpdb;
		$result = $wpdb->update(
			$wpdb->prefix . 'users',
			array( 'user_login' => $args['new_user'] ),
			array( 'user_login' => $args['old_user'] ),
			$format = NULL,
			$where_format = NULL
		);

		if ( false === $result ) {
			$return['message'] = esc_html__( 'Username could not be changed, please try again.', 'rsfirewall' );
		}

		return $return;

	}

	/**
	 * Deletes admin users
	 *
	 * @param $args     int      user_id
	 *
	 * @since 1.0.0
	 * @throws Exception
	 */
	public function delete_admin_user($args) {
		if (!isset($args['user_id']) || empty($args['user_id'])) {
			throw new Exception(esc_html__( 'No User ID is specified.', 'rsfirewall' ));
		}

		// make sure the id is an int
		$user_id = (int) $args['user_id'];

		// get current logged in user -  cannot delete own user
		$current_user = wp_get_current_user();

		if ($user_id == $current_user->ID) {
			throw new Exception(esc_html__( 'Cannot delete own user.', 'rsfirewall' ));
		}

		// check if the user actually exist
		$user = get_user_by('ID', $user_id);

		if (!$user) {
			throw new Exception(esc_html__( 'This user does not exist.', 'rsfirewall' ));
		}

		// if somehow the function is triggered with an ID that is not an administrator
		if ($user->roles[0] != 'administrator') {
			throw new Exception(esc_html__( 'This user is not an administrator.', 'rsfirewall' ));
		}

		// Delete the user if all above passes
		if (!wp_delete_user($user->ID)) {
			throw new Exception(esc_html__( 'Something went wrong.', 'rsfirewall' ));
		}
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function getINI( $name ) {
		return ini_get( $name );
	}

	/**
	 * @param        $name
	 * @param string $against
	 *
	 * @return bool
	 */
	public function compareINI( $name, $against = '1' ) {
		return $this->getINI( $name ) == $against;
	}

	/**
	 * Helper function to build the PHP ini file
	 *
	 * @return string
	 */
	public function build_php_ini() {
		$isPHP54 = version_compare( phpversion(), '5.4.0', '>=' );
		$isWin   = substr( PHP_OS, 0, 3 ) == 'WIN';

		$contents = array(
			'allow_url_include=Off',
			'disable_functions=system, shell_exec, passthru, exec, phpinfo, popen, proc_open'
		);

		if ( ! $isPHP54 ) {
			$contents[] = 'register_globals=Off';
			$contents[] = 'safe_mode=Off';
		}

		if ( $this->compareINI( 'open_basedir', '' ) ) {
			$paths     = array();
			$delimiter = $isWin ? ';' : ':';

			// add the path to the WP installation
			if ( get_home_path() ) {
				$paths[] = get_home_path();
			}

			// try to add the path for the server temporary folder
			if ( $path = $this->getINI( 'upload_tmp_dir' ) ) {
				$paths[] = $path;
			}
			if ( $temp_dir = sys_get_temp_dir() ) {
				$paths[] = $temp_dir;
			}
			// try to add the path for the server session folder
			if ( $path = $this->getINI( 'session.save_path' ) ) {
				$paths[] = $path;
			}

			$paths = array_filter( $paths );

			$contents[] = 'open_basedir=' . implode( $delimiter, array_unique( $paths ) );
		} else {
			$contents[] = 'open_basedir=' . $this->getINI( 'open_basedir' );
		}

		return implode( "\r\n", $contents );
	}

	/**
	 * Function to save the php ini
	 *
	 * @param $content
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function save_php_ini( $content ) {

		$access_type = get_filesystem_method();
		if ( $access_type === 'direct' ) {
			/* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

			/* initialize the API */
			if ( ! WP_Filesystem( $creds ) ) {
				/* any problems and we exit */
				return false;
			}

			global $wp_filesystem;
			/* do our file manipulations below */
			$wp_filesystem->put_contents(
				get_home_path() . '/php.ini',
				$content,
				FS_CHMOD_FILE // predefined mode settings for WP files
			);

			return true;
		}

		return false;

	}

	/**
	 * Attempts to fix the php directives by creating a local php ini file
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function php_configuration_fix() {
		$result = array(
			'success' => true,
			'message' => esc_html__( 'RSFirewall! succesfully created the php.ini file', 'rsfirewall' )
		);

		$contents = $this->build_php_ini();
		// using @ because file_put_contents outputs a warning when unsuccessful
		if ( ! @$this->save_php_ini( $contents ) ) {
			$error             = error_get_last();
			$result['result']  = false;
			$result['message'] = $error['message'];
			$result['details'] = $contents;
		}

		return $result;
	}

	/**
	 * Function that handles the ignoring of hashes/files
	 *
	 * @param $args
	 * @param $type
	 * @param $table
	 *
	 * @return array
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function ignore_stuff( $args, $type, $table ) {
		global $wpdb, $wp_version;
		$wpdb->show_errors();
		$table  = $wpdb->prefix . $table;
		$result = array();
		$added = 0;

		switch ( $type ) {
			case 'files':
				foreach ( $args['files'] as $file ) {
					$query = $wpdb->insert(
						$table,
						array(
							'path' => RSFIREWALL_SITE . self::DS . stripslashes( $file ),
							'type' => 'ignore_file',
						)
					);

					if ( false === $query ) {
						$result['result'] = false;
						throw new Exception ( esc_html__( 'Could not add the files to the ignored table. Please try again.', 'rsfirewall' ) );
					}

					$result['result']  = true;
					$added++;
				}
				break;
			case 'hashes':
				foreach ( $args['data'] as $file ) {
					// set the proper flag
					$flag = (isset($file['type']) && $file['type'] == 'missing') ? 'M' : '';

					// check if the file is the 'protect' type.
					$is_protect = false;
					if (isset($file['version']) && $file['version'] == 'protect') {
						$is_protect = true;
					}

					// full path to the file
					$file_path 	= $is_protect ? $file['file'] : RSFIREWALL_SITE . '/' . $file['file'];

					if (file_exists($file_path) && !is_readable($file_path)) {
						$result['result'] = false;
						throw new Exception ( sprintf(esc_html__( 'Could not open %s for reading. Please make sure you have read permissions.', 'rsfirewall' ), $file_path) );
					}

					if ($flag == 'M') {
						// no hash for a missing file
						$file_hash = '';
					} else {
						// this check is done only when the file exists and has the wrong hash
						if (!is_file($file_path)) {
							$result['result'] = false;
							throw new Exception ( sprintf(esc_html__( '%s: file not found.', 'rsfirewall' ), $file_path) );
						}
						// calculate the hash
						$file_hash = md5_file($file_path);
					}

					if ($is_protect) {
						// Rebuild the protected files hashes
						$id = $wpdb->get_var("SELECT `id` FROM $table  WHERE `file`='".$file['file']."' AND `type`='protect' LIMIT 1");
					} else {
						// Rebuild or add core files hashes
						$id = $wpdb->get_var("SELECT `id` FROM $table  WHERE `file`='" . $file['file'] . "' AND (`type`='ignore' OR `type`='" . $wp_version . "') LIMIT 1");
					}

					// The update is available for all file types
					if (!is_null($id)) {
						$query = $wpdb->update(
							$table,
							array(
								'hash' => $file_hash,
								'flag' => $flag,
								'date' => current_time('mysql'),
							),
							array(
								'id' => $id,
							)
						);
					} else if(!$is_protect) {
						$query = $wpdb->insert(
							$table,
							array(
								'file' => $file['file'],
								'hash' => $file_hash,
								'type' => 'ignore',
								'flag' => $flag,
								'date' => current_time('mysql'),
							)
						);
					}

					if (!$is_protect &&  false === $query ) {
						$result['result'] = false;
						throw new Exception ( esc_html__( 'Could not add the files to the hashes table. Please try again.', 'rsfirewall' ) );
					}

					$result['result']  = true;
					$added++;
				}
				break;
		}

		if ($added)
		{
			if ($type == 'files')
			{
				$result['message'] = sprintf( esc_html__( '%d file(s) was added to the ignored table.', 'rsfirewall' ), $added );
			}
			else if ($type == 'hashes')
			{
				$result['message'] = sprintf( esc_html__( '%d file(s) was added to the hashes table.', 'rsfirewall' ), $added );
			}
		}

		return $result;
	}

	/**
	 * Function that sets the file/folder permissions
	 *
	 * @param $paths
	 * @param $perms
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function set_permissions( $paths, $perms ) {
		$success = array();
		if ( ! is_array( $paths ) ) {
			$paths = (array) $paths;
		}

		foreach ( $paths as $path ) {
			$success[] = (int) @chmod( RSFIREWALL_SITE . self::DS . $path, octdec( $perms ) );
		}

		// the result will be an array with the same length as the input array
		// 0 for failure, 1 for success
		return $success;
	}

	/**
	 * Function to delete the post revisions from the database
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function fix_delete_revisions() {
		global $wpdb;
		$table  = $wpdb->prefix . 'posts';
		$result = $wpdb->delete( $table, array( 'post_type' => 'revision' ) );

		if ( $result ) {
			return array(
				'result'  => true,
				'message' => sprintf( esc_html__( 'We managed to delete %d revisions from the database', 'rsfirewall' ), $result ),
			);
		}

		return array(
			'result'  => false,
			'message' => esc_html__( 'No posts have been deleted. Please try again.', 'rsfirewall' )
		);
	}

	/**
	 * Remove the ip from the blocklist
	 *
	 * @param args
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function remove_ip( $args ) {
		$result = wp_delete_post( $args, true );

		if ( ! $result ) {
			return array(
				'result'  => false,
				'message' => esc_html__( 'The entry wasn\'t be deleted from the database. Please try again.', 'rsfirewall' )
			);
		}

		return array(
			'result'  => true,
			'message' => esc_html__( 'This entry was deleted from the database.', 'rsfirewall' ),
		);

	}
}