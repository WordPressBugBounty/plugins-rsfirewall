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

class RSFirewall {
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * The instance of the version class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version_class;

    /**
     * Number of offenders
     *
     * @since  1.0.0
     * @access protected
     * @var int
     */
    public $offenders;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        /**
         * Define Plugin Name
         */
        $this->plugin_name = 'rsfirewall';

        $this->version_class = RSFirewall_Version::get_instance();
        /**
         * Define Version
         */
        $this->version = $this->version_class->version;
    }

    /**
     * Handles the updates to point at our server
     */
    public function enable_updates() {
        $code = RSFirewall_Config::get('code');
        if ( $code && strlen( $code ) == 20) {
            add_action('init', array($this->version_class, 'activate_updates'));
        }
    }

    /**
     * Register menu items
     *
     * @since    1.0.0
     */
    public function add_menu_pages()
    {
        $items = array();
		$locals = RSFirewall_i18n::get_locale( 'menus_strings');
		
        foreach (glob(RSFIREWALL_BASE . "views/*.xml") as $file)
        {
            $xml        = simplexml_load_file($file);
            $name       = pathinfo($file, PATHINFO_FILENAME);
            $ordering   = (int) $xml->ordering;
			
			$page_title = (string) $xml->page_title;
            $menu_title = (string) $xml->menu_title;

            $items[$ordering] = array(
                'name'       => $name,
                'type'       => (string) $xml->type,
                'hidden'     => (string) $xml->hidden,
                'page_title' => isset($locals[$page_title]) ? $locals[$page_title] : $page_title,
                'menu_title' => isset($locals[$menu_title]) ? $locals[$menu_title] : $menu_title,
                'capability' => (string) $xml->capability,
                'menu_slug'  => (string) $xml->menu_slug,
                'function'   => (string) $xml->function,
                'icon'       => (string) $xml->icon
            );
        }

        // check for xml files in the proversion folder
        foreach (glob(RSFIREWALL_BASE . "/proversion/views/*.xml") as $file)
        {
            $xml        = simplexml_load_file($file);
            $name       = pathinfo($file, PATHINFO_FILENAME);
            $ordering   = (int) $xml->ordering;

            $page_title = (string) $xml->page_title;
            $menu_title = (string) $xml->menu_title;

            $items[$ordering] = array(
                'name'       => $name,
                'type'       => (string) $xml->type,
                'hidden'     => (string) $xml->hidden,
                'page_title' => isset($locals[$page_title]) ? $locals[$page_title] : $page_title,
                'menu_title' => isset($locals[$menu_title]) ? $locals[$menu_title] : $menu_title,
                'capability' => (string) $xml->capability,
                'menu_slug'  => (string) $xml->menu_slug,
                'function'   => (string) $xml->function,
                'icon'       => (string) $xml->icon
            );
        }

        ksort($items);

        foreach ($items as $item)
        {

            $class = 'RSFirewall_' . $item['type'];
            /**
             * Constructor adds admin_menu hook.
             */
            $loaded_class = new $class($item);

            if (method_exists($loaded_class, 'load_scripts')) {
                $loaded_class->load_scripts();
            }
        }
    }
	
	public function add_settings_link()
    {
        add_filter( 'plugin_action_links_'.plugin_basename( RSFIREWALL_BASE . 'rsfirewall.php'), array($this, 'add_settings_link_filter') );
    }

    public function add_settings_link_filter($links)
    {
        $settings_link = '<a href="'.esc_url( admin_url( 'admin.php?page=rsfirewall_configuration') ).'">'.__('Configuration', 'rsfirewall').'</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Gets an instance of the plugin.
     *
     * We don't need to instantiate a new one everytime we need to access its properties
     * and methods.
     *
     * @return RSFirewall
     * @since    1.0.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall;
        }

        return $inst;
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the RSFirewall_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   public
     */
    public function set_locale()
    {
        add_action( 'plugins_loaded', array('RSFirewall_i18n','load_plugin_textdomain'));
    }
	
	 /**
     * Checks if the plugin has the file .disabled.
     *
     * @since    1.1.9
     * @access   protected
     */
	protected function check_if_disabled() {
        static $status;

        if (is_null($status)) {
            if (file_exists(RSFIREWALL_BASE.'.disabled')) {
                $status = true;
            } else {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * Register all of the hooks used in both the admin area and the frontend
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function define_global_hooks() {
		if (!$this->check_if_disabled()) {
			add_action( 'init', array($this, 'start_active_scanner') );
            add_filter('logout_url', array($this, 'logout_url'), 10, 2);
		}
		
		add_action( 'rsfirewall_clear_transient', array($this, 'remove_old_transient'), 10, 2 );
    }
	
	 /**
     * Removes old transients by rsfirewall so that the database would not overload
     *
     * @since    1.1.17
     * @access   public
     */
	public function remove_old_transient() {
        global $wpdb;

        $transients_found = $wpdb->get_col(
            $wpdb->prepare( "SELECT REPLACE(option_name, '_transient_timeout_', '') AS transient_name FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_rsfirewall\__%%'")
        );

        if (!empty($transients_found)) {
            // parsing the transients deletes the expired ones
            foreach( $transients_found as $transient ) {
                get_transient($transient);
            }
        }
    }

    /**
     * Handles the redirect logout_url
     *
     * @access   public
     */
    public function logout_url($logout_url, $redirect) {
        if (!empty($redirect)) {
            return $logout_url;
        }

        $logout_redirect = RSFirewall_Config::get('logout_redirect', '');
        $backend_pass    = RSFirewall_Config::get('enable_backend', false);
        $admin_slug      = RSFirewall_Config::get('enable_admin_slug', false);

        if (!empty($logout_redirect) && ($backend_pass || $admin_slug)) {
            $redirect_url = wp_http_validate_url($logout_redirect);
            if ($redirect_url) {
                $redirect_url = urlencode($redirect_url);
                $logout_url .= '&redirect_to=' . $redirect_url;
            }
        }

        return $logout_url;
    }
	
	 /**
     * Shows a message when the file .disabled is present
     *
     * @since    1.1.9
     * @access   protected
     */
	public function check_if_disabled_message() {
        $delete_url =  wp_nonce_url(admin_url( 'admin.php?page=rsfirewall_configuration').'&handler=configuration&task=delete_disabled', 'rsfirewall', 'rsf-actions');
        set_transient( 'global_admin_notice', sprintf(wp_kses_post(__( 'All the functionalities of the RSFirewall are disabled (the file <strong><em>.disabled</em></strong> is detected in the plugin root directory).<br/> If you are done changing the configurations please push  <a href="%s" class="button">Delete</a>', 'rsfirewall' )), $delete_url), 5);
    }
	
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function define_admin_hooks() {
		$disabled = $this->check_if_disabled();
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'admin_init', array($this, 'check_ajax_tasks') );
        add_action( 'admin_init', array($this, 'check_normal_tasks') );
        add_action( 'wp_dashboard_setup', array('RSFirewall_Widgets', 'setup') );
		
		add_action( 'admin_notices', array($this, 'global_admin_notice') );
		if (!$disabled) {
			add_action( 'plugins_loaded', array( $this, 'wp_login_slug' ), 1 );
			add_action( 'init', array($this, 'backend_password'), 2 );
		} else {
            // Add the message that the plugin functionalities are not working
            add_action( 'admin_init', array($this, 'check_if_disabled_message') );
        }
    }
	
	/**
     * Get any admin notice
     */
	public function global_admin_notice() {
		$message = get_transient( 'global_admin_notice' );
		
		if (!empty($message)) {
			?>
			<div class="notice notice-error is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
			<?php
		}
	}

    /**
     * Init the backend password for the admin login area
     */
    public function backend_password(){
       $backend_password = RSFirewall_Core_Backend_Password::get_instance();
       $backend_password->init();
    }

    /**
     * Init the custom slug for the backend login
     */
    public function wp_login_slug(){
       RSFirewall_Core_Login_Slug::get_instance();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
   public function enqueue_styles( $pagenow ) {
        /**
         * Css that are globally loaded
         */
        wp_enqueue_style( 'vex', RSFIREWALL_URL . 'assets/js/vendors/vex.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'vex-theme', RSFIREWALL_URL . 'assets/js/vendors/vex-theme-plain.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'custom-scrollbar', RSFIREWALL_URL . 'assets/js/vendors/customScrollbar/jquery.mCustomScrollbar.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'rsfirewall', RSFIREWALL_URL . 'assets/css/rsfirewall.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @param    $pagenow
     */
   public function enqueue_scripts($pagenow) {
        /**
         * Scripts that are globally loaded
         */
        wp_enqueue_script( 'vex', RSFIREWALL_URL . 'assets/js/vendors/vex.combined.min.js', array(), $this->version, false );
        wp_enqueue_script( 'custom-scrollbar', RSFIREWALL_URL . 'assets/js/vendors/customScrollbar/jquery.mCustomScrollbar.concat.min.js', array( 'jquery' ), $this->version, false );
        wp_register_script( 'rsfirewall', RSFIREWALL_URL . 'assets/js/rsfirewall.js', array( 'jquery' ), $this->version, false );
		
		$current_page_type = $pagenow;
		 
        global $post_type, $pagenow;
        if ( $post_type == 'threat' ) {
            wp_deregister_script( 'autosave' );
        }

        if ($current_page_type == 'toplevel_page_rsfirewall') {
            $results = RSFirewall_Helper::get_statistics();
            wp_localize_script('rsfirewall', 'rsfirewall_statistics', $results);
        }
        wp_localize_script( 'rsfirewall', 'rsfirewall_fix', array('remove_ip_nonce' => wp_create_nonce( 'remove_ip' )) );

        // Allow this only for the WordPress Dashboard page
        if ($pagenow == 'index.php') {

            // Add the nonce's for the ajax that we need to use fot the widgets
            $params = array(
                'step_check_nonce' => wp_create_nonce('step_check'),
                'verify_wordpress_version_nonce' => wp_create_nonce('verify_wordpress_version'),
            );
            wp_localize_script('rsfirewall', 'rsfirewall_widget_security', $params);
        }

        wp_enqueue_script( 'rsfirewall' );
    }

     /**
     * Start the active scanner after the core vulnerabilities
     */
	public function start_active_scanner() {
        /**
         * In case it's triggered by the system check, stop execution
         */
        if ( ! empty( $_POST ) && ! empty( $_POST['class'] ) && ( $_POST['class'] == 'RSFirewall_System_Check' || $_POST['class'] == 'RSFirewall_Fix' || $_POST['class'] == 'RSFirewall_Diff' ) ) {
            return;
        }

        // these are some specific WordPress vulnerabilities
        $core_vulnerabilities = RSFirewall_Core_Vulnerabilities::get_instance();
        $core_vulnerabilities->init();

        // Active scanner
        $active_scanner = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Core_Active_Scanner', 'get_instance'));
        $active_scanner->init();
	}

    /**
     * Check $_POST and if conditions are met, add the action to the admin ajax API
     *
     * @since 1.0.0
     */
    public function check_ajax_tasks() {
        if (defined('DOING_AJAX') && DOING_AJAX)
        {
            if (!empty($_POST) && !empty($_POST['action']) && strpos($_POST['action'], 'rsfirewall') === 0)
            {
                if (is_user_logged_in()) {
					// Don't allow if it's not an administrator
                    if (!current_user_can('manage_options')) {
                        wp_die(esc_html__( 'You are not authorised.', 'rsfirewall' ));
                    }
					
                    add_action('wp_ajax_' . sanitize_text_field($_POST['action']), array($this, 'do_ajax'));
                } else {
                    list($rsfirewall, $controller, $task) = explode('_', $_POST['action'], 3);

                    // Allow access only to specific controllers for security reasons
                    $allow = array(
                        'tfa'
                    );

                    if (in_array($controller, $allow)) {
                        add_action('wp_ajax_nopriv_' . sanitize_text_field($_POST['action']), array($this, 'do_ajax'));
                    }
                }
            }
        }
    }

    public function do_ajax()
    {
        list($rsfirewall, $controller, $task) = explode('_', $_POST['action'], 3);

        $controller = sanitize_text_field($controller);
        $task = sanitize_text_field($task);

        if (RSFirewall_Helper::class_exists_pro('RSFirewall_Controller_'.$controller))
        {
            $controller = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Controller_'.$controller, 'get_instance'));
            if (is_callable(array($controller, $task)))
            {
                call_user_func(array($controller, $task));
            }
        }

        exit();
    }

    /**
     * Check $_POST and if conditions are met, add the action to the admin non ajax API
     *
     * @since 1.0.0
     */
    public function check_normal_tasks() {
        if (!defined('DOING_AJAX') || !DOING_AJAX)
        {
            if (!empty($_REQUEST) && !empty($_REQUEST['rsf-actions']) && wp_verify_nonce($_REQUEST['rsf-actions'], 'rsfirewall'))
            {
                $handler = sanitize_text_field($_REQUEST['handler']);
                $task    = sanitize_text_field($_REQUEST['task']);

                // Run the task from a controller if exists, else run it from the model (most used in posts)
                if (class_exists('RSFirewall_Controller_'.$handler)) {
                    $handler    = 'Controller_'.$handler;
                } else if (class_exists('RSFirewall_Model_'.$handler))  {
                    $handler    = 'Model_'.$handler;
                } else {
                    wp_die(__('The handler requested is not defined', 'rsfirewall'));
                }

                $handler = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_'.$handler, 'get_instance'));
                if (is_callable(array($handler, $task)))
                {
                    call_user_func(array($handler, $task));
                }

                exit();
            }
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}