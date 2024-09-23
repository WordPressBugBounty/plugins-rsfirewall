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

class RSFirewall_Model_Threats extends RSFirewall_Post {
    /**
     * Create a custom post type to hold the threats
     */
    static $check_post= false;

    public function init() {
        // Set UI labels for Custom Post Type
        $labels = array(
            'name'               => _x( 'Threats', 'Post Type General Name', 'rsfirewall' ),
            'singular_name'      => _x( 'Threat', 'Post Type Singular Name', 'rsfirewall' ),
            'menu_name'          => esc_html__( 'Threat', 'rsfirewall' ),
            'all_items'          => esc_html__( 'All Threats', 'rsfirewall' ),
            'view_item'          => esc_html__( 'View Threat', 'rsfirewall' ),
            'edit_item'          => esc_html__( 'Edit Threat', 'rsfirewall' ),
            'update_item'        => esc_html__( 'Update Threat', 'rsfirewall' ),
            'search_items'       => esc_html__( 'Search Threat', 'rsfirewall' ),
            'not_found'          => esc_html__( 'Not Found', 'rsfirewall' ),
            'not_found_in_trash' => esc_html__( 'Not found in Trash', 'rsfirewall' ),
        );

        // Set other options for Custom Post Type
        $args = array(
            'label'                => esc_html__( 'Threats', 'rsfirewall' ),
            'description'          => esc_html__( 'Blocked attempts on your website', 'rsfirewall' ),
            'labels'               => $labels,
            'supports'             => false,
            'hierarchical'         => false,
            'show_ui'              => true,
            'show_in_menu'         => false,
            'show_in_nav_menus'    => false,
            'show_in_admin_bar'    => true,
            'menu_position'        => 5,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => true,
            'publicly_queryable'   => false,
            'rewrite'              => array( "slug" => $this->prefix."threats" ),
            'capabilities'         => array(
                'create_posts' => false,
                'delete_posts' => true,
                'delete_post' => true,
                // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
            ),
            'map_meta_cap'         => true,
            'register_meta_box_cb' => array( $this, 'add_threats_metabox' )
        );

        // Registering your Custom Post Type
        register_post_type( $this->prefix.'threats', $args );

        // Modify the query so that the search and filters work properly
        add_filter('pre_get_posts', array($this, 'refine_query'));

        // Modify the get_search_query for proper display
        add_filter('get_search_query', array($this, 'get_search_query'));

        // Add custom filters
        add_action( 'restrict_manage_posts', array($this, 'add_filters') );
        add_action( 'manage_posts_extra_tablenav', array($this, 'add_empty_button') );

    }

	public function add_empty_button($which) {
		global $pagenow, $typenow;

		if ($pagenow == 'edit.php' && $typenow == 'rsf_threats') {
			$edit_url = admin_url( 'edit.php?post_type='.$this->prefix.'threats');
			$url = wp_nonce_url($edit_url, 'rsfirewall', 'rsf-actions') . '&handler=threats&task=emptylog';

			echo '<a href="' . $url . '" class="rsfirewall-btn small danger" onclick="return confirm(\'' . esc_js(__('Are you sure you want to empty the log?', 'rsfirewall')) . '\');">' . __( 'Empty Log', 'rsfirewall' ) . '</a>';
		}
	}

    /**
     * Function to add the filters
     */
    function add_filters(){
        global $pagenow, $typenow;

        if ($pagenow == 'edit.php' && $typenow == 'rsf_threats') {
            $filters = array(
                'level' => array(
                    esc_attr__('Select Level', 'rsfirewall') => 'all',
                    esc_attr__('Low', 'rsfirewall')          => 'low',
                    esc_attr__('Medium', 'rsfirewall')       => 'medium',
                    esc_attr__('High', 'rsfirewall')         => 'high',
                    esc_attr__('Critical', 'rsfirewall')     => 'critical'
                ),
                'type' => array(
                    esc_attr__('Select Blocked Status', 'rsfirewall') => 'all',
                    esc_attr__('Blocked', 'rsfirewall')               => '0',
                    esc_attr__('Not blocked', 'rsfirewall')           => '1',
                )
            );

            foreach ($filters as $filter => $options) {
                ?>
                <select name="rsf_filter[<?php echo esc_attr($filter);?>]">
                    <?php
                    $current_v = (isset($_GET['rsf_filter']) && isset($_GET['rsf_filter'][$filter])) ? $_GET['rsf_filter'][$filter] : '';
                    foreach ($options as $label => $value) {
                        printf
                        (
                            '<option value="%s"%s>%s</option>',
                            $value,
                            $value == $current_v ? ' selected="selected"' : '',
                            $label
                        );
                    }
                    ?>
                </select>
                <?php
            }
        }
    }

    /**
     * Function used for handling the search and filters
     */
    public function refine_query($query) {
        if (self::$check_post) {
            return;
        }

        global $pagenow, $typenow, $wpdb;

        if ($pagenow == 'edit.php' && $typenow == $this->prefix.'threats') {
            $custom_fields = array(
                "rsfirewall_ip",
                "rsfirewall_user_id",
                "rsfirewall_username",
                "rsfirewall_referer",
                "rsfirewall_page",
            );

            // Handle the search term
            $searchterm = $query->query_vars['s'];

            // unset the 's' value from the query, because we do not need it anymore and interferes with our query
            $query->query_vars['s'] = "";

            $meta_query_search = array('relation' => 'OR');
            if (strlen($searchterm) != 0) {
                foreach($custom_fields as $cf) {
                    array_push($meta_query_search, array(
                        'key' => $cf,
                        'value' => esc_sql($searchterm),
                        'compare' => 'LIKE'
                    ));
                }
            };

            // Handle our own filter values
            $meta_query_filters = array('relation' => 'AND');
            if (isset($_GET['rsf_filter']) && !empty($_GET['rsf_filter'])) {
                foreach ($_GET['rsf_filter'] as $field => $value){
                    // in case the default value of a filter is set to 'all'
                    if ($value == 'all') {
                        continue;
                    }

                    if ($field == 'type') {

                        array_push($meta_query_filters, array(
                            'key' => 'rsfirewall_ip',
                            'value' => $this->get_blacklisted(),
                            'compare' => (int) $value == 0 ? 'IN' : 'NOT IN'
                        ));
                    } else {
                        array_push($meta_query_filters, array(
                            'key' => 'rsfirewall_' . esc_sql($field),
                            'value' => esc_sql($value),
                            'compare' => '='
                        ));
                    }
                }
            }

            $is_search = count($meta_query_search) > 1 ? 1 : 0;
            $is_filters = count($meta_query_filters) > 1 ? 3 : 0;

            $meta_query = array();
            switch ($combine = ($is_search + $is_filters)) {
                // when only the search is used
                case 1:
                    $meta_query = $meta_query_search;
                break;

                // when only the filters are used
                case 3:
                    $meta_query = $meta_query_filters;
                break;

                // when both filters and search are used
                case 4:
                    array_push($meta_query_filters, $meta_query_search);
                    $meta_query = $meta_query_filters;
                break;
            }

            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Function used internally for getting all the whitelisted IP's from the lists post type
     */
    protected function get_blacklisted($implode = true) {
        global $wpdb;

        static $blacklisted_ips;

        if (is_null($blacklisted_ips)) {
            $blacklisted_ips = $wpdb->get_col("
              SELECT `meta_value` FROM `".$wpdb->prefix."postmeta`as `c`
              WHERE `meta_key` = 'rsfirewall_ip'
              AND 0 IN (SELECT `meta_value` FROM `".$wpdb->prefix."postmeta` WHERE `meta_key` = 'rsfirewall_type' AND `post_id`=`c`.`post_id`)");
        }

        if ($implode) {
            static $imploded;
            if (is_null($imploded)) {
                $imploded = implode(', ', $blacklisted_ips);
            }
            return $imploded;
        } else {
            return $blacklisted_ips;
        }
    }

    /**
     * Function used for handling the text of the query that is outputed in the "Search results for" statement
     */
    public function get_search_query($search_term) {
        global $pagenow, $typenow;

        if ($pagenow == 'edit.php' && $typenow == $this->prefix.'threats') {
            // Empty the search term in case there is a value
            $search_term = '';

            if (isset($_GET['s']) && !empty($_GET['s'])) {
                $search_term = esc_attr($_GET['s']);
            }
        }

        return $search_term;
    }

    /**
     * Save post.
     *
     * @param $post_id
     * @param $post
     *
     * @return int|void
     */
    public function save( $post_id, $post ) {
        $meta = get_post_meta( $post_id );
        foreach ( $meta as $key => $val ) {
            if ( array_key_exists( 'rsfirewall_' . $key, $_POST ) ) {
                $value =  sanitize_text_field($_POST[ 'rsfirewall_' . $key ]);

                update_post_meta( $post_id,
                    'rsfirewall_' . $key,
                    $value
                );
            }
        }
    }

    /**
     * Add Columns to the Threat Tables
     *
     * @param $columns
     *
     * @return array
     */
    public function columns( $columns )
    {
        $columns = array(
            'cb'                         => '<input type="checkbox" />',
            'actions'                    => esc_html__( 'Actions', 'rsfirewall' ),
            'rsfirewall_level'           => esc_html__( 'Level', 'rsfirewall' ),
            'rsfirewall_ip'              => esc_html__( 'IP Address', 'rsfirewall' ),
            'rsfirewall_username'        => esc_html__( 'Username', 'rsfirewall' ),
            'rsfirewall_user_id'         => esc_html__( 'User Id', 'rsfirewall' ),
            'rsfirewall_referer'         => esc_html__( 'Referer', 'rsfirewall' ),
            'rsfirewall_code'            => esc_html__( 'Description', 'rsfirewall' ),
            'rsfirewall_page'            => esc_html__( 'Page', 'rsfirewall' ),
            'rsfirewall_date'            => esc_html__( 'Date of event', 'rsfirewall' )
        );

        return $columns;
    }

    /**
     * Display data in the backend table
     *
     * @param $column
     * @param $post_id
     */
    public function column( $column, $post_id )
    {
        $value = $column != 'rsfirewall_date' ? get_post_meta( $post_id, $column, true ): '';

        // output filter for the columns content, if needed
        switch($column) {
            case 'rsfirewall_ip':
                static $placeholders = array();
                if ( empty( $placeholders ) ) {
                    // Load the config to get our variables
                    $placeholders['ipv4'] = RSFirewall_Config::get('ipv4_whois_service' );
                    $placeholders['ipv6'] = RSFirewall_Config::get('ipv6_whois_service' );

                    // Also require our IP class
                    require_once RSFIREWALL_BASE . 'libraries/ip/ip.php';
                }

                $placeholder = '';
                $ip = $value;
                if ( RSFirewall_IPv4::test( $ip ) ) {
                    $placeholder = $placeholders['ipv4'];
                } elseif ( RSFirewall_IPv6::test( $ip ) ) {
                    $placeholder = $placeholders['ipv6'];
                }

                if ( $placeholder ) {
                    $link = str_ireplace( '{ip}', urlencode( $ip ), $placeholder );
                    $value = '<a target="_blank" href="' . $link . '" class="rsf-ip-address">' . htmlentities( $ip, ENT_COMPAT, 'utf-8' ) . '</a>';
                } else {
                    $value = '<span class="rsf-ip-address">' . htmlentities( $ip, ENT_COMPAT, 'utf-8' ) . '</span>';
                }

             break;

            case 'rsfirewall_code':
                $codes = RSFirewall_i18n::get_locale('codes');
                $value = isset($codes[$value]) ? $codes[$value] : $value;

                $variables = get_post_meta($post_id, 'rsfirewall_debug_variables', true);
                if (!empty($variables)){
                    $value = sprintf($value, $variables);
                }
				$value = wp_kses_post($value);
            break;

            case 'actions':
                $edit_url = admin_url( 'edit.php?post_type='.$this->prefix.'threats');
                $meta_ip = get_post_meta($post_id, 'rsfirewall_ip', true);

                if (in_array($meta_ip, $this->get_blacklisted(false))) {
                    $value = '<a href="' . wp_nonce_url($edit_url, 'rsfirewall', 'rsf-actions') . '&handler=threats&task=unblock&id=' . $post_id . '" class="rsfirewall-btn primary small" type="button" id="rsf-list-status" data-pid="' . $post_id . '">' . __('Unblock', 'rsfirewall') . '</a>';
                } else {
                    $value = '<a href="' . wp_nonce_url($edit_url, 'rsfirewall', 'rsf-actions') . '&handler=threats&task=block&id=' . $post_id . '" class="rsfirewall-btn danger small" type="button" id="rsf-list-status" data-pid="' . $post_id . '">' . __('Block', 'rsfirewall') . '</a>';
                }
            break;

            case 'rsfirewall_date':
                $date_format = get_option('date_format', 'Y-m-d');
                $time_format = get_option('time_format', 'H:i:s');
                $value = get_the_time( $date_format.' '.$time_format, $post_id );
            break;
        }

        if ( ! empty( $value ) ) {
            echo $value;
        }
    }

    /**
     * Make columns sortable
     *
     * @param $columns
     *
     * @return mixed
     */
    public function sortable_columns( $columns )
    {
        $columns['rsfirewall_level']    = 'rsfirewall_level';
        $columns['rsfirewall_ip']       = 'rsfirewall_ip';
        $columns['rsfirewall_code']     = 'rsfirewall_code';
        $columns['rsfirewall_username'] = 'rsfirewall_username';
        $columns['rsfirewall_user_id']  = 'rsfirewall_user_id';
        $columns['rsfirewall_referer']  = 'rsfirewall_referer';
        $columns['rsfirewall_page']     = 'rsfirewall_page';
        $columns['rsfirewall_date']     = 'rsfirewall_date';

        return $columns;
    }

    /**
     * Display bulk actions.
     *
     * @param $actions
     *
     * @return array
     */
    public function bulk_actions($actions)
    {
        // Remove the edit action
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }

        // Add our new actions at the beginning of the actions array
        $actions = array_merge(
            array(
                'whitelist'=> esc_html__('Add to safelist', 'rsfirewall'),
                'blacklist' => esc_html__('Add to blocklist', 'rsfirewall')
            )
            , $actions );


        return $actions;
    }

    /**
     * Handle the bulk actions
     *
     * @param $redirect_to - where to redirect after the action has been done
     * @param $action - the action that is called upon
     * @param $post_ids - the array containing the selected ids
     *
     * @return string - the proper redirect
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {

        $modify_actions = array('whitelist', 'blacklist');

        if (!in_array($action, $modify_actions)) {
            return $redirect_to;
        }

        $error_msg = '';
        $added = 0;
        // Insert selected threats ip
        foreach ($post_ids as $i => $post_id) {
            $post_meta = get_post_meta($post_id, 'rsfirewall_ip', true);

            $type = ($action == 'whitelist' ? '1' : '0');

            if ($post_meta == $_SERVER['SERVER_ADDR'] && $action == 'blacklist') {
                if (empty($error_msg)) {
                    $error_msg = __('You cannot block your own server IP', 'rsfirewall');
                }
                continue;
            }

            $list_data = array(
                'post_title'  => '',
                'post_status' => 'publish',
                'post_type'   => $this->prefix.'lists',
                'meta_input'  => array(
                    'rsfirewall_ip'     => $post_meta,
                    'rsfirewall_type'   => $type,
                    'rsfirewall_reason' => $type == '0' ? __('This IP is blocklisted', 'rsfirewall') : ''
                ),
            );


            // Check if this post ip meta data is already inserted in the blocklist/safelist
            if (!$this->check_if_listed($post_meta)) {
                wp_insert_post($list_data);
                $added++;
            }
        }

        if (!empty($error_msg)) {
            $this->set_message($error_msg);
        }

        if ($added > 0) {
            $this->set_message(sprintf(__('You have added %s IP to the %s.', 'rsfirewall'), $added, $action), 'success');
        }

        return $redirect_to;
    }

    /**
     * Add an IP to the blocklist
     */
    public function block() {
        $post_id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
        $back_url = wp_get_referer();

        $block = true;
        // Check if the id is not empty (0)
        if (empty($post_id)) {
            $this->set_message(__('The post id is not correct!', 'rsfirewall'));
            $block = false;
        }

        // Check if the id is available
        if (!get_post_status($post_id)) {
            $this->set_message(__('This post does not exist!', 'rsfirewall'));
            $block = false;
        }

        if ($block) {
            $this->handle_bulk_actions($back_url, 'blacklist', array($post_id));
        }
        // Redirect to the list
        RSFirewall_Helper::redirect($back_url);
    }

	public function emptylog()
	{
		$back_url = wp_get_referer();

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare("
            DELETE posts,pt,pm
            FROM ".$wpdb->prefix."posts posts
            LEFT JOIN ".$wpdb->prefix."term_relationships pt ON pt.object_id = posts.ID
            LEFT JOIN ".$wpdb->prefix."postmeta pm ON pm.post_id = posts.ID
            WHERE posts.post_type = %s
            ",
				'rsf_threats'
			)
		);

		$this->set_message(__('Threats log has been emptied.', 'rsfirewall'), 'success');

		RSFirewall_Helper::redirect($back_url);
	}

    /**
     * Remove an IP from the blocklist
     */
    public function unblock() {
        $post_id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
        $back_url = wp_get_referer();

        $unblock = true;
        // Check if the id is not empty (0)
        if (empty($post_id)) {
            $this->set_message(__('The post id is not correct!', 'rsfirewall'));
            $unblock = false;
        }

        // Check if the id is available
        if (!get_post_status($post_id)) {
            $this->set_message(__('This post does not exist!', 'rsfirewall'));
            $unblock = false;
        }

        if ($unblock) {
            $ip_to_remove = get_post_meta($post_id, 'rsfirewall_ip', true);
            $args = array(
                'post_type'   => $this->prefix.'lists',
                'post_status' => 'publish',
                'meta_query'  => array(
                    array(
                        'key'     => 'rsfirewall_ip',
                        'value'   => $ip_to_remove,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => 'rsfirewall_type',
                        'value'   => '0',
                        'compare' => '=',
                    ),
                ),
            );

            $query = new WP_Query( $args );
            if ($posts = $query->posts) {
                foreach ($posts as $post) {
                    wp_delete_post($post->ID);
                }
            }
            $this->set_message(sprintf(__('You have unblocked the IP:  %s', 'rsfirewall'), $ip_to_remove), 'success');
        }
        // Redirect to the list
        RSFirewall_Helper::redirect($back_url);
    }

    protected function check_if_listed($ip) {
        static $checked_ips = array();
        self::$check_post = true;

        if (!isset($checked_ips[$ip])) {
            $args = array(
                'numberposts' => -1,
                'post_type'   => $this->prefix.'lists',
                'meta_query'  => array(
                    array(
                        'key'   => 'rsfirewall_ip',
                        'value' => $ip,
                        'compare' => '=',
                    )
                )
            );


            $query = new WP_Query( $args );
            $checked_ips[$ip] = $query->post_count;
        }
        else
        {
            $checked_ips[$ip] += 1;
        }

        return $checked_ips[$ip];
    }
}