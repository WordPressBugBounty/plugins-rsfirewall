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

class RSFirewall_Core_Vulnerabilities extends RSFirewall_Core {
    /**
     * Holds the vulnerabilities checks for the WordPress core
     */
    protected $callbacks = array(
        'arbitrary_file_deletion',
		'wp_gdpr_compliance_plugin',
		'stop_unwanted_admins'
    );
	
	 /**
     * Holds the list of unwanted admins names reported by different WordPress vulnerabilities
     */
    public static $unwanted_admins = array(
        't2trollherten'
    );

    /**
     * @return RSFirewall_Core_Vulnerabilities
     * @since 1.1.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall_Core_Vulnerabilities();
        }

        return $inst;
    }

    /**
     * Initiate the scanner for the vulnerabilities after the config was generated
     */
    public function init() {
        if (!empty($this->callbacks)) {
            foreach ($this->callbacks as $check) {
                if (method_exists($this, $check)) {
                    $this->call_method($check);
                }
            }
        }
    }

    /**
     * Shows the forbidden message and count the attempt for autoban purpose
     *
     * @since  1.1.0
     * @access protected
     */
    protected function show_forbidden_message($count = true, $message = '') {
        if ($this->is_bot()) {
            return;
        }

        $this->logger->save();

        $message = !empty($message) ? $message : $this->reason;

        if ($this->get_config('enable_auto_blacklist') && $count) {
            $this->count_autoban(esc_html__('Repeat offender (Autobanned)', 'rsfirewall'));
        }

        wp_die( $message , 403);
    }

    /**
     * Function to prevent the Arbitrary File Deletion Vulnerability Exploit used in post attachment thumb (until 4.9.6)
     * This exploit could be prevented also by enabling Local File Inclusion for $_POST variables in admin area
     */
    public function arbitrary_file_deletion() {
        global $wp_version;
        if ($this->is_admin && version_compare($wp_version, '4.9.6', '<=')) {
            global $pagenow;

            // Post related vulnerabilities
            if ($pagenow == 'post.php') {
                //Handle the Arbitrary File Deletion
                if (isset($_POST['action']) && $_POST['action'] == 'editattachment' && isset($_POST['thumb'])) {
                    $thumb_name = $_POST['thumb'];
                    if (basename($thumb_name) != $thumb_name) {

                        $args = array(
                            'level'           => 'critical',
                            'code'            => 'ARBITRARY_FILE_DELETION',
                            'debug_variables' => $thumb_name
                        );

                        $this->logger->add( $args );

                        $this->reason = esc_html__('WordPress Arbitrary File Deletion Vulnerability Exploit.', 'rsfirewall');
                        $this->show_forbidden_message();
                    }
                }
            }
        }
    }
	
	/**
     * Function to prevent the WP GDPR Compliance vulnerability exploit used in post option changes without proper capabilites check
     * Vulnerability explained here https://wpvulndb.com/vulnerabilities/9144
     */
    public function wp_gdpr_compliance_plugin() {
        try {
            // check if the user can change the options, if not show forbidden message
            if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['action']) && $_POST['action'] == 'wpgdprc_process_action' && isset($_POST['data'])) {
                // the vulnerability can work even if its a json object or simply an array
                if (!is_array($_POST['data'])) {
                    $data = @json_decode(stripslashes($_POST['data']), true);
                } else {
                    $data = $_POST['data'];
                }

                if (isset($data['type']) && $data['type'] == 'save_setting' && !current_user_can('manage_options')) {
                    $details = sprintf(esc_html__('Option: %s, Value: %s', 'rsfirewall'), $data['option'], $data['value']);
                    $args = array(
                        'level'           => 'critical',
                        'code'            => 'WP_GDPR_COMPLIANCE_VULNERABILITY_EXPLOIT',
                        'debug_variables' => $details
                    );

                    $this->logger->add( $args );

                    $this->reason = esc_html__('WP GDPR Compliance vulnerability exploit.', 'rsfirewall');
                    $this->show_forbidden_message();
                }
            }
        } catch (Exception $e) {
            /// ignore
        }
    }
	
	/**
     * Function to stop unwanted administrators to login (potential threats)
     */
    public function stop_unwanted_admins() {
        add_filter( 'wp_authenticate_user', array($this, 'stop_authenticate'), 9, 2);
    }

    public function stop_authenticate($user, $password = null) {
		// if is not an administrator return the user
        if (is_wp_error($user) || !in_array('administrator', $user->roles)) {
            return $user;
        }
        
        // check the list
        foreach(self::$unwanted_admins as $u_admin) {
            if (stripos($user->data->user_login, $u_admin) !== false) {
                $args = array(
                    'level' => 'critical',
                    'code' => 'FLAGGED_ADMINISTRATOR_USER_AUTHENTICATE',
                    'debug_variables' => $user->data->user_login
                );

                $this->logger->add($args);

                $this->reason = esc_html__('Your user has been detected as a possible threat. If this is not the case please contact the administrator!', 'rsfirewall');
                $this->show_forbidden_message();
            }
        }

        // if everything goes well return the user
        return $user;
    }
}