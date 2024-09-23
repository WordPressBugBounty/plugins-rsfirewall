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

class RSFirewall_Model_Dashboard extends RSFirewall_Model
{
    /**
     * Returns the license key.
     *
     * @return bool|mixed
     */
    public function get_license_code()
    {
        return RSFirewall_Config::get('code');
    }

    public function get_scanner_status()
    {
        return RSFirewall_Config::get('enable_active_scanner');
    }

    public function get_is_outdated_version()
    {
        $current_version = RSFirewall_Version::get_instance();

        $latest = $this->get_latest_version();
        if (isset($latest->ispro)) {
            return true;
        } else {
            return version_compare($current_version->version, $latest->new_version, '<');
        }
    }

    public function get_latest_version()
    {
        static $version;

        if ($version === null)
        {
            $model   = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Check', 'get_instance'));
            try {
                $version = $model->get_latest_firewall();
            } catch(Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return $version;
    }

    public function get_latest_quick_actions()
    {
        global $wpdb, $wp_version;
        $actions = array();

        $tables = array(
            'hashes' => $wpdb->prefix . 'rsfirewall_hashes'
        );

        $results = array();
        foreach ( $tables as $key => $table ) {
            $results[ $key ] = $wpdb->get_results("SELECT * FROM $table  WHERE `flag`!='' AND (`type`='protect' OR `type`='".$wp_version."')", OBJECT);
        }

        // memory variables
        $memory_limit = $this->get_memory_limit_in_bytes();
        $memory_usage = memory_get_usage();

        foreach ( $results as $key => $log_entry ) {
            $action = null;
            foreach ( $log_entry as $entry ) {
                // There is a problem with the checked file, so we need to build the action element
                $action = array(
                    'level' => 'critical',
                    'accept_changes'=> false
                );

                // Data attributes only for core files
                if ($entry->type != 'protect') {
                    $action['data-attributes'] = array(
                        'file' => $entry->file,
                        'action' => 'diff_view_differences',
                        'toggle' => 'rsmodal',
                        'target' => '#rsmodal',
                        'title' => __('View File Contents', 'rsfirewall'),
                        'viewcontent' => 1,
                        'usefooter' => 1,
                        'showclose' => 1,
                        'size' => 'large',
                        'hid' => 'dash'
                    );
                    // Core files can be reviewed using the fix button
                    $message = esc_html__(' Please review it manually by clicking this button.');
                } else {
                    // The manually added files (the one with protect type) do not have a fix button
                    $message = esc_html__(' Please review it manually (using a FTP client).');
                }

                $full_path = ($entry->type == 'protect') ? $entry->file : RSFIREWALL_SITE . '/' . $entry->file;
                if ( file_exists( $full_path ) ) {
                    $file_size = filesize( $full_path );
                    // let's hope the file can be read
                    if ( $memory_usage + $file_size < $memory_limit ) {
                        // does this file still have a wrong checksum ?
                        if (md5_file($full_path) != $entry->hash) {
                            $action['message'] = sprintf(wp_kses_post(__('<em>%s</em> file is modified.', 'rsfirewall')), $entry->file).$message;

                            // define the accept changes action in case the file exists and it is modified
                            $action['accept_changes'] = array(
                                'version'   => ($entry->type == $wp_version) ? $wp_version : 'protect',
                                'action'    => 'ignore_hash',
                                'file'      => $entry->file,
                                'type'      => 'wrong'
                            );

                        } else {
                            // skip this file as it has been checked and the checksum is the same
                            $wpdb->update( $wpdb->prefix . 'rsfirewall_hashes', array( 'flag' => '' ), array( 'id' => $entry->id ) );
                            continue;
                        }
                    }
                } else {
                    // skip because the file that is missing has already been flagged as missing
                    if ($entry->flag == 'M') {
                        continue;
                    }

                    // define the accept changes action in case the file is missing
                    $action['accept_changes'] = array(
                        'version'   => ($entry->type == $wp_version) ? $wp_version : 'protect',
                        'action'    => 'ignore_hash',
                        'file'      => $entry->file,
                        'type'      => 'missing'
                    );

                    $action['message'] = sprintf(wp_kses_post(__('<em>%s</em> file is missing.', 'rsfirewall')), $entry->file).$message;
                }

                $actions[] = $action;
            }
        };

        $args = array(
            'post_type'      => $this->prefix.'threats',
            'post_status'    => 'publish',
            'posts_per_page' => - 1,
			'meta_key' => 'rsfirewall_code',
            'meta_value' => 'IP_BLACKLISTED',
            'meta_compare' => '=',
        );

        $results = new WP_Query( $args );
        wp_reset_postdata();

        $codes = RSFirewall_i18n::get_locale('codes');
        foreach ( $results->posts as $result )
        {
            if ( get_post_meta( $result->ID, 'rsfirewall_code', true ) !== 'IP_BLACKLISTED' ) {
                continue;
            }

            $action = array(
                'level'   => 'warning',
                'data-attributes' => array(
                    'info'    => $result->ID,
                    'action'  => 'remove_from_list',
                ),
                'message' => wp_kses_post(sprintf( $codes['IP_BLACKLISTED'], get_post_meta( $result->ID, 'rsfirewall_debug_variables', true ) ))
            );

            if ( get_post_meta( $result->ID, 'rsfirewall_type', true ) === '1' ) {
                $action['message'] = esc_html__( 'The following IP is safelisted, click the fix button to remove it from the list', 'rsfirewall' );
            }

            $actions[] = $action;
        }

        return $actions;
    }

    /**
     * @return int|string
     */
    protected function get_memory_limit_in_bytes() {
        $memory_limit = ini_get( 'memory_limit' );
        switch ( substr( $memory_limit, - 1 ) ) {
            case 'K':
                $memory_limit = (int) $memory_limit * 1024;
                break;

            case 'M':
                $memory_limit = (int) $memory_limit * 1024 * 1024;
                break;

            case 'G':
                $memory_limit = (int) $memory_limit * 1024 * 1024 * 1024;
                break;
        }

        return $memory_limit;
    }

    /**
     * Get the latest 5 logs from the database
     *
     * @since     1.0.0
     * @return    array
     */
    public function get_latest_threats() {
        $threats = array();

        $args = array(
            'post_type'      => $this->prefix.'threats',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
        );

        $codes = RSFirewall_i18n::get_locale('codes');

        $results = new WP_Query( $args );
        wp_reset_postdata();

        if ( ! empty( $results->posts ) ) {
            foreach ($results->posts as $result) {
                $id = $result->ID;
                $threats[] = array(
                    'level'      => get_post_meta( $id, 'rsfirewall_level', true ),
                    'level_text' => sprintf( esc_html__( '%s alert', 'rsfirewall' ), get_post_meta( $id, 'rsfirewall_level', true ) ),
                    'code'       => wp_kses_post(sprintf( $codes[ get_post_meta( $id, 'rsfirewall_code', true ) ], get_post_meta( $id, 'rsfirewall_debug_variables', true ) ))
                );
            }
        }

        return $threats;
    }

    /**
     * Get the available feeds
     *
     * @since     1.0.0
     * @return    array
     */

    public function get_feeds() {
        $feeds = array();

        $args = array(
            'post_type'   => $this->prefix.'feeds',
            'post_status' => 'publish',
            'orderby' => 'date'
        );

        $query = new WP_Query( $args );

        $date_format = get_option('date_format'). ' '.get_option('time_format');
        if ($posts = $query->posts){
           // Include the feed library
           include_once(ABSPATH . WPINC . '/feed.php');

           foreach($posts as $post) {
               $feed_link  = get_post_meta($post->ID, 'rsfirewall_url', true);
               $feed_limit = (int) get_post_meta($post->ID, 'rsfirewall_limit', true);

               $rss = fetch_feed($feed_link);
               if (is_wp_error($rss)) {
                   continue;
               }

               $feed = new stdClass();
               $feed->name = __('Feed - ', 'rsfirewall').$rss->get_title();
               $feed->items = array();
               foreach ($rss->get_items() as $i => $item) {

                   if ($i == $feed_limit) {
                       break;
                   }
                   $feed_item = new stdClass();

                   $feed_item->link  = $item->get_permalink();
                   $feed_item->title = $item->get_title();
                   $feed_item->date  = $item->get_date($date_format);

                   $feed->items[] = $feed_item;
               }
               $feeds[] = $feed;
           }
        }

        return $feeds;
    }
}