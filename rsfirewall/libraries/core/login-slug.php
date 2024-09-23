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

class RSFirewall_Core_Login_Slug extends RSFirewall_Core {
    private $use_login;
    private $request_raw;
    private $admin_slug;
    private $is_permalink_structure;
    /**
     * @return RSFirewall_Core_Login_Slug
     * @since 1.0.0
     */
    public static function get_instance() {
        static $inst;
        if ( ! $inst ) {
            $inst = new RSFirewall_Core_Login_Slug();
        }

        return $inst;
    }

    public function __construct(){
        parent::__construct();

        // use it if is defined also
        if ( $is_enabled = (int) RSFirewall_Config::get( 'enable_admin_slug', 0 ) && strlen(RSFirewall_Config::get( 'admin_slug_text', '' )) > 0) {
            $this->request_raw              = $_SERVER['REQUEST_URI'];
            $this->admin_slug               = RSFirewall_Config::get( 'admin_slug_text', '' );
			 // just in case
            $this->admin_slug               = trim($this->admin_slug);
            $this->is_permalink_structure   = get_option( 'permalink_structure' );

            $this->init();
        }
    }

    /**
     * Initiate the login slug
     */
    public function init() {
        // Call the function that needs to run on this hook
        $this->plugins_loaded();

        // Set the other actions and filters needed
        add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
        add_action( 'setup_theme', array( $this, 'setup_theme' ), 1 );
        add_filter( 'site_url', array( $this, 'site_url' ), 10, 4 );
        add_filter( 'wp_redirect', array( $this, 'wp_redirect' ), 10, 2 );
        remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
    }

    public function plugins_loaded() {
        global $pagenow;

        // If not multiple site than these 2 will redirect to the admin login, for better privacy we will stop them
        if ( !is_multisite() && ( strpos( $this->request_raw, 'wp-signup' )  !== false || strpos( $this->request_raw, 'wp-activate' ) )  !== false ) {
            wp_die( __( 'Access denied!', 'rsfirewall' ) );
        }

        if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) && $pagenow !== 'admin-post.php' ) {
            wp_die( __( 'Access denied!', 'rsfirewall' ), 403 );
        }

        $request = parse_url( $this->request_raw );

        if (is_null($request['path']) || !isset($request['path']))
        {
            $request['path'] = '';
        }

        if ( ! is_admin() && ( strpos( rawurldecode( $this->request_raw ), 'wp-login.php' ) !== false || untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) ) {
            $this->use_login = true;
            $this->request_raw = $this->do_trailingslashit( '/' . str_repeat( '-/', 10 ) );
            $pagenow = 'index.php';

        } elseif ( untrailingslashit( $request['path'] ) === home_url( $this->admin_slug, 'relative' ) || ( ! $this->is_permalink_structure
                && isset( $_GET[$this->admin_slug] )
                && empty( $_GET[$this->admin_slug] ) ) ) {
            $pagenow = 'wp-login.php';
        }
    }

    public function wp_loaded() {
        global $pagenow;

        $request = parse_url( $_SERVER['REQUEST_URI'] );

        if ( $pagenow === 'wp-login.php'
            && $request['path'] !== $this->do_trailingslashit( $request['path'] )
            && $this->is_permalink_structure ) {

            wp_safe_redirect( $this->slug_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

            die;

        } elseif ( $pagenow == 'index.php' && $this->use_login ) {

            if ( ( $referer = wp_get_referer() ) && strpos( $referer, 'wp-activate.php' ) !== false && ( $referer = parse_url( $referer ) ) && ! empty( $referer['query'] ) ) {

                parse_str( $referer['query'], $referer );

                if ( ! empty( $referer['key'] )
                    && ( $result = wpmu_activate_signup( $referer['key'] ) )
                    && is_wp_error( $result )
                    && ( $result->get_error_code() === 'already_active'
                        || $result->get_error_code() === 'blog_taken' ) ) {

                    wp_safe_redirect( $this->slug_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' ) );

                    die;
                }

            }

            if ( ! defined( 'WP_USE_THEMES' ) ) {
                define( 'WP_USE_THEMES', true );
            }

            wp();

            if ( $_SERVER['REQUEST_URI'] === $this->do_trailingslashit( str_repeat( '-/', 10 ) ) ) {
                $_SERVER['REQUEST_URI'] = $this->do_trailingslashit( '/wp-login-php/' );
            }

            require_once( ABSPATH . WPINC . '/template-loader.php' );

            die;

        } elseif ( $pagenow === 'wp-login.php' ) {
            global $error, $interim_login, $action, $user_login;

            if ( is_user_logged_in() && ! isset( $_REQUEST['action'] ) ) {
                wp_safe_redirect( admin_url() );
                die();
            }

            @require_once ABSPATH . 'wp-login.php';

            die;
        }
    }

    public function setup_theme() {
        global $pagenow;

        if ( ! is_user_logged_in() && 'customize.php' === $pagenow ) {
            wp_die( __( 'Access denied!', 'rsfirewall' ), 403 );
        }
    }

    public function site_url( $url, $path, $scheme, $blog_id ) {

        return $this->filter_wp_login( $url, $scheme );

    }

    public function wp_redirect( $location, $status ) {

        return $this->filter_wp_login( $location );

    }

    public function filter_wp_login( $url, $scheme = null ) {

        if ( strpos( $url, 'wp-login.php' ) !== false ) {

            if ( is_ssl() ) {
                $scheme = 'https';
            }

            $args = explode( '?', $url );

            if ( isset( $args[1] ) ) {
                parse_str( $args[1], $args );
                $url = add_query_arg( $args, $this->slug_login_url( $scheme ) );
            } else {
                $url = $this->slug_login_url( $scheme );
            }
        }

        return $url;

    }

    public function slug_login_url( $scheme = null ) {

        $separator  = $this->is_permalink_structure ? '' : '?';
        $url =  home_url( '/', $scheme ) .$separator. $this->admin_slug;

        return $this->is_permalink_structure ? $this->do_trailingslashit($url) : $url;
    }

    private function do_trailingslashit( $string ) {
        static $is_trailing_slashes;

        if (is_null($is_trailing_slashes)) {
            $is_trailing_slashes = ( '/' === substr( $this->is_permalink_structure, -1, 1 ) );
        }

        $string = (string) $string;

        return $is_trailing_slashes ? trailingslashit( $string ) : untrailingslashit( $string );
    }
}