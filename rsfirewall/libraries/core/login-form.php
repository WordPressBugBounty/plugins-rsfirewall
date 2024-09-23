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

class RSFirewall_Core_Login_Form extends RSFirewall_Core {
    protected $is_woocommerce = false;
    protected $is_ultimate_m = false;
    protected $is_lrm = false;
    static $is_ultimate_m_captcha_error = false;

    /**
     * @return RSFirewall_Core_Login_Form
     * @since 1.0.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall_Core_Login_Form();
        }

        return $inst;
    }

    /**
     * Function to init the hooks used in the login form, only if  the page is wp-login
     */
    public function init() {
        global $pagenow;
        // init the authentication hooks and process
        add_filter( 'wp_authenticate_user', array($this, 'login_authenticate'), 10, 2);
        add_action( 'login_form', array($this, 'generate_and_check_captcha') );
        add_action( 'wp_login_failed', array($this, 'capture_failure'), 10, 1);

        /// Ultimate Member plugin Hooks
        $this->is_ultimate_m = $pagenow != 'wp-login.php' && !$this->is_admin && $this->is_plugin_active('ultimate-member/ultimate-member.php');
        if ($this->is_ultimate_m) {
           add_filter('um_after_login_fields', array($this, 'generate_and_check_captcha'), 10);
        }

        // Check if is Woocommerce
        $this->is_woocommerce = $pagenow != 'wp-login.php' && !$this->is_admin && $this->is_plugin_active('woocommerce/woocommerce.php');
        if ($this->is_woocommerce) {
            add_action('woocommerce_login_form', array($this, 'generate_and_check_captcha'), 10);
        }

        // Check for LRM
        $this->is_lrm = $pagenow != 'wp-login.php' && $this->is_plugin_active('ajax-login-and-registration-modal-popup/ajax-login-registration-modal-popup.php');
        if ($this->is_lrm) {
            add_action('lrm/login_fail', array($this, 'check_and_enable_captcha'), 10, 1);
            add_action('lrm_login_form', array($this, 'load_the_ajax_monitor'), 10);
        }


        // clear data when the user is successful logged in (must not send arguments)
        add_action( 'wp_login', array($this, 'clear_offender'), 10, 0);
    }

    public function check_if_captcha_available() {
        $enabled_captcha             = (int) $this->get_config( 'enable_captcha' );
        $captcha_site_key            = $this->get_config( 'recaptcha_site_key' );
        $captcha_secret_key          = $this->get_config( 'recaptcha_secret_key' );


        if (!$enabled_captcha || !isset( $captcha_secret_key ) || !isset( $captcha_site_key )) {
            return false;
        }

        return true;
    }

    public function check_and_enable_captcha($user_signon) {
        if (!$this->check_if_captcha_available()) {
            return;
        }

        $offenders = $this->count_offenders();
        $attempts  = (int) $this->get_config( 'autoban_captcha_attempts' );
        if ($offenders >= $attempts) {
            wp_send_json_error(array('message'=>implode('<br/>', $user_signon->get_error_messages()), 'rsf_captcha'=>'<div class="g-recaptcha" data-sitekey="' . RSFirewall_Config::get( 'recaptcha_site_key' ) . '"></div>'));
        }
    }

    public function load_the_ajax_monitor() {
        wp_enqueue_script('rsfirewall-ajax-monitor', RSFIREWALL_URL . 'assets/js/majax.js', array('jquery'), $this->version);

        if (!$this->check_if_captcha_available()) {
            return;
        }

        $offenders = $this->count_offenders();
        $attempts  = (int) $this->get_config('autoban_captcha_attempts');
        if ($offenders >= $attempts) {
            $this->generate_captcha();
        }
    }

    /**
     * Function that checks if the captcha is enabled and outputs the captcha field
     */
    public function generate_and_check_captcha() {
        $attempts                    = (int) $this->get_config( 'autoban_captcha_attempts' );
        $enabled_captcha             = (int) $this->get_config( 'enable_captcha' );
        $captcha_site_key            = $this->get_config( 'recaptcha_site_key' );
        $captcha_secret_key          = $this->get_config( 'recaptcha_secret_key' );

        $offenders = $this->count_offenders();
        if ($offenders >= $attempts && $enabled_captcha && ( isset( $captcha_secret_key ) && isset( $captcha_site_key ))) {
           $this->generate_captcha();
        }
    }

    /**
     * Function that captures the login failure (Except the TFA errors)
     * @param null $username
     */
    public function capture_failure($username = null) {
        // Skip this part if the failure was generated due to TFA errors
        if (class_exists('RSFirewall_Core_Tfa') && RSFirewall_Core_Tfa::$already_logged) {
            return;
        }

        $this->login_failed_attempt($username);

        $enable_auto_blacklist_admin = (int) $this->get_config( 'enable_auto_blacklist_for_admin', false );
        $auto_blacklist              = $this->get_config( 'autoban_attempts' );

        $offenders = $this->count_offenders();
        if ( $enable_auto_blacklist_admin && ( $offenders >= (int) $auto_blacklist ) ) {
            $this->too_many_login_attempts();
        }
    }

    /**
     * The wp_authenticate hook callback that checks for the login attempts and enables the captcha if necessary
     * @param $user
     * @param $password
     * @return $user object
     */
    public function login_authenticate($user, $password = null){
        /**
         *
         * $attempts                    = the number of failed attempts before captcha is rendered
         * $auto_blacklist              = the number of failed attempts before the IP is blocklisted
         * $enable_auto_blacklist_admin = enable autoblacklisting for failed logins
         * $enabled_captcha             = enable captcha for the administrator login form
         * $captca_site_key | $captcha_secret_key = google recaptcha credentials
         *
         */

        $attempts                    = (int) $this->get_config( 'autoban_captcha_attempts' );
        $enabled_captcha             = (int) $this->get_config( 'enable_captcha' );
        $captcha_site_key            = $this->get_config( 'recaptcha_site_key' );
        $captcha_secret_key          = $this->get_config( 'recaptcha_secret_key' );

        $offenders = $this->count_offenders();

        // validate the captcha field
        if ($offenders >= $attempts && $enabled_captcha && ( isset( $captcha_secret_key ) && isset( $captcha_site_key ))) {
            $user = $this->validate_captcha_field($user, $password);
            return $user;
        }

        return $user;
    }

    /**
     * Function to handle login failed attempts
     *
     * @since 1.0.0
     */
    public function login_failed_attempt( $username ) {

        $this->capture_offender();

        $capture_login_attempts = (int) $this->get_config( 'capture_login_attempts');

        if ( $capture_login_attempts ) {
            $args = array('username' => $username);
            // Check if we are allowed to store the password
            $capture_login_attempts_password = (int) $this->get_config( 'capture_login_attempts_password');
            if ($capture_login_attempts_password) {
                $pass = '';
                // Some plugins use the name "password"
                if (!empty($_POST['password'])) {
                    $pass = $_POST['password'];
                } else if (!empty($_POST['pwd'])) {
                    $pass = $_POST['pwd'];
                }

                // the Ultimate Member plugin uses a suffix for the post password field
                if (strlen($pass) == 0 && $this->is_ultimate_m && isset($_POST['form_id']) && isset($_POST['user_password-'.$_POST['form_id']])) {
                    $pass = $_POST['user_password-'.$_POST['form_id']];
                }

                $args['password'] = $pass;
            }

            $this->capture_last_login($args);
        }
    }

    /**
     * Add an ip to the blocklist
     */
    public function too_many_login_attempts() {
        $data = array(
            'reason'  => esc_html__( 'Too many login attempts', 'rsfirewall' ),
            'date'    => current_time( 'mysql' ),
            'enabled' => 1
        );

        $this->add_to_blacklist( $data );

        $log = array(
            'level'           => 'critical',
            'code'            => 'IP_BLACKLISTED',
            'debug_variables' => $this->ip
        );

        $this->logger->add($log)->save();
        $this->clear_offender();
    }

    /**
     * Generates the captcha
     *
     * @since 1.0.0
     * @throws Exception
     */
    public function generate_captcha() {
        wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array(), '', false );

        $extra_style = '';
        if ($this->is_ultimate_m) {
            $extra_style .= ' style="margin-bottom: 30px;"';
        }

        echo '<style>#login{ width: 350px; }</style>';
        echo '<div class="g-recaptcha" data-sitekey="' . RSFirewall_Config::get( 'recaptcha_site_key' ) . '"'.$extra_style.'></div>';
    }

    /**
     * Validates the captcha
     *
     * @since 1.0.0
     * @throws Exception
     */
    public function validate_captcha_field($user, $password = null) {
        if (empty($_POST['g-recaptcha-response'])) {
            return new WP_Error('empty_captcha', esc_html__('reCAPTCHA should not be empty', 'rsfirewall'));
        }

        // Params for response verification
        $params = array(
            'secret' => RSFirewall_Config::get('recaptcha_secret_key'),
            'response' => $_POST['g-recaptcha-response'],
            'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        );

        // Verify ReCAPTCHA response
        $response = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?' . http_build_query($params));

        // In case the connection fails
        if ($response === false) {
            return new WP_Error('verification_failed', esc_html__('Unable to connect to the server to verify the reCAPTCHA challenge. Please try again.', 'rsfirewall'));
        }

        // Decode the response
        $captcha_response = json_decode($response['body'], true);

        // In case decoding fails
        if ($captcha_response === false) {
            return new WP_Error('decode_error', esc_html__('Unable to decode the server response. Please try again.', 'rsfirewall'));
        }

        // The challenge failed, show an error
        if ($captcha_response['success'] !== true) {
            if (isset($captcha_response['error-codes'])) {
                foreach ($captcha_response['error-codes'] as $code) {
                    switch ($code) {
                        case 'missing-input-secret':
                            return new WP_Error('secret_missing', esc_html__('The secret parameter is missing.', 'rsfirewall'));
                            break;

                        case 'invalid-input-secret':
                            return new WP_Error('secret_invalid', esc_html__('The secret parameter is invalid or malformed.', 'rsfirewall'));
                            break;

                        case 'missing-input-response':
                            return new WP_Error('response_missing', esc_html__('The response parameter is missing.', 'rsfirewall'));
                            break;

                        case 'invalid-input-response':
                            return new WP_Error('response_invalid', esc_html__('The response parameter is invalid or malformed.', 'rsfirewall'));
                            break;
                    }
                }
            } else {
                return new WP_Error('response_robot', esc_html__('reCAPTCHA response said you are a robot - please try again.', 'rsfirewall'));
            }
        }

        return $user;
    }

    /**
     * Captures the last login attempt
     *
     * @since 1.0.0
     * @throws Exception
     */
    public function capture_last_login( $params ) {

        $args = array(
            'level'           => 'critical',
            'username'        => $params['username'],
            'code'            => 'LOGIN_FAILED_ATTEMPT',
        );

        // Different message if the password is provided
        if (isset($params['password'])) {
            // Check if it's a captcha error
            $user = get_user_by( is_email( $params['username'] ) ? 'email' : 'login', $params['username'] );
            if ( $user && wp_check_password( $params['password'], $user->data->user_pass, $user->ID ) ) {
                $args['code']            = 'LOGIN_FAILED_ATTEMPT_CAPTCHA';
            } else {
                $args['code']            = 'LOGIN_FAILED_ATTEMPT_PASSWORD';
                $args['debug_variables'] = empty($params['password']) ? 'none' : $params['password'];
            }
        }

        $this->logger->add($args)->save();
    }


    /**
     * Initiate the reCaptcha for the comments
     *
     * @since 1.1.7
     */
    public function init_comment_captcha() {
        $captcha_site_key            = $this->get_config( 'recaptcha_site_key' );
        $captcha_secret_key          = $this->get_config( 'recaptcha_secret_key' );

        // check to see if the keys are present
        if (!isset($captcha_site_key) || !isset($captcha_secret_key)) {
            return;
        }

        // Only for non logged in users
        if (!is_user_logged_in()) {
            // Add the field filter hook
            add_filter('comment_form_fields', array($this, 'comment_form_fields'));

            // Process the recaptcha hook
            add_filter('pre_comment_approved', array($this, 'pre_comment_approved'), 10 ,2);
        }
    }

    /**
     * Function that is called in the hook that displays the captcha field
     *
     * @since 1.1.7
     */
    public function comment_form_fields($fields) {
        //add the recaptcha field
        if (!isset($fields['rsf_captcha'])) {
            wp_enqueue_script( 'recaptcha', 'https://www.google.com/recaptcha/api.js', array(), '', false );

            $fields['rsf_captcha'] =  '<div class="g-recaptcha" data-sitekey="' . RSFirewall_Config::get( 'recaptcha_site_key' ) . '"></div>';
        }

        return $fields;
    }

    /**
     * Function that is called in the hook that verifies if the captcha has been checked
     *
     * @since 1.1.7
     */
    public function pre_comment_approved($approved, $commentdata) {
        $validate_captcha = $this->validate_captcha_field(true);
        if (is_wp_error($validate_captcha)) {
            wp_die($validate_captcha->get_error_message(), __( 'Comment Submission Failure' ), array( 'back_link' => true ));
        }

        return $approved;
    }

    /**
     * Initiate the reCaptcha for the register forms
     *
     * @since 1.1.7
     */
    public function init_register_captcha() {
        $captcha_site_key            = $this->get_config( 'recaptcha_site_key' );
        $captcha_secret_key          = $this->get_config( 'recaptcha_secret_key' );

        // check to see if the keys are present
        if (!isset($captcha_site_key) || !isset($captcha_secret_key)) {
            return;
        }


        // Add the field filter hook (we can use the generate function from the login)
        add_action('register_form', array($this, 'generate_captcha'));

        // Process the reCaptcha hook
        add_filter('registration_errors', array($this, 'registration_errors'), 10 ,3);

        // Add/process the captcha to the registration form of woocommerce
        if ($this->is_woocommerce) {
            add_action('woocommerce_register_form', array($this, 'generate_captcha'), 10);
            add_filter('woocommerce_process_registration_errors', array($this, 'registration_errors'), 10, 4);
        }

        if ($this->is_lrm) {
            add_action('lrm_register_form', array($this, 'generate_captcha'), 10);
        }

        if ($this->is_ultimate_m) {
            add_filter('um_after_register_fields', array($this, 'generate_captcha'), 10);
            add_filter('um_after_register_fields', array($this, 'um_show_captcha_error'), 11);
            add_action('um_submit_form_register', array($this, 'um_check_captcha'), 9 );
        }
    }

    /**
     * Capture the registration reCAPTCHA errors
     *
     * @since 1.1.7
     */
    public function registration_errors($errors, $sanitized_user_login, $user_email, $woo_email = null) {
        // if there is no error present
        if (is_wp_error($errors) && empty($errors->errors)) {
            $validate_captcha = $this->validate_captcha_field(true);

            if (is_wp_error($validate_captcha)) {
                if ($this->is_woocommerce && !is_null($woo_email)) {
                    return $validate_captcha;
                }
                else {
                    $errors->add($validate_captcha->get_error_code(), $validate_captcha->get_error_message());
                }
            }
        }

        return $errors;
    }

    // Special functions for the Ultimate Member plugin for the registration process

    /**
     * Check the reCAPTCHA and stop the registration for the Ultimate Member plugin
     *
     * @since 1.1.7
     */
    public function um_check_captcha() {
        // check if the global function for the UM class is present
        if (isset($GLOBALS['ultimatemember'])) {
            if ( !isset( $GLOBALS['ultimatemember']->form()->errors ) ) {
                $validate_captcha = $this->validate_captcha_field(true);
                if (is_wp_error($validate_captcha)) {
                    self::$is_ultimate_m_captcha_error = true;
                    $GLOBALS['ultimatemember']->form()->add_error($validate_captcha->get_error_code(), $validate_captcha->get_error_message());
                }
            }
        }
    }

    /**
     * After the registration process has been stopped due to a reCAPTCHA error, need to display the error - only for Ultimate Member plugin
     *
     * @since 1.1.7
     */
    public function um_show_captcha_error() {
        if (isset($GLOBALS['ultimatemember']) && self::$is_ultimate_m_captcha_error) {

            // display only these errors
            $accepted_errors = array(
                'empty_captcha',
                'verification_failed',
                'decode_error',
                'secret_missing',
                'response_missing',
                'secret_invalid',
                'response_invalid',
                'response_robot'
            );

            foreach($accepted_errors as $captcha_error) {
                if ($GLOBALS['ultimatemember']->form()->has_error($captcha_error)) {
                    $error_message = $GLOBALS['ultimatemember']->form()->errors[$captcha_error];

                    echo '<p class="um-notice err um-error-code-' . esc_attr($captcha_error) . '"><i class="um-icon-ios-close-empty" onclick="jQuery(this).parent().fadeOut();"></i>' . $error_message . '</p>';
                    break;
                }
            }
        }
    }
}