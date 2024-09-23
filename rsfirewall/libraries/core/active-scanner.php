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

class RSFirewall_Core_Active_Scanner extends RSFirewall_Core {
    /**
     * Holds the exceptions
     */
    protected $exception;

    /**
     * Holds the current URI
     */
    protected $uri;

    /**
     * @return RSFirewall_Core_Active_Scanner
     * @since 1.0.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall_Core_Active_Scanner();
        }

        return $inst;
    }

    /**
     * Initiate the scanner after the config was generated
     */
    public function init() {
        // get all the callbacks
        $callbacks = $this->get_callbacks();

        // Execute the core ones
        if (isset($callbacks['core']) && !empty($callbacks['core'])) {
            foreach ($callbacks['core'] as $key => $value) {
                if (method_exists($this, $key) && $value) {
                    $args = ($key == 'protect_users') ? $value : NULL;

                    $this->call_method($key, $args);
                }
            }
        }

        if ( ! $this->is_whitelisted() ) {
            $calling_class = get_class($this);

            if ( $this->is_blacklisted() || ($calling_class == 'RSFirewall_Core_Active_ScannerPro' && $this->is_geo_ip_blacklisted()) || $this->is_user_agent_blacklisted() || $this->is_referer_blacklisted() ) {
                $this->show_forbidden_message(false, sprintf( wp_kses_post( __('Your IP Address is blocked. <br /> Reason: %s <br /> Please contact an administrator.', 'rsfirewall' )), $this->reason ));
            }


            // Check for SESSION injections
            if ($this->is_session_injection()) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                    // Just to be on the safe side in case destroying doesn't work.
                    $_SESSION['session_client_forwarded'] = null;
                    $_SESSION['session_client_browser'] = null;
                }

                // if the SESSION uses cookies then we must destroy them to
                if (ini_get("session.use_cookies") || ini_get("session.use_only_cookies")) {
                    $params = session_get_cookie_params();
                    // remove this session cookie
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }

                session_unset();
                session_destroy();
                $this->show_forbidden_message();
            }
            // Check if the active scanner is enabled
            $enable = (int) $this->get_config( 'enable_active_scanner' );

            if ($enable) {
                // Set the login
                $login_form = RSFirewall_Core_Login_Form::get_instance();
                $login_form->init();
				
				// Enable reCAPTCHA for comments
                if ($this->get_config('enable_captcha_comments')) {
                    $login_form->init_comment_captcha();
                }

                // Enable reCAPTCHA for register forms
                if ($this->get_config('enable_captcha_register')) {
                    $login_form->init_register_captcha();
                }

                // Check if active scanner is enabled in admin area
                $enable_admin = (int)$this->get_config('enable_active_scanner_in_admin');

                // Catch the exception only if necessary
                if (($this->is_admin && $enable_admin) || !$this->is_admin) {
                    // build URI out of $_GET and $_POST to compare easier
                    $this->uri = array(
                        'get' => urldecode(http_build_query($_GET)),
                        'post' => urldecode(http_build_query($_POST))
                    );

                    // determine if is an exception
                    $this->exception = $this->is_exception();


                    // these are the checks that can be turned on/off from the configuration area
                    if (isset($callbacks['optional']) && !empty($callbacks['optional'])) {
                        foreach ($callbacks['optional'] as $key => $value) {
                            if (method_exists($this, $key) && $value) {
                                $args = $value == '1' ? NULL : $value;

                                $this->call_method($key, $args);
                            }
                        }
                    }
                }
            }
        }

        // empty the threats history
        if ($this->is_admin) {
            $this->clear_log_history();
            $this->clear_old_offenders();
        }
    }

    /**
     * Shows the forbidden message and count the attempt for autoban purpose
     *
     * @since  1.0.0
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
     * Throws the 404 page
     *
     * @since  1.0.0
     * @access protected
     */
    protected function show_404_page() {
        status_header( 404 );
        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );

        // Load the standard 404 template available, if any
        $template = get_404_template();
        if ( @file_exists( $template ) ) {
            include( $template );
            exit;
        }

        /* Try and load our own template */

        // Set the error message
        $error_msg = 'The requested URL <code>' . esc_url( $_SERVER['REQUEST_URI'] ) . '</code> was not found on this server.';

        // Check theme directory first
        $template = locate_template( array( 'rsfirewall-404.php' ) );
        if( $template == '' ) {
            // Check plugin directory if not found in template
            $template = RSFIREWALL_BASE . 'templates/rsfirewall-404.php';
        }

        if( @file_exists( $template ) ) {
            // Set the document direction
            $text_direction = 'ltr';
            if ( function_exists( 'is_rtl' ) && is_rtl() ) {
                $text_direction = 'rtl';
            }

            include $template;

            exit;
        }

        // If none are available
        echo '<html>
                <head>
                    <title>404 Page Not Found</title>
                </head>
                <body>
                    <h1>Page Not Found</h1>
                    <p>'.$error_msg.'</p>
                </body>
              </html>';
        exit;

    }

    /**
     * Builds the callbacks array
     *
     * @since  1.0.0
     * @access private
     */
    protected function get_callbacks() {
        /**
         * Array that holds all the Active Scanner Options
         */
        $callbacks = array(
            'core' => array(
                'verify_generator'                => $this->get_config( 'verify_generator' ),
                'disable_admin_creation'          => $this->get_config( 'disable_admin_creation' ),
                'disable_wp_installer'            => $this->get_config( 'disable_wp_installer' ),
                'check_core_wp_integrity'         => $this->get_config( 'check_core_wp_integrity' ),
                'monitor_files'                   => $this->get_config( 'monitor_files' ),
                'block_xmlrpc'                    => $this->get_config( 'block_xmlrpc' ),
                'disable_rest_api'                => $this->get_config( 'disable_rest_api' ),
            ),
            'optional' => array(
                'php_enable_protections_for'      => $this->get_config( 'php_enable_protections_for' ),
                'sql_enable_protections_for'      => $this->get_config( 'sql_enable_protections_for' ),
                'js_enable_protections_for'       => $this->get_config( 'js_enable_protections_for' ),
                'prevent_author_scan'             => $this->get_config( 'prevent_author_scan' ),
                'handle_uploads'                  => 1, // this must always be true
            )
        );

        return $callbacks;
    }

    /**
     * The function used to display the admin notices if necessary (used in the admin_notice hook)
     */
    public function display_notice() {
        if ($message = RSFirewall_Helper::display_admin_notice()) {
            print($message);
        }
    }

    /**
     * Function to block XML_RPC
     */
    public function block_xmlrpc() {
        $url = $this->get_current_url();

        // Disable XML-RPC methods that require authentication.
        add_filter( 'xmlrpc_enabled', '__return_false' );

        // Disable the posts to be open for pings.
        add_filter( 'pings_open', '__return_false' );

        // Disable pingback URL that shows up in the Head area in case the function get_bloginfo() is used
        add_filter( 'bloginfo_url', array($this, 'remove_pingback_url'), 10, 2 );

        // Removes the EditURI from the head section that also points tot he xmlrpc.php
        remove_action( 'wp_head', 'rsd_link', 10 );

        // Removes the wlwmanifest.xml from the head
        remove_action( 'wp_head', 'wlwmanifest_link', 10 );

        if (stripos($url, 'xmlrpc.php') !== false || stripos($url, 'wp-trackback.php') !== false) {
            $args = array(
                'level'           => 'medium',
                'code'            => 'XMLRPC_ATTEMPTED',
                'debug_variables' => stripos($url, 'xmlrpc.php') !== false ? 'xmlrpc.php' : 'wp-trackback.php'
            );

            $this->logger->add( $args );

            $this->reason = esc_html__('Blocked XML-RPC server access attempt.', 'rsfirewall');
            // set the default wp_die_handler for the xmlrpc_wp_die
            add_filter('wp_die_xmlrpc_handler', array($this, 'xmlrpc_die_filter'));

            $this->show_forbidden_message();
        }
    }


    /**
     * Function to disable the REST API
     */
    public function disable_rest_api() {
        // in the admin are...if any rest api calls are made with the REST_REQUEST constant enabled, allow them
        if ($this->is_admin) {
            return;
        }

        $disable = false;
        $url = false;
        // check if the logged in user is allowed
        $allow_logged_in = $this->get_config('allow_rest_api_logged');

        if (!$allow_logged_in || ($allow_logged_in && !is_user_logged_in())) {
            // get the rest api exceptions
            $exceptions = $this->get_config( 'rest_api_exceptions' );

            if (!empty($exceptions)) {
                $exceptions = RSFirewall_Helper::explode($exceptions);
            } else {
                $exceptions = array();
            }

            $url = $this->get_current_url();

            // verify the exceptions
            $found_exception = false;
            if (!empty($exceptions)) {
                foreach ($exceptions as $exception) {
                    if (strpos($url, $exception) !== false) {
                        $found_exception = true;
                        break;
                    }
                }
            }

            // If no exceptions found
            if (!$found_exception) {
                $disable = $this->is_rest_api_request($url);
            }
        }

        if ( $disable ) {
            if ($url) {
                // add to the log as well
                $details = sprintf(esc_html__('URI: %s', 'rsfirewall'), $url);
                $args = array(
                    'level' => 'low',
                    'code' => 'GENERAL_REST_API_REQUEST',
                    'debug_variables' => $details
                );
                $this->logger->add( $args )->save();
            }

            $this->show_404_page();
        }
    }

    protected function is_rest_api_request($url) {
        if (defined('REST_REQUEST') && REST_REQUEST) {
           return true;
        }

        if (isset($_REQUEST['rest_route'])) {
           return true;
        }

        $url = $this->clean_url_path($url);

        if (strpos(urldecode($url) , get_rest_url()) === 0) {
            return true;
        }

        return false;
    }

    protected function clean_url_path($url) {
        $url = parse_url($url);

        // make sure there is a right backslash at the end of the path part
        if (isset($url['path'])) {
           $url['path'] = rtrim($url['path'], '/').'/';
        }

        // add the :// to the scheme part
        $url['scheme'] = $url['scheme'] . '://';

        return implode($url);
    }

    public function remove_pingback_url($output, $show) {
        if ( $show == 'pingback_url' ) {
            $output = '';
        }

        return $output;
    }

    public function xmlrpc_die_filter($handler) {
        if ($handler == '_xmlrpc_wp_die_handler') {
            return '_default_wp_die_handler';
        }

        return $handler;
    }

    /**
     * Function to remove the meta generator tags
     */
    public function verify_generator() {
        if (!$this->is_admin) {
            //remove inbuilt version
            remove_action('wp_head', 'wp_generator');
            //remove Woo version (if it's present)
            remove_action('wp_head', 'woo_version');
        }
    }

    /**
     * Enables the protection against:
     *  - User Enumeration (example.com/?author=1) - 1 is the id of the user
     *  - Getting user information from oEmbed response data. (example.com/wp-json/oembed/1.0/embed?url=http%3A%2F%2Fexample.com%2Fhello-world%2F)
     *  - Getting user information from REST Requests. (example.com/wp-json/wp/v2/users)
     */
    public function prevent_author_scan() {
        add_filter('oembed_response_data', array($this, 'oembed_author_filter'), 99, 4);
        add_filter('rest_request_before_callbacks', array($this, 'json_api_author_filter'), 99, 3);
        add_filter('request', array($this, 'prevent_author_scan_enumeration_filter'), 1);
    }

    public function oembed_author_filter($data, $post, $width, $height) {
        unset($data['author_name']);
        unset($data['author_url']);
        return $data;
    }

    public function json_api_author_filter($response, $handler, $request) {
        $route = $request->get_route();
        if (!current_user_can('list_users')) {
            $rest_controller =  RSFirewall_Helper_Restusers::get_instance();
            $rest_url_base = $rest_controller->get_url_base();

            if (preg_match('~' . preg_quote($rest_url_base, '~') . '/*$~i', $route)) {
                $error = new WP_Error('rest_user_restricted_access', __('Sorry, you don\'t have permission to list users.', 'rsfirewall'), array('status' => rest_authorization_required_code()));
                $response = rest_ensure_response($error);

                $args = array(
                    'level'           => 'medium',
                    'code'            => 'USER_REST_INFORMATION_ATTEMPT'
                );

                $this->logger->add( $args )->save();
            }
            else if (preg_match('~' . preg_quote($rest_url_base, '~') . '/+(\d+)/*$~i', $route, $matches)) {
                $id = (int) $matches[1];
                if (get_current_user_id() !== $id) {
                    $error = new WP_Error('rest_user_invalid_id', __('Invalid user ID.', 'rsfirewall'), array('status' => 404));
                    $response = rest_ensure_response($error);

                    $args = array(
                        'level'           => 'medium',
                        'code'            => 'USER_REST_INFORMATION_ATTEMPT_INVALID_ID'
                    );

                    $this->logger->add( $args )->save();
                }
            }
        }
        return $response;
    }

    /**
     * The function triggered by the hook for preventing Author Scan
     */
    public function prevent_author_scan_enumeration_filter($query_vars) {
        if (!is_admin() && !empty($query_vars['author']) && is_numeric(preg_replace('/[^0-9]/', '', $query_vars['author'])) &&
            (
                (isset($_GET['author']) && is_numeric(preg_replace('/[^0-9]/', '', $_GET['author']))) ||
                (isset($_POST['author']) && is_numeric(preg_replace('/[^0-9]/', '', $_POST['author'])))
            )
        ) {
            $args = array(
                'level'           => 'medium',
                'code'            => 'USER_ENUM_ATTEMPTED',
                'debug_variables' => $query_vars['author']
            );

            $this->logger->add( $args );

            $this->reason = esc_html__('Blocked User Enumeration attempt.', 'rsfirewall');
            $this->show_forbidden_message();
        }

        return $query_vars;
    }

    /**
     * Enables the protections for the php
     */
    public function php_enable_protections_for($enable_php_for) {
        if (!$this->exception || !$this->exception->php) {
            // Check for LFI if is enabled
            if ($this->get_config( 'local_file_inclusion' )) {
                // $_GET is filtered
                // or $_POST is filtered
                if ((in_array('get', $enable_php_for) && ($result = $this->is_LFI($this->uri['get']))) || (in_array('post', $enable_php_for) && ($result = $this->is_LFI($this->uri['post'])))) {
                    $details  = sprintf(esc_html__('URI: %s | Match: %s', 'rsfirewall'), $result['uri'], $result['match']);

                    $args = array(
                        'level'           => 'medium',
                        'code'            => 'LFI_ATTEMPTED',
                        'debug_variables' => $details
                    );

                    $this->logger->add( $args );

                    $this->reason = esc_html__('Local file inclusion attempted.', 'rsfirewall');
                    $this->show_forbidden_message();
                }
            }

            if ($this->get_config( 'remote_file_inclusion' )) {
                // $_GET is filtered
                // or $_POST is filtered
                if ((in_array('get', $enable_php_for) && ($result = $this->is_RFI($this->uri['get']))) || (in_array('post', $enable_php_for) && ($result = $this->is_RFI($this->uri['post'])))) {
                    $details  = sprintf(esc_html__('URI: %s | Match: %s', 'rsfirewall'), $result['uri'], $result['match']);

                    $args = array(
                        'level'           => 'medium',
                        'code'            => 'RFI_ATTEMPTED',
                        'debug_variables' => $details,
                        'check_bot'       => 1
                    );

                    $this->logger->add( $args );

                    $this->reason = esc_html__('Remote file inclusion attempted.', 'rsfirewall');
                    $this->show_forbidden_message();
                }
            }

            // null code should never be found...
            if ($this->find_null_code($this->uri['get']) || $this->find_null_code($this->uri['post'])) {

                $args = array(
                    'level'           => 'low',
                    'code'            => 'NULLCODE_IN_URI',
                    'check_bot'       => 1
                );

                $this->logger->add( $args );

                $this->reason = esc_html__('Nullcode in URI.', 'rsfirewall');
                $this->show_forbidden_message();
            }
        }
    }

    /**
     * Enables the protections for the SQL
     */
    public function sql_enable_protections_for($enable_sql_for) {
        if (!$this->exception || !$this->exception->sql) {
            if ((in_array('get', $enable_sql_for) && ($result = $this->is_SQLi($this->uri['get']))) || (in_array('post', $enable_sql_for) && ($result = $this->is_SQLi($this->uri['post'])))) {
                $details  = sprintf(esc_html__('URI: %s | Match: %s', 'rsfirewall'), $result['uri'], $result['match']);

                $args = array(
                    'level'           => 'medium',
                    'code'            => 'SQLI_ATTEMPTED',
                    'debug_variables' => $details
                );

                $this->logger->add( $args );

                $this->reason = esc_html__('SQL injection attempted.', 'rsfirewall');
                $this->show_forbidden_message();
            }
        }
    }

    /**
     * Enables the protections for the JavaScript
     */
    public function js_enable_protections_for($enable_js_for) {
        if (!$this->exception || !$this->exception->js) {
            if ((in_array('get', $enable_js_for) && ($result = $this->is_XSS($this->uri['get']))) || (in_array('post', $enable_js_for) && ($result = $this->is_XSS($this->uri['post'])))) {
                // if we don't filter, just drop the connection
                if (!$this->get_config( 'filter_javascript' )) {
                    $details  = sprintf(esc_html__('URI: %s | Match: %s', 'rsfirewall'), $result['uri'], $result['match']);

                    $args = array(
                        'level'           => 'medium',
                        'code'            => 'XSS_ATTEMPTED',
                        'debug_variables' => $details
                    );

                    $this->logger->add( $args );

                    $this->reason = esc_html__('XSS attempted.', 'rsfirewall');
                    $this->show_forbidden_message();
                }

                // filter $_GET
                if (in_array('get', $enable_js_for)) {
                    $this->filter_XSS($_GET);
                }

                // filter $_POST
                if (in_array('post', $enable_js_for)) {
                    $this->filter_XSS($_POST);
                }
            }
        }
    }

    /**
     * Checks the uploaded files
     */
    public function handle_uploads() {

        if (!$this->exception || !$this->exception->uploads) {
            if ($_FILES) {
                $verify_upload 				  = $this->get_config('check_known_malware');
                $verify_multiple_exts 		  = $this->get_config('check_multiple_extensions');
                $verify_upload_blacklist_exts = $this->get_config('banned_extensions', '');

                if (!empty($verify_upload_blacklist_exts)) {
                    $verify_upload_blacklist_exts = RSFirewall_Helper::explode($verify_upload_blacklist_exts);
                } else {
                    $verify_upload_blacklist_exts = array();
                }

                $filter_uploads				  = $this->get_config('filter_uploads');

                // compact $_FILES in a friendlier array
                $files_handler = RSFirewall_Helper_Files::get_instance();
                $files = $files_handler->get();
                //lets make a singular array in case there are multiple ones
                $files = $files_handler->compact_files($files);

                foreach ($files as $file) {

                    if ($file->tmp_name && file_exists($file->tmp_name)) {

                        $ext = $this->get_extension($file->name);

                        // has extension
                        if ($ext != '') {
                            if ($verify_multiple_exts && strpos($ext, '.') !== false) {
                                // verify parts
                                $parts = explode('.', $ext);
                                $multi = true;
                                foreach ($parts as $part) {
                                    if (is_numeric($part) || strlen($part) > 4) {
                                        $multi = false;
                                        break;
                                    }
                                }

                                if ($multi) {
                                    $args = array(
                                        'level'           => 'low',
                                        'code'            => 'UPLOAD_MULTIPLE_EXTS_ERROR',
                                        'debug_variables' => $file->name
                                    );

                                    $this->logger->add( $args );
                                    $this->logger->save();

                                    if (!$filter_uploads) {
                                        $this->reason = esc_html__('There was an attempt to upload a file with multiple extensions.', 'rsfirewall');
                                        $this->show_forbidden_message();
                                    }

                                    @unlink($file->tmp_name);
                                    continue;
                                }
                            }
                            if ($verify_upload_blacklist_exts && in_array($ext, $verify_upload_blacklist_exts)) {
                                $args = array(
                                    'level'           => 'medium',
                                    'code'            => 'UPLOAD_EXTENSION_ERROR',
                                    'debug_variables' => $file->name
                                );

                                $this->logger->add( $args );
                                $this->logger->save();

                                if (!$filter_uploads) {
                                    $this->reason = esc_html__('There was an attempt to upload a file with a forbidden extension.', 'rsfirewall');
                                    $this->show_forbidden_message();
                                }
                                @unlink($file->tmp_name);
                                continue;
                            }
                            if ($verify_upload && $ext == 'php') {
                                if ($match = $this->is_malware_upload($file->tmp_name)) {
                                    $args = array(
                                        'level'           => 'medium',
                                        'code'            => 'UPLOAD_SHELL',
                                        'debug_variables' => $file->name.' / '.$match['reason']
                                    );

                                    $this->logger->add( $args );
                                    $this->logger->save();

                                    if (!$filter_uploads) {
                                        $this->reason = esc_html__('There was an attempt to upload a malware script.', 'rsfirewall');
                                        $this->show_forbidden_message();
                                    }

                                    @unlink($file->tmp_name);
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Disable the installation of new plugins
     */
    public function disable_wp_installer() {
        global $pagenow;

        if (is_user_logged_in()) {
            // remove the pages from the menu
            if (is_admin() && !in_array('administrator', $this->current_user->roles )) {
                if( !in_array('administrator', $this->current_user->roles )){
                    add_action( 'admin_menu', array($this, 'remove_installer_pages'), 999 );
                }
            }

            // temporary remove the capabilities from all the users except administrators
            add_filter('user_has_cap', array($this, 'remove_capabilities'), 10, 4);

            // if accessed directly show the forbidden message
            if ( is_admin() && !in_array('administrator', $this->current_user->roles ) && ($pagenow == 'theme-install.php' || $pagenow == 'plugin-install.php') ) {
                $this->show_forbidden_message(false,  wp_kses_post( __('You are not allowed to access this page. Installation of plugins / themes was disabled from RSFirewall! <br/> <a href="' . get_admin_url() . '">Go back</a>', 'rsfirewall' )));
            }
        }
    }
    /**
     * Used by the hook 'user_has_cap' when the installer is disabled - it removes install_themes and install_plugins capabilities
     */
    public function remove_capabilities($capabilities, $selectedcap, $args, $user) {
        if( !in_array('administrator', $user->roles ) && ($selectedcap[0] == 'install_themes' || $selectedcap[0] == 'install_plugins')) {
            unset($capabilities['install_themes']);
            unset($capabilities['install_plugins']);
        }

        return $capabilities;
    }

    /**
     * Used by the hook 'admin_menu' when the installer is disabled - it removes the submenu 'Add New' from 'Plugins' menu
     */
    public function remove_installer_pages() {
        remove_submenu_page( 'plugins.php', 'plugin-install.php' );
    }

    /**
     * Disable creation of user accounts
     */
    public function disable_admin_creation() {
        global $pagenow;

        if ($this->is_admin && ($pagenow == 'user-edit.php' || $pagenow == 'user-new.php')) {
            // stop adding users with 'administrator' role (the normal users add new / update)
            add_filter('user_profile_update_errors', array($this, 'unset_if_admin'), 10, 3);
        } else {
            // if the users are created / inserted anywhere else
            $admin_users    = RSFirewall_Helper_Users::getAdminUsers();
            $lockdown_users = get_option('rsfirewall_admin_users');

            if ($lockdown_users) {
                if ($diff_users = array_diff($admin_users, $lockdown_users)) {
					// load the user.php so that we can use the wp_delete_user function
                    if (!function_exists('wp_delete_user')) {
                        require_once(ABSPATH . 'wp-admin/includes/user.php');
                    }
					
                    foreach ($diff_users as $user_id) {
                        wp_delete_user($user_id);
                    }
                }
            }

            // in case any admin that is locked becomes, by direct access of the function 'wp_insert_user' on the update case, a non admin we must delete it from the locked admins
            add_action('profile_update', array($this, 'update_locked_users'), 10, 2);
        }
    }
    /**
     * This will update the locked admin users stored in the options if any administrator becomes a non administrator
     *
     * @param $user_id
     * @param $old_user_data
     *
     */
    public function update_locked_users($user_id, $old_user_data) {
        $new_user_data = get_userdata($user_id);
        if (in_array('administrator', $old_user_data->roles) && !in_array('administrator', $new_user_data->roles)) {
            $lockdown_users = get_option('rsfirewall_admin_users');
            if (!empty($lockdown_users) && in_array($user_id, $lockdown_users)) {
                $lockdown_users = array_diff($lockdown_users, array($user_id));
                update_option('rsfirewall_admin_users', $lockdown_users);
            }
        }
    }

    /**
     * Checks if the user's role is set to Administrator and stops from adding it
     *
     * @param $errors
     * @param $update
     * @param $user
     *
     */
    public function unset_if_admin($errors, $update, $user) {
        $do_not_continue = false;
        // if the user we are editing is already an administrator must continue updating
        if ($update) {
            $old_user = get_userdata($user->ID);
            if (in_array('administrator', $old_user->roles)) {
                $do_not_continue = true;

                // In case the old role was administrator and the new role is not then we need to update the 'rsfirewall_admin_users' option
                if ($user->role != 'administrator') {
                    $lockdown_users = get_option('rsfirewall_admin_users');
                    if (!empty($lockdown_users) && in_array($user->ID, $lockdown_users)) {
                        $lockdown_users = array_diff($lockdown_users, array($user->ID));
                        update_option('rsfirewall_admin_users', $lockdown_users);
                    }
                }
            }
        }

        if ( $user->role == 'administrator' && !$do_not_continue) {
            $args = array(
                'level'           => 'critical',
                'code'            => 'NEW_ADMIN_CREATED',
                'debug_variables' => $user->user_email,
            );

            $this->logger->add( $args )->save();

            // stop adding the user and throw the proper error
            $errors->add('no_administrator', sprintf( esc_html__( 'Attempt to create a new Administrator account (%s) was blocked.', 'rsfirewall' ), $user->user_email ));
        }
    }

    /**
     * Monitor the core wp files for changes
     */
    public function check_core_wp_integrity() {
        global $wp_version;

        $files = $this->get_hashes($wp_version);
        if ( ! $files ) {
            return false;
        }

        $response       = array();
        foreach ( $files as $file ) {
            $path = RSFIREWALL_SITE . '/' . $file->file;
            if ( file_exists( $path ) && is_readable( $path ) ) {
                if ( $file->hash != md5_file( $path ) ) {
                    $args = array(
                        'level'           => 'critical',
                        'code'            => 'CORE_FILE_MODIFIED',
                        'debug_variables' => $file->file,
                    );

                    $response[] = $args;
                    $this->add_checked_status($file, $wp_version);
                }
            }
        }

        foreach ( $response as $file ) {
            $this->logger->add( $file )->save();
        }

        return true;
    }


    /**
     * Monitor the files that are selected in the config (with the type protect)
     */
    public function monitor_files() {
        $files = $this->get_hashes('protect');

        if ( ! $files ) {
            return false;
        }

        $response       = array();
        foreach ( $files as $file ) {
            $path = $file->file;
            if ( file_exists( $path ) && is_readable( $path ) ) {
                if ( $file->hash != md5_file( $path ) ) {
                    $args = array(
                        'level'           => 'critical',
                        'code'            => 'PROTECTED_FILES_MODIFIED',
                        // if windows replace the backslashes with forward slashes, so that they wont be removed
                        'debug_variables' => str_replace('\\', '/',$file->file),
                    );

                    $response[] = $args;
                    $this->add_checked_status($file);
                }
            }
        }

        foreach ( $response as $file ) {
            $this->logger->add( $file )->save();
        }

        return true;
    }

    /**
     * Function to check if this is an exception, based on User Agent or URL
    */
    protected function is_exception() {
        $args = array(
            'post_type'   => $this->prefix.'exceptions',
            'post_status' => 'publish',
        );


        // current url
        $url = $this->get_current_url();

        // built in exceptions for the firewall admin
        if ($this->is_admin) {
            if (strpos($url, 'wp-admin/options.php') !== false && !empty($this->uri['post'])) {
                if (strpos($this->uri['post'], 'wp-admin/admin.php?page=rsfirewall_configuration') !== false) {
                   return (object)array(
                        'php' => 1,
                        'sql' => 1,
                        'js' => 1,
                        'uploads' => 1
                    );
                }
            }
        }

        $query = new WP_Query( $args );

        if ($posts = $query->posts) {
            foreach ($posts as $post) {
                $post_meta = get_post_meta($post->ID);

                // for each meta key there is an array with only one entry, so we need to add that entry directly to the meta key
                foreach ($post_meta as $key => $value) {
                    // remove the prefix "rsfirewall" and other values that are generated by wordpress and don't need
                    unset($post_meta[$key]);
                    if (strpos($key, 'rsfirewall') !== false) {
                        $key = str_replace(array('rsfirewall_exception_', 'rsfirewall_'), '', $key);
                        $post_meta[$key] = is_array($value) ? $value[0] : $value;
                    }
                }

				if (!empty($post_meta['type'])) {
					switch ($post_meta['type']) {
						case 'ua':
							if ($post_meta['use_regex']) {
								if (preg_match('/'.$post_meta['match'].'/', $this->agent)) {
									return (object) $post_meta;
								}
							} else {
								if ($post_meta['match'] == $this->agent) {
									return (object) $post_meta;
								}
							}
							break;
						case 'url':
							if ($post_meta['use_regex']) {
								if (preg_match('/'.$post_meta['match'].'/', $url)) {
									return (object) $post_meta;
								}
							} else {
								if (get_site_url().'/'.ltrim($post_meta['match'], '/') == $url) {
									return (object) $post_meta;
								}
							}
							break;
					}
				}
            }
        }

        return false;
    }

    /**
     * Function to check there is a SESSION injection attempt
     */
    protected function is_session_injection() {
        $value = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : false;
        if ($value) {
            if (filter_var($value, FILTER_VALIDATE_IP) === false && strpos($value, '{') !== false && strpos($value, '}') !== false) {
                $args = array(
                    'level'           => 'medium',
                    'code'            => 'SESSION_INJECTION_ATTEMPT',
                    'debug_variables' => $value,
                );

                $this->logger->add( $args );

                $this->reason = esc_html__( 'Session injection attempt blocked!', 'rsfirewall' );
                return true;
            }
        }

        return false;
    }

    /**
     * Function to check for Remote File Inclusion
     */
    protected function is_RFI($uri) {
        static $exceptions;
        if (!is_array($exceptions)) {
            $exceptions = array();
            // attempt to remove instances of our website from the URL...
            $site_url = get_site_url();
            $site_url = RSFirewall_Helper::parse_url($site_url);

            $domain = $site_url['host'];
            $exceptions[] = 'http://'.$domain;
            $exceptions[] = 'https://'.$domain;
			
			// add the non www version to
            if (strpos($domain, 'www.') === 0) {
                $domain = substr($domain, 4);
                $exceptions[] = 'http://'.$domain;
                $exceptions[] = 'https://'.$domain;
            } else {
                // if the domain does not have www check for this to
                $exceptions[] = 'http://www.'.$domain;
                $exceptions[] = 'https://www.'.$domain;
            }
			
            // also remove blank entries that do not pose a threat
            $exceptions[] = 'http://&';
            $exceptions[] = 'https://&';
        }

        $uri = str_replace($exceptions, '', $uri);

        if (preg_match('#=https?:\/\/.*#is', $uri, $match)) {
            return array(
                'match' => $match[0],
                'uri'	=> $uri
            );
        }
        return false;
    }

    /**
     * Function to check for Local File Inclusion
     */
    protected function is_LFI($uri) {
        if (preg_match('#\.\/#is', $uri, $match)) {
            return array(
                'match' => $match[0],
                'uri'	=> $uri
            );
        }
        return false;
    }

    /**
     * Look in the get or post to find null code
     */
    protected function find_null_code($uri) {
        return strpos($uri, "\0") !== false;
    }

    /**
     * Check to see if there is an SQL injection
     */
    protected function is_SQLi($uri) {
        global $wpdb;
        if (preg_match('#[\d\W](union select|union join|union distinct)[\d\W]#is', $uri, $match)) {
            return array(
                'match' => $match[0],
                'uri'	=> $uri
            );
        }
        // check for SQL operations with a table name in the URI
        if (preg_match('#[\d\W](union|union select|insert|from|where|concat|into|cast|truncate|select|delete|having)[\d\W]#is', $uri, $match) && preg_match('/[\s]'.preg_quote($wpdb->prefix).'/', $uri, $match)) {
            return array(
                'match' => $match[0],
                'uri'	=> $uri
            );
        }

        return false;
    }

    /**
     * Detects if there is an XSS attempt in the Uri data
     */
    protected function is_XSS($uri) {
        if (preg_match('#<[^>]*\w*\"?[^>]*>#is', $uri, $match)) {
            return array(
                'match' => $match[0],
                'uri'	=> $uri
            );
        }

        return false;
    }

    /**
     * Detects if there is an XSS attempt in the Uri data
     */
    protected function filter_XSS(&$array) {
        RSFirewall_Helper_XSS::filter($array);
    }

    /**
     * Get the extension of the current file
     */
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
     * Checks if an upload is a malware
     */
    protected function is_malware_upload($file) {
        static $model = null;
        if (is_null($model)) {
            $model = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Check', 'get_instance'));
        }

        return $model->signatures_check($file);
    }
}