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

class RSFirewall_Core_Backend_Password extends RSFirewall_Core {
    protected $cookie_hash;
    protected $is_ssl;
    protected $blog_id;
    /**
     * @return RSFirewall_Core_Backend_Password
     * @since 1.0.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall_Core_Backend_Password();
        }

        return $inst;
    }

    public function __construct() {
        parent::__construct();

        $this->is_ssl = is_ssl();

        $this->blog_id = 'rsf_';
        if (is_multisite()) {
            $this->blog_id .= get_current_blog_id();
        }
        // build the hash as unique as possible, signed with the site secret (and the backend password) so it cannot be forged
        $this->cookie_hash = hash_hmac('sha256', $this->blog_id.'|'.$this->plugin_name.'|'.$this->version.'|'.$this->ip.'|'.$this->agent.'|'.RSFirewall_Config::get('type_password', ''), wp_salt('auth'));

        $this->blog_id = md5($this->blog_id);
    }

    /**
     * Initiate the backend login
     */
    public function init() {
        // if the ip is whitelisted or an AJAX call is performed do not show the login
        if ($this->is_whitelisted() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        global $pagenow;

        // Hold the current url in case this is not wp-login/wp-admin/admin
        $current_url = $this->get_current_url();

        if ($is_enabled = $this->get_config( 'enable_backend' )) {

            // Add the logout action hook
            add_action('wp_logout', array($this, 'delete_login_cookie'));

            // Let's determine if is the login page or the admin area
            if ($pagenow != 'wp-login.php' && !$this->is_admin){
                return;
            }
			
			$is_user_logged_in = is_user_logged_in();
            // the case when the user wants to logout using the normal wp-login
            if ($pagenow == 'wp-login.php' && $is_user_logged_in && isset($_REQUEST['action']) && $_REQUEST['action'] == 'logout') {
                return;
            }

            // some logins from the front area have the action set to the wp-login.php file, so when the login action is triggered need to add this exception
            if ($this->detect_front_login()) {
                // the referer can be changed, so privileged accounts must still pass the backend password
                add_filter('authenticate', array($this, 'check_front_login_role'), 100);
                return;
            }

            // Set the return URL
            if (stripos($current_url, 'wp-login.php') === false) {
                $return_url = $current_url;
            } else {
                $return_url = admin_url();
            }


            // Need to check if is in admin area and the user is logged in (workaround for other login plugins, because they log in the user themselves)
            if ($this->is_admin && $is_user_logged_in) {
                $user = wp_get_current_user();
                $roles = $user->roles;

                // apply backend role filter in case other plugins want to add the backend login for other roles
                $check_roles = apply_filters('rsfirewall_backend_login_roles', array('administrator'));

                if (!array_intersect($check_roles, $roles)) {
                    return;
                }
            }

            // Show the Backend Password
            if (!$this->has_valid_cookie()) {
                if ($this->is_blacklisted())
                {
                    $message = wp_kses_post( __('Your IP Address is blocked. <br /> Reason: Too many backend password login attempts <br /> Please contact an administrator.', 'rsfirewall' ));
                    wp_die( $message , 403);
                }
                if ( isset( $_POST['rsf_backend_password'] ) && strlen( $_POST['rsf_backend_password'] ) ) {
                    $redirect = false;
                    if (hash_equals((string) RSFirewall_Config::get('type_password', ''), md5(wp_unslash($_POST['rsf_backend_password'])))) {
                        $this->set_login_cookie($this->cookie_hash, 0);
                        $redirect = true;
                    } else {
                        $args = array(
                            'level'           => 'medium',
                            'code'            => 'BACKEND_LOGIN_ERROR',
                            'debug_variables' => '',
                        );

                        RSFirewall_Helper::$error_type = 'error';
                        RSFirewall_Helper::$message = esc_html__( 'The password you have entered is not correct.', 'rsfirewall' );
						
						// Add to the log
						$this->logger->add($args)->save();

                        $this->capture_failure();
                    }

                    // Redirect after we store the log if the login was successful
                    if ($redirect)
					{
                        wp_redirect( $return_url );
                        exit;
                    }
                }

                // And if the user is not logged in show the form
                if (!$this->has_valid_cookie())
                {
                   $this->show_backend_login();
                }
            }
        }
    }

    /**
     * Function that captures the backend password login failure
     */
    public function capture_failure() {

        $this->capture_offender();

        $enable_auto_blacklist_admin = (int) $this->get_config( 'enable_auto_blacklist_for_admin', false );
        $auto_blacklist              = $this->get_config( 'autoban_attempts' );

        $offenders = $this->count_offenders();
        if ( $enable_auto_blacklist_admin && ( $offenders >= (int) $auto_blacklist ) ) {
            $this->too_many_login_attempts();
        }
    }

    /**
     * Add an ip to the blocklist
     */
    public function too_many_login_attempts() {
        $data = array(
            'reason'  => esc_html__( 'Too many backend password login attempts.', 'rsfirewall' ),
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
     * Detect if a login has been triggered from the front area calling upon the wp-login.php, checking the fererer for internal call
     */
	protected function detect_front_login() {
        global $pagenow;

        // check if its a login request action
        if ($pagenow == 'wp-login.php' && !isset($_REQUEST['action']) && !empty($this->referer)) {

            // because there is not defined an actual 'action' for the login action we need to check the user and password
            if (!isset($_REQUEST['log']) || strlen($_REQUEST['log']) === 0) {
                return false;
            }

            if (!isset($_REQUEST['pwd']) || strlen($_REQUEST['pwd']) === 0) {
                return false;
            }

            // parse the referer and compare the exact host
            $referer = wp_parse_url($this->referer);
            if (empty($referer['scheme']) || empty($referer['host']) || !in_array(strtolower($referer['scheme']), array('http', 'https'), true)) {
                return false;
            }

            // build the exceptions for the referer check
            $site_url = get_site_url();
            $site_url = RSFirewall_Helper::parse_url($site_url);

            $domain = strtolower($site_url['host']);

            $exceptions = array($domain);

            // add the non www version too
            if (strpos($domain, 'www.') === 0) {
                $exceptions[] = substr($domain, 4);
            } else {
                // if the domain does not have www check for this to
                $exceptions[] = 'www.'.$domain;
            }

            // check the referer (allow only from the site/frontend)
            return in_array(strtolower($referer['host']), $exceptions, true);
        }

        return false;
    }

    /**
     * Block the front login bypass for the roles that must pass the backend password
     */
    public function check_front_login_role($user) {
        if (!($user instanceof WP_User) || $this->has_valid_cookie()) {
            return $user;
        }

        $check_roles = apply_filters('rsfirewall_backend_login_roles', array('administrator'));

        if (array_intersect($check_roles, (array) $user->roles)) {
            $message = sprintf(
                '%s <a href="%s">%s</a>',
                esc_html__('This account requires the additional backend password.', 'rsfirewall'),
                esc_url(wp_login_url()),
                esc_html__('Log in from the administrator login page.', 'rsfirewall')
            );

            return new WP_Error('rsf_backend_password', $message);
        }

        return $user;
    }

    /**
     * Check if the backend login cookie is present and valid
     */
    protected function has_valid_cookie() {
        $name = 'rsf_backend_login'.$this->blog_id;

        return isset($_COOKIE[$name]) && is_string($_COOKIE[$name]) && hash_equals($this->cookie_hash, $_COOKIE[$name]);
    }

    /**
     * Set the backend login cookie
     */
    protected function set_login_cookie($value, $expires) {
        $name = 'rsf_backend_login'.$this->blog_id;

        // the options array is available only from PHP 7.3
        if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
            setcookie($name, $value, array(
                'expires'  => $expires,
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => $this->is_ssl,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
        } else {
            // append the samesite for older PHP versions
            setcookie($name, $value, $expires, COOKIEPATH.'; samesite=Lax', COOKIE_DOMAIN, $this->is_ssl, true);
        }
    }

    /**
     * Clear the backend login session
     */

    public function delete_login_cookie() {
        $this->set_login_cookie(' ', time() - YEAR_IN_SECONDS);
    }

    /**
     * Function to build and retrieve the backend login output
     *
     * It will look in the default template for override, if not found will output our own template
     */
    public function show_backend_login(){
        // Check theme directory first
        $newTemplate = locate_template( array( 'rsfirewall-login.php' ) );
        if( $newTemplate == '' ) {
            // Check plugin directory if not found in template
            $newTemplate = RSFIREWALL_BASE . 'templates/rsfirewall-login.php';
        }

        if( file_exists( $newTemplate ) ) {
            // Set the proper headers
            if ( !headers_sent() ) {
                status_header( 200 );
                nocache_headers();
                header( 'Content-Type: text/html; charset=utf-8' );
            }

            // Set the document direction
            $text_direction = 'ltr';
            if ( function_exists( 'is_rtl' ) && is_rtl() ) {
                $text_direction = 'rtl';
            }

            // Set the error message if is present
            $error_msg = '';
            if (RSFirewall_Helper::$message !='') {
                $error_msg = RSFirewall_Helper::display_admin_notice();
            }

			$wp_styles = wp_styles();
			$wp_scripts = wp_scripts();

            include $newTemplate;

            die();
        }
    }
}