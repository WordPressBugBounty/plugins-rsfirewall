<?php

/**
 * @package        RSFirewall!
 * @copyright  (c) 2018 RSJoomla!
 * @link           https://www.rsjoomla.com
 * @license        GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

/**
 * Class RSFirewall_Installer
 */
class RSFirewall_Installer {
    /**
     * Function triggered upon plugin activation
     * @since 1.0.0
     */
    public static function activate() {
        self::check_version();
        // Get the current version if it has previously activated
        $old_version = get_option('rsfirewall_version', null);

        $new_version = RSFirewall_Version::get_instance()->version;

        // Establish if this is a first activation (install) or an update
        if (is_null($old_version)) {
            self::install();
        } else if (version_compare($new_version, $old_version, '>')){
            self::update();
        }
		
		// Schedule the clear transient event
        if (! wp_next_scheduled ( 'rsfirewall_clear_transient' )) {
            wp_schedule_event( time(), 'hourly', 'rsfirewall_clear_transient' );
        }
    }

    public static function check_version($die = true) {
        $min = '5.4.0';
        if (version_compare(PHP_VERSION, $min, '<')) {
            deactivate_plugins( plugin_basename( RSFIREWALL_BASE ) );
            if ($die) {
                wp_die('<p>The <strong>RSFirewall!</strong> Plugin requires PHP version ' . $min . ' or greater.</p>','Plugin Activation Error',  array( 'response' => 200, 'back_link' => true ) );
            }

            return false;
        }

        return true;
    }

    /**
     * Function triggered upon plugin first activation (first install)
     * @since 1.0.0
     */
    protected static function install() {
        // Run the necessary database queries
        self::runSQL('standard-tables.sql');
        self::runSQL('signatures.sql');
        self::runSQL('hashes.sql');

        //Set the default options
        $configuration_xml_path = RSFIREWALL_BASE.'models/configuration.xml';
        if (is_file($configuration_xml_path)) {
            self::setDefaultOptions($configuration_xml_path);
        }

        // check the ip
        $ip = $_SERVER['REMOTE_ADDR'];
        $error_msg = sprintf(wp_kses_post(__('Your IP is currently detected as <strong>%s</strong>. If this does not match your current IP, go to RSFirewall Configuration &mdash; Active Scanner &mdash; Grab IP from Proxy Headers and select which PHP headers forward your current IP address. You can select them one by one until your correct IP address is reported. If in doubt, contact your hosting provider for more information. You can Google &quot;what is my ip address&quot; to find out what your real IP address is.'.$test, 'rsfirewall')), $ip);

        set_transient( 'global_admin_notice', $error_msg, 120);

        // Update the RSFirewall version in the database
        update_option( 'rsfirewall_version', RSFirewall_Version::get_instance()->version );
        // Set this option in case there is a pro version installed after
        update_option( 'rsfirewall_lite_version', 1 );

		self::removeSQL('signatures.sql');
    }

    /**
     * Function triggered upon plugin first activation (first install) to add the default configuration options
     * @since 1.0.0
     */
    protected static function setDefaultOptions($filename) {
        $xml = simplexml_load_file( $filename );

        $parse_sections       = array(
            'rsfirewall_system_check',
            'rsfirewall_active_scanner',
            'rsfirewall_core_scanner',
            'rsfirewall_logging'
        );

        $accepted_type_fields = array(
            'checkboxes',
            'switchery',
            'textbox',
            'select'
        );

        foreach ($xml as $section) {
            $section_name   = (string) $section->attributes()->name;
            // Skip the ones that are not of interest
            if (!in_array($section_name, $parse_sections)) {
                continue;
            }

            // Check if the option already exists in the database
            $old_options = array();
            $current_value = get_option($section_name, null);
            if (!is_null($current_value) && is_array($current_value)) {
                $old_options = $current_value;
            }


            $options = array();
            foreach ($section->field as $field) {
                $attr       = $field->attributes();
                $type       = (string) $attr->type;
                // check the type
                if (!in_array($type, $accepted_type_fields)) {
                    continue 1;
                }
                $field_name = (string) $attr->name;

                // if the field is already saved then skip it
                if (!empty($old_options) && isset($old_options[$field_name])) {
                    continue 1;
                }

                if ($type != 'select') {
                    $options[$field_name] = (string) $attr->default;
                } else {
                    // Determine if the select has multiple values or just one
                    $multiple       = $field->attributes()->multiple ? true : false;
                    $selected_values  = array();
                    if ($select_options = RSFirewall_Helper::select_options($field->option)) {
                        foreach ($select_options as $option) {
                            $value   = $option->value;
                            $checked = $option->checked ? true : false;

                            if ($checked) {
                                $selected_values[] = $value;
                            }
                        }
                    }

                    if (!$multiple && !empty($selected_values)) {
                        $selected_values = (string) $selected_values[0];
                    }

                    if (!empty($selected_values)) {
                        $options[$field_name] = $selected_values;
                    }
                }

            }

            if (!empty($options)) {
                update_option( $section_name, $options );
            }
        }
    }

    public static function upgrade() {
        // Get the current version if it has previously activated
        $old_version = get_option('rsfirewall_version', null);

        $new_version = RSFirewall_Version::get_instance()->version;

        if ($old_version !== null && version_compare($new_version, $old_version, '>')) {
            static::update();
        }
    }

    /**
     * Function triggered upon plugin next activations (acts like an update)
     * @since 1.0.0
     */
    protected static function update() {
        // The hashes must always be updated
        self::runSQL('hashes.sql');

        // Add new tables if exists
        self::runSQL('standard-tables.sql');

        // The signatures must always be updated
        self::runSQL('signatures.sql');

        // Update the RSFirewall version in the database
        update_option( 'rsfirewall_version', RSFirewall_Version::get_instance()->version );

		self::removeSQL('signatures.sql');
    }

    /**
     * Function triggered upon plugin deactivation
     * @since 1.0.0
     */
    public static function deactivate() {
        // remove schedule the clear transient event
        wp_clear_scheduled_hook( 'rsfirewall_clear_transient' );
    }


    /**
     * Function triggered upon plugin uninstall
     * @since 1.0.0
     */
    public static function uninstall() {
        // Delete tables
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsfirewall_hashes");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsfirewall_ignored");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsfirewall_offenders");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}rsfirewall_signatures");

        // Delete options
        delete_option('rsfirewall_version');
        delete_option('rsfirewall_backend_password');
        delete_option('rsfirewall_system_check');
        delete_option('rsfirewall_active_scanner');
        delete_option('rsfirewall_core_scanner');
        delete_option('rsfirewall_lockdown');
        delete_option('rsfirewall_logging');
        delete_option('rsfirewall_import');
        delete_option('rsfirewall_updates');
        delete_option('rsfirewall_grade');
        delete_option('rsfirewall_system_check_last_run');
        delete_option('rsfirewall_admin_users');
        delete_option('rsfirewall_log_emails_send_after');
        delete_option('rsfirewall_log_emails_count');

        // Delete all the posts related to rsfirewall
        self::delete_custom_posts('rsf_feeds');
        self::delete_custom_posts('rsf_exceptions');
        self::delete_custom_posts('rsf_lists');
        self::delete_custom_posts('rsf_threats');
    }

    /**
     * Function to delete posts by post type and all dependencies
     * @since 1.0.0
     */
    protected static function delete_custom_posts($post_type = null){
        if (is_null($post_type)) {
            return false;
        }

        global $wpdb;
        $result = $wpdb->query(
            $wpdb->prepare("
            DELETE posts,pt,pm
            FROM ".$wpdb->prefix."posts posts
            LEFT JOIN ".$wpdb->prefix."term_relationships pt ON pt.object_id = posts.ID
            LEFT JOIN ".$wpdb->prefix."postmeta pm ON pm.post_id = posts.ID
            WHERE posts.post_type = %s
            ",
                $post_type
            )
        );
        return $result!==false;
    }

	/**
	 * @param $file
	 * @since 1.1.9
	 */
	public static function removeSQL($file)
	{
		$sql_file = RSFIREWALL_BASE . 'installer/sql/'.$file;

		if (!file_exists($sql_file)) {
			return false;
		}

		return unlink($sql_file);
	}

    /**
     * Function to parse and run SQL files
     * @since 1.0.0
     */
    public static function runSQL($file) {
        global $wpdb;
        $wpdb->show_errors();
        $sql_file = RSFIREWALL_BASE . 'installer/sql/'.$file;

        if (!file_exists($sql_file)) {
            return false;
        }

        // Load the SQL file
        $buffer = file_get_contents($sql_file);
        if ($buffer === false) {
            return false;
        }

        // Get the queries
        $queries = self::sqlSplit($buffer);

        $replace = array(
            '$__wp_charset' => $wpdb->get_charset_collate(),
			'$__wp_collate' => $wpdb->collate,
            '#__'           => $wpdb->prefix
        );

        foreach ($queries as $query)
        {
            $query = trim($query);
            $query = str_replace(array_keys($replace), array_values($replace), $query);

            // Run the query
            $wpdb->query($query);
        }
    }

    /**
     * Helper function to get the queries from the SQL file
     * @since 1.0.0
     */
    protected static function sqlSplit($sql) {
        $comment = false;
        $isQuote = false;
        $skipNext = false;
        $query = '';
        $queries = array();


        $end = strlen($sql);
        $endComment = '';

        for ($i = 0; $i < $end; $i++){
            $current = $sql[$i];

            // Detect if there is a quote statement
            if ($current == "'" && !$isQuote) {
                $isQuote = true;
            } else if ($current == "'" && $isQuote){
                $isQuote = false;
            }

            // Check for comments and ignore them
            $current2 = $current.(isset($sql[($i+1)]) ? $sql[($i+1)] : '');
            $current3 = $current2.(isset($sql[($i+2)]) ? $sql[($i+2)] : '');


            if ((($current == '#' && $current3 !='#__') || $current2 == '--' || $current2 == '/*') && !$comment && !$isQuote) {
                if (($current == '#' && $current3 !='#__') || $current2 == '--') {
                    $endComment = "\n";
                }
                if ($current2 == '/*') {
                    $endComment = '*/';
                }

                $comment = true;
            }

            if ($comment && ($current == $endComment || $current2 == $endComment) && !$isQuote) {
                $comment = false;
                if (strlen($endComment) > 1) {
                    $skipNext = true;
                }
                continue;

            }

            if ($comment) {
                continue;
            }

            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            if ($current != ';') {
                $query = $query.$current;
            } else if (!$isQuote) {
                $queries[] = trim($query).';';
                $query = '';
            }
        }

        // In case there is only one Query in the sql file
        if (empty($queries) && !empty($query)) {
            $queries[] = $query.';';
        }


        return $queries;
    }
}