<?php
/**
 * @package    RSFirewall!
 * @copyright  (c) 2018 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

class RSFirewall_i18n
{
	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public static function load_plugin_textdomain() {
	    static $loaded = false;

	    if (!$loaded) {
			$loaded = true;
            load_plugin_textdomain(
                'rsfirewall',
                false,
                RSFIREWALL_REL_PATH . '/languages/'
            );
        }
	}

    /**
     * Arrays that hold localisation strings for javascripts
     *
     * @param $array
     *
     * @return $array
     */
	public static function get_locale($array)
    {
		self::load_plugin_textdomain();
        $codes = array(
            'PROTECTED_USER_CHANGE'      				=> wp_kses_post(__( 'There was an attempt to change a protected user:<br/> <strong>(%s)</strong>.', 'rsfirewall' )),
            'CORE_FILE_MODIFIED'         				=> wp_kses_post(__( 'The following core file is modified: <em> %s </em>. Please review it manually as the scan might have detected false alerts.', 'rsfirewall' )),
            'PROTECTED_FILES'            				=> wp_kses_post(__( 'The following files have been flagged as protected: <em> %s </em>', 'rsfirewall' )),
            'PROTECTED_FILES_MODIFIED'   				=> wp_kses_post(__( 'The following file is modified: <em> %s </em>. Please review it manually as the scan might have detected false alerts.', 'rsfirewall' )),
            'REMOVED_PROTECTED_FILES'   				=> esc_html__( 'Files removed from the protection table.', 'rsfirewall' ),
            'IP_BLOCKED'                				=> esc_html__( 'The IP is Blocked', 'rsfirewall' ),
            'MALWARE_DETECTED'           				=> esc_html__( 'Connection blocked - Malware Detected: %s', 'rsfirewall' ),
            'DANGEROUSE_USER_AGENT'      				=> esc_html__( 'Connection blocked - Dangerouse User Agent: %s', 'rsfirewall' ),
            'REFERER_BLOCKED'            				=> esc_html__( 'Referer blocked: %s', 'rsfirewall' ),
            'GEOIP_BLOCKED'              				=> esc_html__( 'Connection blocked. %s', 'rsfirewall' ),
            'NEW_ADMIN_CREATED'          				=> esc_html__( 'Attempt to create a new Admin account (%s) was blocked.', 'rsfirewall' ),
            'LOGIN_FAILED_ATTEMPT'      				=> esc_html__( 'Login failed attempt!', 'rsfirewall' ),
            'LOGIN_FAILED_ATTEMPT_CAPTCHA'      		=> esc_html__( 'Login failed attempt! Captcha was not verified.', 'rsfirewall' ),
            'LOGIN_FAILED_ATTEMPT_PASSWORD'      		=> wp_kses_post(__( 'Login failed attempt! Password used: <em>%s</em>', 'rsfirewall' )),
			'LOGIN_TFA_FAILED_ATTEMPT'      			=> wp_kses_post(__( 'Two Factor Authentication failed attempt - type <em>%s</em>', 'rsfirewall' )),
            'BACKEND_LOGIN_ERROR'        				=> esc_html__( 'RSFirewall Backend Login failed attempt!', 'rsfirewall' ),
            'IP_BLACKLISTED'             				=> wp_kses_post(__( 'An IP has been blocklisted: <em>%s</em>', 'rsfirewall' )),
			'SESSION_INJECTION_ATTEMPT'  				=> wp_kses_post(__( 'Session injection attempted and blocked: <em>%s</em>', 'rsfirewall' )),
			'DNSBL_LISTED'				 				=> wp_kses_post(__( 'This IP is listed in a spam database. <em>%s</em>', 'rsfirewall' )),
			'USER_ENUM_ATTEMPTED'		 				=> wp_kses_post(__( 'Blocked User Enumeration attempt. User id scanned: <em>%s</em>', 'rsfirewall' )),
			'XMLRPC_ATTEMPTED'		 					=> wp_kses_post(__( 'Blocked XML-RPC server access attempt. Accessed file: <em>%s</em>', 'rsfirewall' )),
			'USER_REST_INFORMATION_ATTEMPT'		 		=> esc_html__( 'Blocked listing users from REST API.', 'rsfirewall' ),
			'USER_REST_INFORMATION_ATTEMPT_INVALID_ID'	=> esc_html__( 'Blocked listing user from REST API (Invalid ID request).', 'rsfirewall' ),
			'LFI_ATTEMPTED'				 				=> wp_kses_post(__( 'Local file inclusion attempted. <em>%s</em>', 'rsfirewall' )),
			'RFI_ATTEMPTED'				 				=> wp_kses_post(__( 'Remote file inclusion attempted. <em>%s</em>', 'rsfirewall' )),
			'NULLCODE_IN_URI'			 				=> esc_html__( 'Nullcode in URI.', 'rsfirewall' ),
			'SQLI_ATTEMPTED'			 				=> wp_kses_post(__( 'SQL injection attempted. <em>%s</em>', 'rsfirewall')),
			'XSS_ATTEMPTED'				 				=> wp_kses_post(__( 'XSS attempted. <em>%s</em>', 'rsfirewall')),
			'UPLOAD_MULTIPLE_EXTS_ERROR' 				=> wp_kses_post(__( 'There was an attempt to upload a file with multiple extensions. <em>%s</em>', 'rsfirewall')),
			'UPLOAD_EXTENSION_ERROR' 	 				=> wp_kses_post(__( 'There was an attempt to upload a file with a forbidden extension. <em>%s</em>', 'rsfirewall')),
			'UPLOAD_SHELL' 	 			 				=> wp_kses_post(__( 'There was an attempt to upload a malware script. <em>%s</em>', 'rsfirewall')),
			'EMPTY_USER_AGENT' 	 	 	 				=> esc_html__('Empty user agent detected.', 'rsfirewall'),
			'PERL_USER_AGENT' 	 	 	 				=> esc_html__('&laquo;perl&raquo; user agent detected.', 'rsfirewall'),
			'CURL_USER_AGENT' 	 	 	 				=> esc_html__('&laquo;cURL&raquo; user agent detected.', 'rsfirewall'),
			'JAVA_USER_AGENT' 	 	 	 				=> esc_html__('&laquo;Java&raquo; user agent detected.', 'rsfirewall'),
			'MOZILLA_USER_AGENT' 	 					=> esc_html__('&laquo;Mozilla impersonator&raquo; user agent detected.', 'rsfirewall'),
			'DANGEROUS_USER_AGENT' 	 	 				=> wp_kses_post(__( 'Dangerous user agent detected. User agent: <em>%s</em>', 'rsfirewall')),
			'GENERAL_REST_API_REQUEST' 	 	 			=> wp_kses_post(__( 'REST API call blocked. <em>%s</em>', 'rsfirewall')),
			// WP vulnerabilities
			'ARBITRARY_FILE_DELETION'					=> wp_kses_post(__( 'Arbitrary File Deletion attempt. <br/>Thumb Path:<em>%s</em>', 'rsfirewall')),
			'WP_GDPR_COMPLIANCE_VULNERABILITY_EXPLOIT'	=> wp_kses_post(__( 'WP GDPR Compliance vulnerability exploit attempt. <br/>Change attempted on: <em>%s</em>', 'rsfirewall')),
			'FLAGGED_ADMINISTRATOR_USER_AUTHENTICATE'	=> wp_kses_post(__( 'This administrator could be dangerous. <br/>Username: <em>%s</em>', 'rsfirewall'))
        );

        $rsfirewall_check = array(
            'ignore_button'           => esc_html__( 'Ignore Files', 'rsfirewall' ),
            'view_ignored_files'      => esc_html__( 'View Ignored Files', 'rsfirewall' ),
            'ignore_hashes'           => esc_html__( 'Accept changes for the selected files', 'rsfirewall' ),
            'accept_hash_changes'     => wp_kses_post(__( '<strong>Warning!</strong> Accepting the changes means that the next time the scan will be performed these files will be ignored, unless they are modified again. There is no way to revert the files back to their original state unless you inspect them yourself in order to verify what changes have been made', 'rsfirewall' )),
            'ignore_folders'          => esc_html__( 'Ignore Folders', 'rsfirewall' ),
            'ignore_files'            => esc_html__( 'Ignore Files', 'rsfirewall' ),
            'ignore_files_warning'    => wp_kses_post(__( '<strong>Warning!</strong> Ignoring these files means that the next time the scan will be performed, these files will no longer be checked for malware. If you want to revert the process go to \'Configuration / System Check\' and delete them manually from the \'Ignore files and folders\' field.', 'rsfirewall' )),
            'attempt_fix'             => esc_html__( 'Attempt Fix', 'rsfirewall' ),
            'view_contents'           => esc_html__( 'View Contents', 'rsfirewall' ),
            'building_file_structure' => esc_html__( 'Building file structure', 'rsfirewall' ),
            'malware_found'           => esc_html__( 'RSFirewall! found malware scripts inside your files. Please review them manually as the scan might have detected false alerts.', 'rsfirewall' ),
            'malware_not_found'       => esc_html__( 'There were no known malware files found in your WordPress folder. Please keep in mind that the malware database is limited and may not detect all variants.', 'rsfirewall' ),
            'core_files_ok'           => esc_html__( 'No files are modified from your WordPress (CMS) installation. This includes just the files that are present in the default installation of WordPress not your 3rd party plugins and templates.', 'rsfirewall' ),
            'core_files_not_ok'       => esc_html__( 'RSFirewall! found modified files in your WordPress installation', 'rsfirewall' ),
            'no_csv_file'             => esc_html__( 'RSFirewall! could not find the appropriate CSV file for your WP version', 'rsfirewall' ),
            'reason'                  => esc_html__( 'Reason', 'rsfirewall' ),
            'match'                   => esc_html__( 'Match', 'rsfirewall' ),
            'link'                    => esc_html__( 'Link', 'rsfirewall' ),
            'folder'                  => esc_html__( 'Folder', 'rsfirewall' ),
            'file'                    => esc_html__( 'File', 'rsfirewall' ),
            'actions'                 => esc_html__( 'Actions', 'rsfirewall' ),
            'download_original'       => esc_html__( 'Download Original', 'rsfirewall' ),
            'download'                => esc_html__( 'Download', 'rsfirewall' ),
            'confirm_overwrite'       => esc_html__( 'Are you sure you want to overwrite this file?', 'rsfirewall' ),
			'confirm_add'       	  => esc_html__( 'Are you sure you want to add this file?', 'rsfirewall' ),
            'files_fixed'             => esc_html__( 'All files in your installation have been fixed', 'rsfirewall' ),
            'php_ini_created'         => esc_html__( 'Local PHP Ini File created', 'rsfirewall' ),
            'fix_permission'          => esc_html__( 'Fix permission', 'rsfirewall' ),
            'global_fix'              => esc_html__( 'Fix', 'rsfirewall' ),
            'permissions_ok'          => esc_html__( 'Permissions are ok', 'rsfirewall' ),
            'permissions_issue'       => esc_html__( 'Permissions issue found, please review them manually', 'rsfirewall' ),
            'expected_permission'     => esc_html__( 'Expected', 'rsfirewall' ),
            'found_permission'        => esc_html__( 'Set', 'rsfirewall' ),
            'scanning_in_progress'    => esc_html__( 'Scanning is in progress...', 'rsfirewall'),
			'modal_title'			  => esc_html__('View File Contents', 'rsfirewall'),
			'modal_title_ignored_files'=> esc_html__('View Ignored Files', 'rsfirewall'),
			'file_modified_ago'		  => esc_html__('The file has been modified %s ago', 'rsfirewall'),
			'file_modified'		  	  => esc_html__('The file has been modified.', 'rsfirewall'),
			'file_missing'		  	  => esc_html__('The file is missing.', 'rsfirewall'),
			'remove_from_database'	  => esc_html__('Are you sure you want to remove this entry from the database?', 'rsfirewall'),
			'processing'	  		  => esc_html__('Processing', 'rsfirewall'),
			'success'	  		 	  => esc_html__('Success', 'rsfirewall'),
			'failed'	  		  	  => esc_html__('Failed', 'rsfirewall'),
			'scanning_still'		  => esc_html__('Scanning is still in progress - are you sure you want to navigate away from this page?', 'rsfirewall'),
        );

		$rsfirewall_dbcheck = array(
			'scanning_in_progress'  => esc_html__( 'Please wait while scanning completes...', 'rsfirewall' ),
			'scanning_finished' 	=> esc_html__( 'Scanning has finished', 'rsfirewall' )
		);

		$rsfirewall_diff = array(
            'files_fixed'       => esc_html__( 'All files in your installation have been fixed', 'rsfirewall' ),
            'confirm_overwrite' => esc_html__( 'Are you sure you want to overwrite this file?', 'rsfirewall' ),
			'confirm_add'       	  => esc_html__( 'Are you sure you want to add this file?', 'rsfirewall' )
        );

		$levels = array(
			'low' 		=> esc_html__('low', 'rsfirewall'),
			'medium' 	=> esc_html__('medium', 'rsfirewall'),
			'high' 		=> esc_html__('high', 'rsfirewall'),
			'critical'  => esc_html__('critical', 'rsfirewall')
		);

		$form_configuration = array(
			'lbl_geo_license_key'					=> esc_html__('MaxMind License Key', 'rsfirewall'),
			'desc_geo_license_key'					=> esc_html__('This is the license key used to automatically download the GeoIP database. More information is available in the docs.', 'rsfirewall'),
			'lbl_country_blocking' 					=> esc_html__('Country Blocking', 'rsfirewall'),
			'lbl_blocked_continents' 				=> esc_html__('Blocked Continents', 'rsfirewall'),
			'desc_blocked_continents'				=> wp_kses_post(__('Continent blocking will automatically select the countries based on the specified continent. The IPs belonging to those countries will no longer be able to access your WordPress! installation.','rsfirewall')),
			'lbl_blocked_countries' 				=> esc_html__('Blocked Countries', 'rsfirewall'),
			'desc_blocked_countries' 				=> wp_kses_post(__('Countries that are selected here will be blocked from visiting your website.','rsfirewall')),

			'lbl_backend_password' 					=> esc_html__('Backend Password', 'rsfirewall'),
			'lbl_enable_backend' 					=> esc_html__('Enable Backend Password', 'rsfirewall'),
			'desc_enable_backend' 					=> wp_kses_post(__('By <u>setting</u> this to Yes, you will be greeted with an additional password when accessing your /wp-admin URL. This prevents brute-force password attempts.','rsfirewall')),
			'lbl_type_password' 					=> esc_html__('Type Password', 'rsfirewall'),
			'desc_type_password' 					=> wp_kses_post(__('Please supply an Additional Backend Password with at least 6 characters.','rsfirewall')),
			'lbl_confirm_password' 					=> esc_html__('Re-Type Password', 'rsfirewall'),
			'desc_confirm_password' 				=> wp_kses_post(__('Retype password','rsfirewall')),
			'lbl_logout_redirect_separator'			=> esc_html__('Change Logout Redirect', 'rsfirewall'),
			'lbl_logout_redirect' 					=> esc_html__('Logout Redirect Link', 'rsfirewall'),
			'desc_logout_redirect' 					=> wp_kses_post(__('This link only works if the Backend Password or the Admin Slug are enabled. If the link is not mentioned it will redirect to the default WordPress location.','rsfirewall')),
			'lbl_admin_slug_separator' 				=> esc_html__('Change Admin Slug', 'rsfirewall'),
			'lbl_enable_admin_slug' 				=> esc_html__('Enable Admin Slug', 'rsfirewall'),
			'desc_enable_admin_slug' 				=> wp_kses_post(__('Use New Secure Admin URL Slug (ex: myadminlogin).<br/> You must enter a slug below for this to take effect!','rsfirewall')),
			'lbl_admin_slug_text' 					=> esc_html__('Text Slug', 'rsfirewall'),
			'desc_admin_slug_text' 					=> wp_kses_post(__('Enter the Slug that you want to use!','rsfirewall')),
			'lbl_admin_slug_current'				=> esc_html__('Your current Slug:', 'rsfirewall'),
			'desc_admin_slug_current'				=> wp_kses_post(__('You will need to enter this address for accessing the backend!','rsfirewall')),
			'lbl_rsfirewall_two_factor_auth'		=> esc_html__('Two Factor Authentication', 'rsfirewall'),
			'lbl_enable_two_factor_auth' 			=> esc_html__('Enable 2FA', 'rsfirewall'),
			'desc_enable_two_factor_auth' 			=> wp_kses_post(__('Enabling 2FA (Two Factor Authentication) will allow users to select their 2FA method.</br>Compatibility: <strong class="rsf-big">wp-admin login</strong>, <strong class="rsf-big">WooCommerce, Theme My Login, Ultimate Member, WP-Members, AJAX Login and Registration Modal Popup</strong>','rsfirewall')),
			'lbl_two_factor_auth_for_roles' 		=> esc_html__('For roles', 'rsfirewall'),
			'desc_two_factor_auth_for_roles' 		=> wp_kses_post(__('Specify which roles can use the 2FA system.','rsfirewall')),
			'lbl_enable_two_factor_auth_logging' 	=> esc_html__('Enable Logging', 'rsfirewall'),
			'desc_enable_two_factor_auth_logging' 	=> wp_kses_post(__('Enabling logging for the failed attempts.','rsfirewall')),
			'lbl_enable_two_factor_auth_autoban' 	=> esc_html__('Automatic Blocklisting', 'rsfirewall'),
			'desc_enable_two_factor_auth_autoban' 	=> wp_kses_post(__('Enabling Automatic Blocklisting for the failed attempts.','rsfirewall')),
			'lbl_two_factor_auth_autoban_attempts' 	=> esc_html__('after # of attempts', 'rsfirewall'),
			'desc_two_factor_auth_autoban_attempts' => wp_kses_post(__('This is the minimum number of attempts using the 2FA before the attacker will be added to the blocklist and banned from your website.','rsfirewall')),

			'lbl_system_check' 						=> esc_html__('System Check', 'rsfirewall'),
			'lbl_offset' 							=> esc_html__('Number of files/folders to check in a cycle', 'rsfirewall'),
			'desc_offset' 							=> wp_kses_post(__('This is the number of files/folders to check in one cycle. If you set a higher value there\'s a good chance you will run out of memory and the System Check will not finish. Please use a lower value if you are experiencing issues. The default value is 300.','rsfirewall')),
			'lbl_ignore_files_or_folders' 			=> esc_html__('Ignore Files or Folders', 'rsfirewall'),
			'desc_ignore_files_or_folders' 			=> wp_kses_post(__('During the System Check these folders and/or files will be ignored. <big><strong><em>Warning!</em></strong></big> If you select a folder, all its files and subfolders will be ignored as well.','rsfirewall')),
			'lbl_enable_logging' 					=> esc_html__('Enable Logging', 'rsfirewall'),
			'desc_enable_logging' 					=> wp_kses_post(__('Set this to Yes if you want to debug the RSFirewall! System Check. The output will be added to the wp-content/plugins/rsfirewall/logs folder, under the filename \'rsfirewall.php\'.','rsfirewall')),
			'lbl_file_permissions' 					=> esc_html__('File permissions', 'rsfirewall'),
			'desc_file_permissions' 				=> wp_kses_post(__('By default we recommend 644 file permissions. However, if your server requires something else please change this value so that the System Check will check if your permissions match.','rsfirewall')),
			'lbl_folder_permissions' 				=> esc_html__('Folder permissions', 'rsfirewall'),
			'desc_folder_permissions' 				=> wp_kses_post(__('By default we recommend 755 folder permissions. However, if your server requires something else please change this value so that the System Check will check if your permissions match.','rsfirewall')),
			'lbl_pause_between_requests' 			=> esc_html__('Pause between requests', 'rsfirewall'),
			'desc_pause_between_requests' 			=> wp_kses_post(__('Specify a pause (in seconds) between requests. Some servers might need this in order to avoid being flagged by their firewall as an attacker.','rsfirewall')),
			'lbl_check_md5' 						=> esc_html__('Use MD5 Signature DB', 'rsfirewall'),
			'desc_check_md5' 						=> wp_kses_post(__('By setting this to Yes the System Check will take into account an MD5 database to check the PHP files against; please note that this is rather resource intensive.','rsfirewall')),
			'lbl_google_apis' 						=> esc_html__('Google APIs To Use', 'rsfirewall'),
			'desc_google_apis' 						=> wp_kses_post(__('Select which APIs you would like to use during the System Check.','rsfirewall')),
			'lbl_google_safe_browsing_key' 			=> esc_html__('Google Safe Browsing Key', 'rsfirewall'),
			'desc_google_safe_browsing_key' 		=> wp_kses_post(__('Safe Browsing is a Google service that enables applications to check URLs against Google\'s constantly updated lists of suspected phishing, malware, and unwanted software pages.','rsfirewall')),
			'lbl_google_webrisk_api_key' 			=> esc_html__('Google Web Risk API Key - Server Key', 'rsfirewall'),
			'desc_google_webrisk_api_key' 			=> wp_kses_post(__('Web Risk is a Google service that enables applications to check URLs against Google\'s constantly updated lists of suspected phishing, malware, and unwanted software pages.','rsfirewall')),
			'lbl_dot_files' 						=> wp_kses_post(__('Ignored Hidden Files','rsfirewall')),
			'desc_dot_files' 						=> wp_kses_post(__('Any file that starts with a dot (.) is flagged as suspicious during the System Check, because these are usually hidden by the system and attackers exploit this. However, there are legitimate files that start with a dot and these can be specified here.','rsfirewall')),

			'lbl_active_scanner' 					=> esc_html__('Active Scanner', 'rsfirewall'),
			'lbl_enable_active_scanner'				=> esc_html__('Enable Active Scanner', 'rsfirewall'),
			'desc_enable_active_scanner'			=> wp_kses_post(__('By enabling the Active Scanner all the protections will be enabled on your WordPress! website.', 'rsfirewall')),
			'lbl_enable_active_scanner_in_admin'	=> esc_html__('Enable Active Scanner in Admin section?', 'rsfirewall'),
			'desc_enable_active_scanner_in_admin'	=> wp_kses_post(__('By setting this to Yes the PHP, JS and SQL protections will be triggered in the backend as well. This should only be enabled if you don\'t trust people that have access to your /wp-admin section.', 'rsfirewall')),
			'lbl_log_all_blocked_attempts'			=> esc_html__('Log all blocked attempts', 'rsfirewall'),
			'desc_log_all_blocked_attempts'			=> wp_kses_post(__('By setting this to Yes, every single attempt that\'s stopped by RSFirewall! will be logged. This is useful for debugging your website in case you have false alerts. We recommend setting this to No once you are done so that automated attacks don\'t fill your log.', 'rsfirewall')),
			'lbl_verify_emails'						=> esc_html__('Convert email addresses from plain text to images', 'rsfirewall'),
			'desc_verify_emails'					=> wp_kses_post(__('This setting will convert all email addresses from plain text to images.', 'rsfirewall')),
			'lbl_prevent_author_scan'				=> esc_html__('Block User Scan', 'rsfirewall'),
			'desc_prevent_author_scan'				=> wp_kses_post(__('Stops malicious scripts scanning the site for user data by requesting numerical user IDs even in REST API calls. (EX: ?author=1, example.com/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.com%2Fhello-world%2F, example.com/wp-json/wp/v2/users)', 'rsfirewall')),
			'lbl_ip_proxy_headers'					=> esc_html__('Grab IP from Proxy Headers', 'rsfirewall'),
			'desc_ip_proxy_headers'					=> wp_kses_post(__('Some servers are behind a proxy or firewall and will not provide the correct IP. If this is your case, contact the proxy provider and ask them through what header are they sending the real IP. Otherwise just leave these all checked by default and RSFirewall! will attempt to grab the IP by looking through all of them.', 'rsfirewall')),

			'lbl_core_scanner'						=> esc_html__('Core Scanner', 'rsfirewall'),
			'lbl_verify_generator'					=> esc_html__('Remove the generator meta tag', 'rsfirewall'),
			'desc_verify_generator'					=> wp_kses_post(__('Removing the generator meta tag from your website\'s template will protect you from spambots or attackers that target WordPress websites', 'rsfirewall')),
			'lbl_check_core_wp_integrity'			=> esc_html__('Check core WordPress files integrity', 'rsfirewall'),
			'desc_check_core_wp_integrity'			=> wp_kses_post(__('Checks a few core WordPress files for integrity, like the WordPress login and index.php', 'rsfirewall')),
			'lbl_monitor_files'						=> esc_html__('Monitor the following files for changes', 'rsfirewall'),
			'desc_monitor_files'					=> wp_kses_post(__('If any of the following files will be changed, you will be alerted by email and an entry will be posted in the System Log.', 'rsfirewall')),
			'lbl_block_xmlrpc'						=> esc_html__('Block access to XML-RPC Server (including Pingbacks and Trackbacks)', 'rsfirewall'),
			'desc_block_xmlrpc'						=> wp_kses_post(__('Stops malicious scripts exploiting the xmlrpc.php and wp-trackback.php files from being accessed (except safelisted IP\'s).', 'rsfirewall')),
			'lbl_disable_rest_api'					=> esc_html__('Disable REST API', 'rsfirewall'),
			'desc_disable_rest_api'					=> wp_kses_post(__('Returns a 404 page when any REST API call is made (except safelisted IP\'s and the exceptions you enter bellow). ', 'rsfirewall')),
			'lbl_allow_rest_api_logged'				=> esc_html__('Allow REST API calls for logged in Users', 'rsfirewall'),
			'desc_allow_rest_api_logged'			=> wp_kses_post(__('This option allows the logged in Users to use the REST API calls.<br/><em>(Test: http(s)://www.example.com/wp-json/wp/v2/posts/)</em>', 'rsfirewall')),
			'lbl_rest_api_exceptions'				=> esc_html__('Exceptions for the REST API', 'rsfirewall'),
			'desc_rest_api_exceptions'				=> wp_kses_post(__('Enter here the exceptions for the REST API. Each on a new line. <em>(Ex: contact-form-7)</em> ', 'rsfirewall')),

			'lbl_hardening'							=> esc_html__('Hardening', 'rsfirewall'),
			'lbl_harden_uploads'					=> esc_html__('Disallow direct access to PHP files in wp-content/uploads/', 'rsfirewall'),
			'desc_harden_uploads'					=> wp_kses_post(__('Stops direct URL access to PHP files from the wp-content/uploads/ directory!<br/> Be careful when using this option because many plugins or themes use PHP files to generate images or save temporary data; please use the "Safelist PHP files" option to allow specific files to run properly!', 'rsfirewall')),
			'lbl_harden_wp_content'					=> esc_html__('Disallow direct access to PHP files in wp-content/', 'rsfirewall'),
			'desc_harden_wp_content'				=> wp_kses_post(__('Stops direct URL access to PHP files from the wp-content/ directory!<br/> Be careful when using this option because many plugins or themes use PHP files to generate images or save temporary data; please use the "Safelist PHP files" option to allow specific files to run properly!', 'rsfirewall')),
			'lbl_harden_wp_includes'				=> esc_html__('Disallow direct access to PHP files in wp-includes/', 'rsfirewall'),
			'desc_harden_wp_includes'				=> wp_kses_post(__('Stops direct URL access to PHP files from the wp-includes/ directory!<br/> Be careful when using this option because many plugins or themes use PHP files to generate images or save temporary data; please use the "Safelist PHP files" option to allow specific files to run properly!', 'rsfirewall')),
			'lbl_harden_editors'					=> esc_html__('Disable plugin editors', 'rsfirewall'),
			'desc_harden_editors'					=> esc_html__('Disables the plugin editors to prevent unwanted core files modifications!', 'rsfirewall'),
			'lbl_harden_whitelisted_files'			=> esc_html__('Safelist Blocked PHP Files', 'rsfirewall'),
			'desc_harden_whitelisted_files'			=> esc_html__('After applying hardening to any of the above folders you can safelist any php file located in those folders if you require them to run.', 'rsfirewall'),

			'lbl_php_protection_separator'			=> esc_html__('PHP Protections', 'rsfirewall'),
			'lbl_local_file_inclusion'				=> esc_html__('Local File Inclusion', 'rsfirewall'),
			'desc_local_file_inclusion'				=> wp_kses_post(__('This disallows directory traversal techniques that might allow an attacker to read sensitive files by exploiting poorly coded plugins.','rsfirewall')),
			'lbl_remote_file_inclusion'				=> esc_html__('Remote File Inclusion', 'rsfirewall'),
			'desc_remote_file_inclusion'			=> wp_kses_post(__('This disallows access to URLs that might allow an attacker to download and run malicious scripts by exploiting poorly coded extensions.','rsfirewall')),
			'lbl_php_enable_protections_for'		=> esc_html__('Enable Protection For', 'rsfirewall'),
			'desc_php_enable_protections_for'		=> wp_kses_post(__('<strong><em>Form data (POST)</em></strong> enables filtering for information submitted through forms (eg. article editing, user registration etc).<br/> <strong><em>URL data (GET)</strong></em> enables filtering for variables that are located in the URL.','rsfirewall')),

			'lbl_sql_protection_separator'			=> esc_html__('SQL Protections', 'rsfirewall'),
			'lbl_sql_enable_protections_for'		=> esc_html__('Enable Protection For', 'rsfirewall'),
			'desc_sql_enable_protections_for'		=> wp_kses_post(__('<strong><em>Form data (POST)</strong></em> enables filtering for information submitted through forms (eg. article editing, user registration etc).<br/> <strong><em>URL data (GET)</strong></em> enables filtering for variables that are located in the URL.','rsfirewall')),

			'lbl_js_protection_separator'			=> esc_html__('Javascript Protections', 'rsfirewall'),
			'lbl_filter_javascript'					=> esc_html__('Filter Javascript', 'rsfirewall'),
			'desc_filter_javascript'				=> wp_kses_post(__('By setting this to Yes, the Javascript will be filtered instead of the connection being dropped.','rsfirewall')),
			'lbl_js_enable_protections_for'			=> esc_html__('Enable Protection For', 'rsfirewall'),
			'desc_js_enable_protections_for'		=> wp_kses_post(__('<strong><em>Form data (POST)</strong></em> enables filtering for information submitted through forms (eg. article editing, user registration etc).<br/> <strong><em>URL data (GET)</strong></em> enables filtering for variables that are located in the URL.','rsfirewall')),

			'lbl_dos_protection_separator'			=> esc_html__('Denial of Service', 'rsfirewall'),
			'lbl_dos_enable_protections_for'		=> esc_html__('Deny access to the following User Agents', 'rsfirewall'),
			'desc_dos_enable_protections_for'		=> wp_kses_post(__('The following User Agents are usually automated requests to your website and should not be allowed.<br/> <strong><em>Empty User Agents</strong></em> are usually DoS attack attempts or automated connections to your website.<br/> <strong><em>Perl</strong></em> scripts are used for automated connections to your website.<br/> <strong><em>cURL</strong></em> is used for automated connections to your website.<br/> <strong><em>Java</strong></em> performs automated connections as well.<br/> <strong><em>Mozilla Impersonators</strong></em> are automated scripts trying to impersonate a legitimate browser','rsfirewall')),
			'lbl_protect_forms_from_abuse'			=> esc_html__('Protect forms from abusive IPs', 'rsfirewall'),
			'desc_protect_forms_from_abuse'			=> wp_kses_post(__('Enabling this option will protect your forms from abusive IPs by checking if they exist in a PBL list.','rsfirewall')),
			'lbl_deny_access_to_referers'			=> esc_html__('Deny access to the following referers', 'rsfirewall'),
			'desc_deny_access_to_referers'			=> wp_kses_post(__('Referers are visitors coming from another website (domain). You can block multiple domains by specifying them each on a new line. You can also use wildcards, such as *.domain.com which will block any requests coming from all subdomains of domain.com (eg. www.domain.com, images.domain.com etc). Remember to add domain.com to the list as well, otherwise only subdomains will be blocked when using wildcards. You can also use wildcards anywhere in the domain name, eg. blocked-domain.*, blocked*domain.com','rsfirewall')),

			'lbl_auto_blacklist_separator'			=> esc_html__('Automatic Blocklisting', 'rsfirewall'),
			'lbl_enable_auto_blacklist'				=> esc_html__('Automatic blocklisting', 'rsfirewall'),
			'desc_enable_auto_blacklist'			=> wp_kses_post(__('Automatic blocklisting will automatically add to the blocklist repeat offenders based on the minimum number of attempts specified below.','rsfirewall')),
			'lbl_enable_auto_blacklist_for_admin'	=> esc_html__('Automatic blocklisting for admin area', 'rsfirewall'),
			'desc_enable_auto_blacklist_for_admin'	=> wp_kses_post(__('With this option enabled, failed backend logins will lead to an automatic ban. This option is independent from the reCAPTCHA challenge configurable below.','rsfirewall')),
			'lbl_autoban_attempts'					=> esc_html__('# of attempts', 'rsfirewall'),
			'desc_autoban_attempts'					=> wp_kses_post(__('This is the minimum number of attempts before the attacker will be added to the blocklist and banned from your website.','rsfirewall')),

			'lbl_captcha_separator'					=> esc_html__('reCAPTCHA', 'rsfirewall'),
			'lbl_enable_captcha'					=> esc_html__('Enable Google reCAPTCHA for login forms', 'rsfirewall'),
			'desc_enable_captcha'					=> wp_kses_post(__('This will prompt a reCAPTCHA security on you login forms. reCAPTCHA will appear after the number of failed login attempts you specify below.</br>Compatibility: <strong class="rsf-big">wp-admin login</strong> <br/> Plugins: <strong class="rsf-big">WooCommerce, Theme My Login, Ultimate Member, WP-Members, AJAX Login and Registration modal popup</strong>.','rsfirewall')),
			'lbl_recaptcha_secret_key'				=> esc_html__('Secret Key', 'rsfirewall'),
			'lbl_recaptcha_site_key'				=> esc_html__('Site Key', 'rsfirewall'),
			'lbl_autoban_captcha_attempts'			=> esc_html__('Activate reCAPTCHA after # of attempts', 'rsfirewall'),
			'desc_autoban_captcha_attempts'			=> wp_kses_post(__('Activate reCAPTCHA after this number of failed login attempts.','rsfirewall')),
			'lbl_enable_captcha_comments'			=> esc_html__('Enable Google reCAPTCHA for comment forms', 'rsfirewall'),
			'desc_enable_captcha_comments'			=> wp_kses_post(__('Activate reCAPTCHA for the comment forms. This will display the reCAPTCHA immediately (it ignores the above attempts option)! It will not be logged on failure.','rsfirewall')),
			'lbl_enable_captcha_register'			=> esc_html__('Enable Google reCAPTCHA for register forms', 'rsfirewall'),
			'desc_enable_captcha_register'			=> wp_kses_post(__('Activate reCAPTCHA for the register forms. This will display the reCAPTCHA immediately (it ignores the above attempts option)! It will not be logged on failure.','rsfirewall')),

			'lbl_backend_login_separator'			=> esc_html__('Backend Login', 'rsfirewall'),
			'lbl_capture_login_attempts'			=> esc_html__('Capture login attempts', 'rsfirewall'),
			'desc_capture_login_attempts'			=> wp_kses_post(__('By enabling this, everytime a user fails to login in the /wp-admin section will trigger an event in the Threats.','rsfirewall')),
			'lbl_capture_login_attempts_password'	=> esc_html__('Store passwords', 'rsfirewall'),
			'desc_capture_login_attempts_password'	=> wp_kses_post(__('Set whether to store the passwords used in the failed login attempts or not.','rsfirewall')),

			'lbl_uploads_separator'					=> esc_html__('Uploads', 'rsfirewall'),
			'lbl_filter_uploads'					=> esc_html__('Filter Uploads', 'rsfirewall'),
			'desc_filter_uploads'					=> wp_kses_post(__('By settings this to Yes, the uploads will be deleted instead of the connection being dropped.','rsfirewall')),
			'lbl_check_multiple_extensions'			=> esc_html__('Check for multiple extensions', 'rsfirewall'),
			'desc_check_multiple_extensions'		=> wp_kses_post(__('Uploading files with multiple extensions might trick your or any other user that the file has a safe extension.','rsfirewall')),
			'lbl_check_known_malware'				=> esc_html__('Check for known malware', 'rsfirewall'),
			'desc_check_known_malware'				=> wp_kses_post(__('Verify uploaded files for known malware patterns, such as PHP shell scripts.','rsfirewall')),
			'lbl_banned_extensions'					=> esc_html__('Banned extensions', 'rsfirewall'),
			'desc_banned_extensions'				=> wp_kses_post(__('Files with the following extensions will be deleted as soon as they\'ve been uploaded to the temporary directory on your server. If you enable the &quot;Multiple extensions check&quot;, this will check all the files extensions, as opposed to the last one.','rsfirewall')),

			'lbl_lockdown'							=> esc_html__('Lockdown', 'rsfirewall'),
			'lbl_protect_users'						=> esc_html__('Protect the following administrator users from any change', 'rsfirewall'),
			'desc_protect_users'					=> wp_kses_post(__('This will create a snapshot of the selected administrator users. If any changes will happen to any of them, it will get reverted back immediately. If you want to update your snapshot, you will have to deselect all the users, press \'Save Changes\' and then select the users again and press \'Save Changes\' one more time.','rsfirewall')),
			'lbl_disable_wp_installer'				=> esc_html__('Disable access to the WordPress plugin and theme installer', 'rsfirewall'),
			'desc_disable_wp_installer'				=> wp_kses_post(__('By setting this to Yes, the WordPress plugin and theme installer will no longer be accessible to other users except administrators <em>(if by any chance they have the install_plugins and install_themes capabilities)<em>.','rsfirewall')),
			'lbl_disable_admin_creation'			=> esc_html__('Disable the creation of new Administrators', 'rsfirewall'),
			'desc_disable_admin_creation'			=> wp_kses_post(__('By setting this to yes, new users that can login in the /wp-admin section <strong><em>will be deleted</strong></em> as soon as they are created. Keep in mind that new users (with roles other then \'Administrator\') will not be affected, unless you are trying to add \'Administrator\' role to them (in this case, they will be deleted as well).','rsfirewall')),

			'lbl_logging'							=> esc_html__('Logging Utility', 'rsfirewall'),
			'lbl_email_addresses'					=> esc_html__('Email address(es)', 'rsfirewall'),
			'desc_email_addresses'					=> wp_kses_post(__('Send an email to the following email address(es) when an event occurs. Please specify every address on a new line if you would like multiple email addresses to be notified.','rsfirewall')),
			'lbl_include_alert_levels'				=> esc_html__('Include these alert levels', 'rsfirewall'),
			'desc_include_alert_levels'				=> wp_kses_post(__('Please tick the levels for which you would like to receive email notifications.','rsfirewall')),
			'lbl_log_history'						=> esc_html__('# days to keep log history', 'rsfirewall'),
			'desc_log_history'						=> wp_kses_post(__('Log entries older than this number of days will be automatically deleted, to keep the database fresh and prevent it from overloading.','rsfirewall')),
			'lbl_emails_per_hour'					=> esc_html__('# emails per hours', 'rsfirewall'),
			'desc_emails_per_hour'					=> wp_kses_post(__('This is the maximum number of emails that will be sent to the specified addresses within an hour. Set this to a lower number if your inbox is spammed during attacks or if your hosting provider allows only a certain amount of emails to be sent every hour.','rsfirewall')),
			'lbl_events_to_show'					=> esc_html__('# events to show', 'rsfirewall'),
			'desc_events_to_show'					=> wp_kses_post(__('Number of events that will be shown in the Dashboard.','rsfirewall')),
			'lbl_ipv4_whois_service'				=> esc_html__('IPv4 Whois Service', 'rsfirewall'),
			'desc_ipv4_whois_service'				=> wp_kses_post(__('This will appear as a link on the IP in the Threats area. You can pass the {ip} placeholder to grab the IP address. Works only for IPv4 addresses.','rsfirewall')),
			'lbl_ipv6_whois_service'				=> esc_html__('IPv6 Whois Service', 'rsfirewall'),
			'desc_ipv6_whois_service'				=> wp_kses_post(__('This will appear as a link on the IP in the Threats area. You can pass the {ip} placeholder to grab the IP address. Works only for IPv6 addresses.','rsfirewall')),

			'lbl_import'							=> esc_html__('Import', 'rsfirewall'),
			'lbl_upload_config'						=> esc_html__('Upload configuration .json', 'rsfirewall'),
			'desc_upload_config'					=> wp_kses_post(__('This allows you to restore a previously exported RSFirewall! Configuration.','rsfirewall')),
			'lbl_upload_license'					=> esc_html__('Upload license code from the configuration .json', 'rsfirewall'),
			'desc_upload_license'					=> wp_kses_post(__('By setting this to Yes the Update Code from the configuration.json file will also be taken into account. Set this to No if you want to keep the code you\'ve written in your current config.','rsfirewall')),

			'lbl_updates'							=> esc_html__('Updates', 'rsfirewall'),
			'lbl_code'								=> esc_html__('License Key', 'rsfirewall'),
			'desc_code'								=> wp_kses_post(__('Please insert your code so that you will have access to updates and support.','rsfirewall')),

			'global_checkall' 						=> esc_html__('Check All', 'rsfirewall'),

			'option_other' 							=> esc_html__('Other', 'rsfirewall'),
			'option_anonymous_proxy' 				=> esc_html__('Anonymous Proxy', 'rsfirewall'),
			'option_other_ountry' 					=> esc_html__('Other Country', 'rsfirewall'),
			'option_url_data_get'					=> esc_html__('URL data (GET)', 'rsfirewall'),
			'option_form_data_post'					=> esc_html__('Form data (POST)', 'rsfirewall'),
			'option_empty_user_agent'				=> esc_html__('Empty User Agents', 'rsfirewall'),
			'option_mozzila_impersonators'			=> esc_html__('Mozilla Impersonators', 'rsfirewall'),
			'option_low'							=> esc_html__('low', 'rsfirewall'),
			'option_medium'							=> esc_html__('medium', 'rsfirewall'),
			'option_high'							=> esc_html__('high', 'rsfirewall'),
			'option_critical'						=> esc_html__('critical', 'rsfirewall'),

			'option_safebrowsing'					=> esc_html__('Google Safe Browsing API', 'rsfirewall'),
			'option_webrisk'						=> esc_html__('Google Web Risk API', 'rsfirewall'),
		);

		$form_exceptions = array(
			'lbl_exception_type' 					=> esc_html__('Exception Type', 'rsfirewall'),
			'lbl_use_regex' 						=> esc_html__('Use regular expressions', 'rsfirewall'),
			'desc_use_regex'						=> wp_kses_post(__('You can use regular expressions to match an exception eg. utm_source=(.*?). If set to No, the exception will match the string given here by using a simple string comparison so please make sure to add the full string you are expecting.','rsfirewall')),
			'lbl_match' 							=> esc_html__('Match', 'rsfirewall'),
			'desc_match' 							=> wp_kses_post(__('Please specify either the full string to match or a regular expression (if regular expressions are used).', 'rsfirewall')),
			'lbl_php' 								=> esc_html__('Skip PHP Protections', 'rsfirewall'),
			'desc_php' 								=> wp_kses_post(__('Set this to Yes if you would like to skip PHP protections.', 'rsfirewall')),
			'lbl_sql' 								=> esc_html__('Skip SQL Protections', 'rsfirewall'),
			'desc_sql' 								=> wp_kses_post(__('Set this to Yes if you would like to skip SQL protections.', 'rsfirewall')),
			'lbl_js' 								=> esc_html__('Skip JS Protections', 'rsfirewall'),
			'desc_js' 								=> wp_kses_post(__('Set this to Yes if you would like to skip JS protections.', 'rsfirewall')),
			'lbl_uploads' 							=> esc_html__('Skip Upload Protections', 'rsfirewall'),
			'desc_uploads' 							=> wp_kses_post(__('Set this to Yes if you would like to skip upload protections.', 'rsfirewall')),
			'lbl_reason' 							=> esc_html__('Reason', 'rsfirewall'),
			'desc_reason' 							=> wp_kses_post(__('This reason is only for internal use - it is not displayed anywhere on your website.', 'rsfirewall')),

			'option_user_agent'						=> esc_html__('User Agent', 'rsfirewall'),
			'option_url'							=> esc_html__('URL', 'rsfirewall'),
		);

		$form_feeds = array(
			'lbl_url' 								=> esc_html__('RSS Feed URL', 'rsfirewall'),
			'desc_url' 								=> wp_kses_post(__('This is the URL of the feed.', 'rsfirewall')),
			'lbl_limit' 							=> esc_html__('Entries to show', 'rsfirewall'),
			'desc_limit'							=> wp_kses_post(__('This is the numbers of entries to show (most recent entries will be shown)','rsfirewall')),

		);

		$form_lists = array(
			'lbl_ip' 								=> esc_html__('IP Address', 'rsfirewall'),
			'desc_ip' 								=> wp_kses_post(__('Add a new IP address to the list. You can use wildcards anywhere, for example 192.168.1.* will match any address from 192.168.1.1 to 192.168.1.254.', 'rsfirewall')),
			'lbl_type' 								=> esc_html__('List Type', 'rsfirewall'),
			'desc_type'								=> wp_kses_post(__('Set the type of the list - blocklist (banned) or safelist (no protections triggered).','rsfirewall')),
			'lbl_reason' 							=> esc_html__('Reason', 'rsfirewall'),
			'desc_reason'							=> wp_kses_post(__('This reason will show up to the attacker when he visits your site and finds himself banned.','rsfirewall')),

			'option_whitelist'						=> esc_html__('Safelist', 'rsfirewall'),
			'option_blacklist'						=> esc_html__('Blocklist', 'rsfirewall'),
		);
		
		$menus_strings = array(
			'page_title_dashboard' 					=>esc_html__('Dashboard', 'rsfirewall'),
			'menu_title_dashboard' 					=>esc_html__('RSFirewall!', 'rsfirewall'),

			'page_title_check' 						=>esc_html__('System Check', 'rsfirewall'),
			'menu_title_check' 						=>esc_html__('System Check', 'rsfirewall'),

			'page_title_dbcheck' 					=>esc_html__('Database Check', 'rsfirewall'),
			'menu_title_dbcheck' 					=>esc_html__('Database Check', 'rsfirewall'),

			'page_title_configuration' 				=>esc_html__('Firewall Configuration', 'rsfirewall'),
			'menu_title_configuration' 				=>esc_html__('Configuration', 'rsfirewall'),

			'page_title_diff' 						=>esc_html__('File Differences', 'rsfirewall'),
			'menu_title_diff' 						=>esc_html__('File Differences', 'rsfirewall'),

			'page_title_exceptions' 				=>esc_html__('Exceptions', 'rsfirewall'),
			'menu_title_exceptions' 				=>esc_html__('Exceptions', 'rsfirewall'),

			'page_title_feeds' 						=>esc_html__('RSS Feeds', 'rsfirewall'),
			'menu_title_feeds' 						=>esc_html__('RSS Feeds', 'rsfirewall'),

			'page_title_file' 						=>esc_html__('File Contents', 'rsfirewall'),
			'menu_title_file' 						=>esc_html__('File Contents', 'rsfirewall'),

			'page_title_folders' 					=>esc_html__('Folders', 'rsfirewall'),
			'menu_title_folders' 					=>esc_html__('Folders', 'rsfirewall'),

			'page_title_ignored' 					=>esc_html__('Ignored Files', 'rsfirewall'),
			'menu_title_ignored' 					=>esc_html__('Ignored Files', 'rsfirewall'),

			'page_title_lists' 						=>esc_html__('Blocklist / Safelist', 'rsfirewall'),
			'menu_title_lists' 						=>esc_html__('Blocklist / Safelist', 'rsfirewall'),

			'page_title_threats' 					=>esc_html__('Threats', 'rsfirewall'),
			'menu_title_threats' 					=>esc_html__('Threats', 'rsfirewall'),
		);

        return ${$array};
    }
}