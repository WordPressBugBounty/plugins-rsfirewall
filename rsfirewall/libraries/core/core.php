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

class RSFirewall_Core {

    /**
     * @var
     */
    protected $args;

    /**
     * @var
     */
    protected $files;

    /**
     * The IP of the visitor
     *
     * @since 1.0.0
     * @var string, ip
     */
    protected $ip;
    /**
     * The referer of the visitor
     *
     * @since 1.0.0
     * @var string, referer
     */
    protected $referer;
    /**
     * The USER AGENT of the visitor
     *
     * @since 1.0.0
     * @var string, user agent
     */
    protected $agent = '';
    /**
     * The reason
     *
     * @since 1.0.0
     * @var string
     */
    protected $reason = '';
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;
    /**
     * Hold's the logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $logger The instance of the logger.
     */
    protected $logger;
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Holds the current user, if exists
     */
    protected $current_user;

    /**
     * Holds the admin status
     */
    protected $is_admin;

    /**
     * Holds the post types prefix
     */
    protected $prefix;

    /**
     * @return string
     */
    public function get_referer() {
        return $this->referer;
    }

    /**
     * @return string
     */
    public function get_agent() {
        return $this->agent;
    }

    /**
     * @return string
     */
    public function get_reason() {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function set_reason( $reason ) {
        $this->reason = $reason;
    }


    /**
     * Grabs a configuration by key or return false
     *
     * @param null $key
     *
     * @since 1.0.0
     *
     * @return array|mixed
     */
    public function get_config( $key = NULL, $default = false ) {
        if ( $key ) {
            return RSFirewall_Config::get( $key, $default );
        }

        return $default;
    }

    /**
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $plugin                 = RSFirewall::get_instance();
        $this->plugin_name      = $plugin->get_plugin_name();
        $this->version          = $plugin->get_version();
        $this->prefix           = RSFIREWALL_POSTS_PREFIX;

        $this->agent   = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        $this->referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';

        $this->is_admin     = is_admin();
        $this->current_user = wp_get_current_user();
        $this->ip           = RSFirewall_Helper::get_ip();
        $this->logger       = RSFirewall_Helper_Logger::get_instance();
    }

    /**
     * @param null $call
     * @param null $args
     *
     * @return mixed
     * @throws Exception
     *
     * @since 1.0.0
     */
    protected function call_method( $call = NULL, $args = NULL ) {

        if ( !isset( $call ) ) {
            return false;
        }
        if ( ! empty( $args ) ) {
            $this->args = $args;
        }

        //sanitize the function
        $function = str_replace( '-', '_', $call );

        if ( ! method_exists( $this, $function ) ) {
            throw new Exception( esc_html__( 'Method not found.', 'rsfirewall' ) );
        }

        return $this->$function( $this->args );
    }

    /**
     * Get the files hashes from the database
     *
     * @since 1.0.0
     * @return array|null|object
     */
    public function get_hashes($type = null) {
        // The type must be specified
        if (is_null($type)) {
            return false;
        }

        global $wpdb;
        $old = $wpdb->show_errors();
       

        $table = $wpdb->prefix . 'rsfirewall_hashes';

        $results = $wpdb->get_results(
            "SELECT *
			 FROM $table
			 WHERE `type` = '$type'
			 AND `flag` != 'C'
			",
            OBJECT
        );

        $wpdb->show_errors($old);

        return $results;
    }

    /**
     * Set the flag to 'C'(checked) so we don't flood the system log with it
     *
     * @since 1.0.0
     * @return array|null|object
     */
    public function add_checked_status( $file, $wp_version = null ) {
        global $wpdb;

        $old = $wpdb->show_errors();
        
        $table = $wpdb->prefix . 'rsfirewall_hashes';

        $where = array(
            'file' => $file->file
        );

        if (!is_null($wp_version)) {
            $where['type'] = $wp_version;
        }

        $result = $wpdb->update( $table, array( 'flag' => 'C', 'date' => current_time( 'mysql' ) ), $where );

        $wpdb->show_errors($old);

        return $result;
    }

    /**
     * Helper function to determine if the IP is whitelisted/blocklisted
     *
     * @param $ip
     *
     * @return mixed
     */
    public function is_listed( $ip, $type ) {
        static $cache = array();

        if ( ! isset( $cache[ $type ] ) ) {

            $args  = array(
                'post_type'  => $this->prefix.'lists',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'rsfirewall_type',
                        'value'   => $type,
                        'compare' => '='
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'rsfirewall_ip',
                            'value'   => $ip,
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'rsfirewall_ip',
                            'value'   => '*',
                            'compare' => 'LIKE',
                        ),
                        array(
                            'key'     => 'rsfirewall_ip',
                            'value'   => '/',
                            'compare' => 'LIKE',
                        ),
                        array(
                            'key'     => 'rsfirewall_ip',
                            'value'   => '-',
                            'compare' => 'LIKE',
                        )
                    )
                ),
            );
            $query = new WP_Query( $args );
            // defaults to false
            $cache[ $type ] = false;

            if ( $query->post_count >= 1 ) {
                try {
                    $class = new RSFirewall_IP( $ip );
                    foreach ( $query->posts as $result ) {
                        try {
                            if ( $class->match( get_post_meta( $result->ID, 'rsfirewall_ip', true ) ) ) {
                                // found a match
                                $cache[ $type ] = true;

                                $meta_reason = get_post_meta($result->ID, 'rsfirewall_reason', true);
								
                                // cache the reason
                                $this->set_reason( $meta_reason );
                                break;
                            }
                        } catch ( Exception $e ) {
                            continue;
                        }
                    }
                } catch ( Exception $e ) {

                }
            }
        }

        return $cache[ $type ];
    }

    public function count_autoban($reason){
        $this->capture_offender();

        if ($this->count_offenders() >= $this->get_config('autoban_attempts')) {
            $this->clear_offender();

            $this->add_to_blacklist(array('reason'=> $reason));
        }
    }

    /**
     * Capture the offender
     *
     * @param args
     *
     * @since 1.0.0
     * @throws Exception
     */
    public function capture_offender( $args = null ) {
        global $wpdb;

        $old = $wpdb->show_errors();

        if (is_null($args)) {
            $args = array(
                'ip'   => $this->ip,
                'date' => current_time( 'mysql', true ),
            );
        }

        $table = $wpdb->prefix . 'rsfirewall_offenders';

        $query = $wpdb->insert(
            $table,
            $args
        );

       $wpdb->show_errors($old);
    }

    /**
     * Count the number of offenders of a certain IP
     *
     * @since 1.0.0
     *
     * @return int
     *
     * @param $ip
     */
    public function count_offenders( $ip = null ) {
        if (is_null($ip)) {
            $ip = $this->ip;
        }
        return RSFirewall_Helper::count_offenders($ip);
    }

    /**
     * Clear the IP from the offender table
     *
     * @param $ip
     *
     * @return bool
     */
    public function clear_offender( $ip = null ) {
        global $wpdb;
        if (is_null($ip)) {
            $ip = $this->ip;
        }

        $old = $wpdb->show_errors();

        $table  = $wpdb->prefix . 'rsfirewall_offenders';
        $result = $wpdb->delete( $table, array( 'ip' => $ip ) );

        $wpdb->show_errors($old);

        if ( $result ) {
            return true;
        }

        return false;
    }

    /**
     * Clears the threats posts that are older then 'x' days the user specified
     */
    public function clear_log_history(){
        $days = (int) $this->get_config('log_history');
        if ( empty($days) ) {
            return false;
        }

        $args = array(
            'post_type' => $this->prefix.'threats',
            'posts_per_page' => 50,
            'date_query' => array(
                'column' => 'post_date',
                'before' => $days.' days ago'
            )
        );

        $query = new WP_Query($args);

        if ( $query->post_count == 0 ) {
            return false;
        }

        if ($posts = $query->posts) {
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }

        return true;
    }

    /**
     * The offenders must be kept only 3 days
     */
    public function clear_old_offenders() {
        global $wpdb;

        $time = current_time('timestamp');
        $time -= 259200; // 3 days ago

        $date = date_i18n('Y-m-d h:i:s', $time, true);
        $table  = $wpdb->prefix . 'rsfirewall_offenders';

        $query = $wpdb->prepare("DELETE FROM $table WHERE `date` < %s LIMIT 50", $date);
        $wpdb->query($query);
    }

    /**
     * Add an ip to the blocklist
     *
     * @param $data ;
     *
     * @return mixed
     * @throws Exception
     */
    public function add_to_blacklist( $data ) {
        $data['type'] = 0;
        // add the current IP if is not present
        if (!isset($data['ip'])) {
            $data['ip'] = $this->ip;
        }

        $this->add_to_list( $data );
    }

    /**
     * Add an ip to the black/white list
     *
     * @since 1.0.0
     *
     * @param $args
     */
    public function add_to_list( $args ) {
        $list_item = array(
            'post_title'  => $args['reason'],
            'post_status' => 'publish',
            'post_type'   => $this->prefix.'lists',
            'meta_input'  => array(
                'rsfirewall_ip'   => $args['ip'],
                'rsfirewall_type' => $args['type'],
                'rsfirewall_reason' => $args['reason'],
            ),
        );

        wp_insert_post( $list_item );
    }

    /**
     * Checks if the IP is whitelisted
     *
     * @since 1.0.0
     *
     * @return mixed
     * @throws Exception
     */
    protected function is_whitelisted() {
        return $this->is_listed($this->ip, 1);
    }

    /**
     * Checks if the IP is blocklisted
     *
     * @since 1.0.0
     *
     * @return mixed
     * @throws Exception
     */
    protected function is_blacklisted($force_logging = false) {
        $result = $this->is_listed($this->ip, 0);

        if ( $result ) {
            if (empty($this->reason)) {
                $this->reason = esc_html__('Blocklisted in our database', 'rsfirewall');
            }

            if ($force_logging || ($this->get_config( 'enable_active_scanner' ) && $this->get_config('log_all_blocked_attempts'))) {
                $args = array(
                    'level' => 'medium',
                    'code' => 'IP_BLOCKED',
                    'debug_variables' => ''
                );

                $this->logger->add($args);
            }
        }

        return $result;
    }

    /**
     * Restrict acces to your website based on the user's geo location
     *
     * @since 1.0.0
     * @return bool
     * @throws Exception
     */
    protected function is_geo_ip_blacklisted($force_logging = false) {
        $blocked_countries  = (array) $this->get_config('blocked_countries', array());

        // no countries blocked, no need to continue logic
        if (empty( $blocked_countries ) ) {
            return false;
        }

        $geoip = RSFirewall_GeoIp::get_instance();


        global $wp_session;
        // result already cached in the session so grab it to avoid reparsing the database
        if ( !empty($wp_session['rsfirewall.geoip']) ) {
            $code = $wp_session['rsfirewall.geoip'];
        } else { // not in cache

            $code = $geoip->get_country_code( $this->ip );

            $wp_session['rsfirewall.geoip'] = $code;
        }

        if ( $code != '' && in_array( $code, $blocked_countries ) ) {
            if ( $force_logging || ($this->get_config( 'enable_active_scanner' ) && $this->get_config('log_all_blocked_attempts')) ) {
                $args = array(
                    'level'           => 'low',
                    'code'            => 'GEOIP_BLOCKED',
                    'debug_variables' => sprintf( esc_html__( 'Country code: %s', 'rsfirewall' ), $code )
                );

                $this->logger->add($args);
            }

            $this->reason = sprintf( esc_html__( 'Your location (%s) has been blocked.', 'rsfirewall' ), $code );

            return true;
        }

        return false;

    }

    /**
     * Checks if the USER AGENT is blocklisted
     *
     * @since 1.0.0
     *
     * @return bool
     * @throws Exception
     */
    protected function is_user_agent_blacklisted() {
        $agent = $this->agent;

        $verify_agents = $this->get_config('dos_enable_protections_for');

        /*
         * Provide defaults
         */
        if ( !$verify_agents ) {
            $content =  file_get_contents(RSFIREWALL_BASE . 'models/configuration.xml');
            $verify_agents = array();

            if (preg_match_all('#<field name="dos_enable_protections_for"((.*)+?)[">]([\s\S]*?)</field>#', $content, $match)) {
                $match[3][0] = trim($match[3][0]);
                $options = explode("\n",$match[3][0]);

                foreach($options as $option) {
                    $option = explode('value="', $option, 2);
                    list($agent, $rest) = explode('"', $option[1], 2);

                    $verify_agents[] = $agent;
                }

            }
        }

        $this->reason = esc_html__( 'Malware Detected', 'rsfirewall' );

        // empty user agents are not allowed!
        if ( in_array( 'empty', $verify_agents ) && ( empty( $agent ) || $agent == '-' ) ) {
            if ( $this->get_config('log_all_blocked_attempts') ) {
                $args = array(
                    'level'           => 'low',
                    'code'            => 'EMPTY_USER_AGENT'
                );

                $this->logger->add($args);
            }

            return true;
        }

        // perl user agent - but allow w3c validator
        if ( in_array( 'perl', $verify_agents ) && preg_match( '#libwww-perl#', $agent ) && ! preg_match( '#^W3C-checklink#', $agent ) ) {
            if ( $this->get_config('log_all_blocked_attempts') ) {
                $args = array(
                    'level'           => 'low',
                    'code'            => 'PERL_USER_AGENT'
                );

                $this->logger->add($args);
            }

            return true;
        }

        // curl user agent
        if ( in_array( 'curl', $verify_agents ) && preg_match( '#curl#', $agent ) ) {
            if ( $this->get_config('log_all_blocked_attempts') ) {
                $args = array(
                    'level'           => 'low',
                    'code'            => 'CURL_USER_AGENT'
                );

                $this->logger->add($args);
            }

            return true;
        }

        // Java user agent
        if ( in_array( 'java', $verify_agents ) && preg_match( '#^Java#', $agent ) ) {
            if ( $this->get_config('log_all_blocked_attempts') ) {
                $args = array(
                    'level'           => 'low',
                    'code'            => 'JAVA_USER_AGENT'
                );

                $this->logger->add($args);
            }

            return true;
        }

        // Mozzila impersonator
        if (in_array('mozilla', $verify_agents)) {
            $patterns = array( '#^Mozilla\/5\.0$#', '#^Mozilla$#' );
            foreach ( $patterns as $i => $pattern ) {
                if ( preg_match( $pattern, $agent, $match ) ) {

                    if ( $this->get_config('log_all_blocked_attempts') ) {
                        $args = array(
                            'level' => 'low',
                            'code' => 'MOZILLA_USER_AGENT'
                        );

                        $this->logger->add($args);
                    }

                    return true;
                }
            }
        }

        // Really dangerous user agents here
        $patterns = array( '#c0li\.m0de\.0n#', '#<\?(.*)\?>#' );
        foreach ( $patterns as $i => $pattern ) {
            if ( preg_match( $pattern, $agent, $match ) ) {
                $args = array(
                    'level'           => 'medium',
                    'code'            => 'DANGEROUS_USER_AGENT',
                    'debug_variables' => $agent
                );

                $this->logger->add($args);

                return true;
            }
        }

        // unset here
        $this->reason = '';

        return false;
    }

    /**
     * Check if the referer is blocklisted
     *
     * @since 1.0.0
     * @return bool
     */
    protected function is_referer_blacklisted() {
        // No point going forward if no referer is found.
        if ( ! strlen( $this->referer ) ) {
            return false;
        }

        // If we haven't set any domains to be blocked, exit.
        $deny_referers = $this->get_config('deny_access_to_referers');
        // in case of white spaces
        $deny_referers = trim($deny_referers);

        if ( empty( $deny_referers ) ) {
            return false;
        }

        try {
            // Try to parse the referer
            $url_helper = new RSFirewall_Helper_URL( $this->referer );
            $host       = $url_helper->getHost();
            if ( ! strlen( $host ) ) {
                return false;
            }

            $deny_referers = RSFirewall_Helper::explode($deny_referers);

            foreach ( $deny_referers as $denied_referer ) {
                $denied_host = strtolower( $denied_referer );

                if ( ! strlen( $denied_host ) ) {
                    continue;
                }

                // Check for wildcards
                if ( strpos( $denied_host, '*' ) !== false ) {
                    $parts = explode( '*', $denied_host );
                    array_walk( $parts, array( $this, 'preg_quote_array' ) );

                    $pattern = '/^' . implode( '.*', $parts ) . '$/is';
                    $deny    = preg_match( $pattern, $host );
                } else {
                    $deny = $denied_host == $host;
                }

                if ( $deny ) {
                    // set the reason
                    $this->reason = esc_html__( 'Referer blocked.', 'rsfirewall' );
                    if ( $this->get_config('log_all_blocked_attempts') ) {
                        $args = array(
                            'level'           => 'medium',
                            'code'            => 'REFERER_BLOCKED',
                            'debug_variables' => $denied_host
                        );

                        $this->logger->add($args);
                    }

                    return true;
                }
            }
        } catch ( Exception $e ) {}

        return false;
    }

    /**
     * @param $data
     *
     * @return array|bool
     */
    protected function decode_data($data) {
        $result = array();

        if (is_array($data[0])) {
            foreach ($data[0] as $k => $v) {
                $result[$k] = $this->decode_data(
                    array(
                        $data[0][$k],
                        $data[1][$k],
                        $data[2][$k],
                        $data[3][$k],
                        $data[4][$k]
                    )
                );
            }
            return $result;
        }

        $this->files[] = (object) array(
            'name' => $data[0],
            'type' => $data[1],
            'tmp_name' => $data[2],
            'error' => $data[3],
            'size' => $data[4]
        );

        return true;
    }

    /**
     * @return array
     */
    protected function decode_files() {
        $this->files = array();
        foreach ($_FILES as $k => $v) {
            $this->decode_data(
                array(
                    $v['name'],
                    $v['type'],
                    $v['tmp_name'],
                    $v['error'],
                    $v['size']
                )
            );
        }

        $results = $this->files;
        unset($this->files);

        return $results;
    }

    /**
     * @param $item
     * @param $key
     */
    protected function preg_quote_array( &$item, $key ) {
        $item = preg_quote( $item, '/' );
    }

    /**
     * check if the visitor is a bot (crawler)
     */
    protected function is_bot() {
        static $result;

        if (is_null($result)) {
            try {
                // Get the hostname address
                $hostname 	= RSFirewall_Helper::cache_call('rsfirewall_host_by_addr', array($this, 'get_host_by_addr'), array('transient' => 20, 'cache' => 15), $this->ip);
                $isGoogle 	= substr($hostname, -14) == '.googlebot.com';
                $isMSN 		= substr($hostname, -15) == '.search.msn.com';
                // Check if it's a search engine domain
                if ($isGoogle || $isMSN) {
                    $ip 	= RSFirewall_Helper::cache_call('rsfirewall_addr_by_host', array($this, 'get_addr_by_host'),  array('transient' => 20, 'cache' => 15), $hostname);
                    $result = $this->ip === $ip;
                } else {
                    $result = false;
                }
            } catch (Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    public function get_host_by_addr($ip) {
        require_once RSFIREWALL_BASE.'libraries/Net/DNS2.php';

        $resolver = new Net_DNS2_Resolver(array(
            'nameservers' => array(
                '8.8.8.8', '8.8.4.4' // Google DNS
            ),
            'timeout' => 2
        ));
        $result = $resolver->query($ip, 'PTR');
        if ($result && isset($result->answer[0]->ptrdname)) {
            return $result->answer[0]->ptrdname;
        }

        return $ip;
    }

    public function get_addr_by_host($ip) {
        require_once RSFIREWALL_BASE.'libraries/Net/DNS2.php';

        $resolver = new Net_DNS2_Resolver(array(
            'nameservers' => array(
                '8.8.8.8', '8.8.4.4' // Google DNS
            ),
            'timeout' => 2
        ));
        $result = $resolver->query($ip, 'A');
        if ($result && isset($result->answer[0]->address)) {
            return $result->answer[0]->address;
        }

        return false;
    }

    // Gets the current Url
    protected function get_current_url() {
        static $current_url;

        if (is_null($current_url)) {
            $home_url = home_url();
            $path_home_url = parse_url($home_url);

            if (isset($path_home_url['path'])) {
                $path_home_url = $path_home_url['path'];
            } else {
                $path_home_url = false;
            }

            $current_url = $home_url . $_SERVER['REQUEST_URI'];
            if ($path_home_url) {
                $current_url_data = parse_url($current_url);

                if (stripos($current_url_data['path'], $path_home_url) !== false && stripos($current_url_data['path'], $path_home_url) == 0) {
                    $offset = (int)strlen($path_home_url);
                    $current_url_data['path'] = substr_replace($current_url_data['path'], '', 0, $offset);
                }

                $current_url_data['scheme'] = $current_url_data['scheme'] . '://';
                if (isset($current_url_data['query']) && !empty($current_url_data['query'])) {
                    $current_url_data['query'] = '?' . $current_url_data['query'];
                }
                $current_url = implode($current_url_data);
            }
        }

        return $current_url;
    }

    /**
     * Function to check if a plugin is active or not
     * @param null $plugin, accept string
     * @return bool
     */
    protected function is_plugin_active($plugin = null) {
        if (is_null($plugin) || !is_string($plugin)) {
            return false;
        }

        static $states = array();

        if (!isset($states[$plugin])) {
            if (!function_exists('is_plugin_active')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }

            $states[$plugin] = is_plugin_active($plugin);
        }

        return $states[$plugin];
    }
}