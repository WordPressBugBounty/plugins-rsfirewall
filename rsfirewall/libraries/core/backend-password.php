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
        // build the hash as unique as possible
        $this->cookie_hash = md5($this->blog_id.$this->plugin_name.$this->version.$this->ip.$this->agent);
        
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
            if (!isset($_COOKIE['rsf_backend_login'.$this->blog_id]) || $_COOKIE['rsf_backend_login'.$this->blog_id] != $this->cookie_hash) {
                if ($this->is_blacklisted())
                {
                    $message = wp_kses_post( __('Your IP Address is blocked. <br /> Reason: Too many backend password login attempts <br /> Please contact an administrator.', 'rsfirewall' ));
                    wp_die( $message , 403);
                }
                if ( isset( $_POST['rsf_backend_password'] ) && strlen( $_POST['rsf_backend_password'] ) ) {
                    $redirect = false;
                    if ( md5( $_POST['rsf_backend_password'] ) == RSFirewall_Config::get( 'type_password' ) ) {
                        setcookie( 'rsf_backend_login'.$this->blog_id, $this->cookie_hash, 0, COOKIEPATH, COOKIE_DOMAIN, $this->is_ssl );
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
                if (!isset($_COOKIE['rsf_backend_login'.$this->blog_id]) || $_COOKIE['rsf_backend_login'.$this->blog_id] != $this->cookie_hash) {
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

            // build the exceptions for the referer check
            $site_url = get_site_url();
            $site_url = RSFirewall_Helper::parse_url($site_url);

            $domain = $site_url['host'];

            $exceptions = array();
            $exceptions[] = 'http://'.$domain;
            $exceptions[] = 'https://'.$domain;

            // add the non www version too
            if (strpos($domain, 'www.') === 0) {
                $domain = substr($domain, 4);
                $exceptions[] = 'http://'.$domain;
                $exceptions[] = 'https://'.$domain;
            } else {
                // if the domain does not have www check for this to
                $exceptions[] = 'http://www.'.$domain;
                $exceptions[] = 'https://www.'.$domain;
            }

            // check the referer (allow only from the site/frontend)
            foreach ($exceptions as $exception) {
                if (strpos($this->referer, $exception) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear the backend login session
     */

    public function delete_login_cookie() {
        setcookie( 'rsf_backend_login'.$this->blog_id, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $this->is_ssl );
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