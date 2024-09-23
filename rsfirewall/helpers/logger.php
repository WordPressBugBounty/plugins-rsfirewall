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
class RSFirewall_Helper_Logger {
    protected $root;
    protected $args;
    protected $emails;
    protected $mailfrom;
    protected $fromname;
    protected $bound = false;
    protected $date;
    protected $prefix;

    public function __construct() {
        global $pagenow;

        $current_user   = wp_get_current_user();
        $referer        = isset( $_SERVER['HTTP_REFERER'] ) ? (string) $_SERVER['HTTP_REFERER'] : '';

        $this->prefix   = RSFIREWALL_POSTS_PREFIX;

        $this->args = array(
            'ip'                => RSFirewall_Helper::get_ip(),
            'user_id'           => $current_user->ID,
            'username'          => isset($current_user->user_login) ? $current_user->user_login : '',
            'page'              => $pagenow,
            'referer'           => $referer,
            'debug_variables'   => '',
        );

        $this->date     = current_time('mysql');
        $this->root     = home_url();
        $this->mailfrom = get_option('admin_email');
        $this->fromname = get_option('blogname');

        $this->emails	= $this->get_config('email_addresses', '');
        $this->emails	= trim($this->emails);

        if (empty($this->emails)) {
            $this->emails = array();
        } else {
            $this->emails = RSFirewall_Helper::explode($this->emails);
        }
    }

    public function get_config( $key = NULL, $default = false ) {
        if ( $key ) {
            return RSFirewall_Config::get( $key, $default );
        }

        return $default;
    }

    public static function get_instance() {
        // always create a new instance to allow subsequent calls to grab the correct details
        $inst = new RSFirewall_Helper_Logger();
        return $inst;
    }

    public function add($args) {
        $this->args = array_merge($this->args, $args);

        $this->bound = true;

        return $this;
    }

    public function save() {
        if ($this->bound) {
            $this->bound = false;

            $threat = array(
                'post_title' => $this->args['code'],
                'post_status' => 'publish',
                'post_author' => $this->args['user_id'],
                'post_type' => $this->prefix.'threats',
                'meta_input' => array(
                    'rsfirewall_code' => $this->args['code'],
                    'rsfirewall_level' => $this->args['level'],
                    'rsfirewall_ip' => $this->args['ip'],
                    'rsfirewall_page' => $this->args['page'],
                    'rsfirewall_referer' => $this->args['referer'],
                    'rsfirewall_debug_variables' => $this->args['debug_variables'],
                    'rsfirewall_username' => $this->args['username'],
                    'rsfirewall_user_id' => $this->args['user_id']
                ),
            );

            wp_insert_post($threat);

            // if this level is higher or equal to the configured minimum level
            $alert_levels = $this->get_config('include_alert_levels');
            if (!empty($alert_levels) && in_array($this->args['level'], $alert_levels)) {
                // send the email alert
                $this->send_alert();
            }
        }
    }

    protected function send_alert() {
        $levels = RSFirewall_i18n::get_locale('levels');
        $codes = RSFirewall_i18n::get_locale('codes');

        $subject =  sprintf(esc_html__('[%1$s] [IP: %3$s] RSFirewall! for %2$s', 'rsfirewall'), $levels[$this->args['level']], RSFirewall_Helper::escape($this->root), RSFirewall_Helper::escape($this->args['ip']));
        $description = $codes[$this->args['code']];
        if (strpos($description, '%s') !== false) {
            $description = sprintf($description, RSFirewall_Helper::escape($this->args['debug_variables']));
            $description = nl2br($description);
        }

        $body =  '<html>'."\n"
            .'<body>'."\n"
            .'<p><strong>'.esc_html__('Website', 'rsfirewall').':</strong> <a href="'.RSFirewall_Helper::escape($this->root).'">'.RSFirewall_Helper::escape($this->root).'</a></p>'."\n"
            .'<p><strong>'.esc_html__('Page', 'rsfirewall').':</strong> '.RSFirewall_Helper::escape($this->args['page']).'</p>'."\n"
            .'<p><strong>'.esc_html__('Referer', 'rsfirewall').':</strong> '.($this->args['referer'] ? RSFirewall_Helper::escape($this->args['referer']) : '<em>'.esc_html__('No referer', 'rsfirewall').'</em>').'</p>'."\n"
            .'<p><strong>'.esc_html__('Description', 'rsfirewall').':</strong> '.$description.'</p>'."\n"
            .'<p><strong>'.esc_html__('Debug information', 'rsfirewall').':</strong> '.nl2br(RSFirewall_Helper::escape($this->args['debug_variables'])).'</p>'."\n"
            .'<p><strong>'.esc_html__('Alert level', 'rsfirewall').':</strong> '.$levels[$this->args['level']].'</p>'."\n"
            .'<p><strong>'.esc_html__('Date of event', 'rsfirewall').':</strong> '.$this->date.'</p>'."\n"
            .'<p><strong>'.esc_html__('IP address', 'rsfirewall').':</strong> '.RSFirewall_Helper::escape($this->args['ip']).'</p>'."\n"
            .'<p><strong>'.esc_html__('User ID', 'rsfirewall').':</strong> '.RSFirewall_Helper::escape($this->args['user_id']).'</p>'."\n"
            .'<p><strong>'.esc_html__('Username', 'rsfirewall').':</strong> '.RSFirewall_Helper::escape($this->args['username']).'</p>'."\n"
            .'<small>'.esc_html__('This email was sent because the RSFirewall! plugin is monitoring your WordPress website. Notifications can be changed in the Firewall Configuration area, under the Logging Utility tab.', 'rsfirewall').'</small>'."\n"
            .'</body>'."\n"
            .'</html>';

        // sent so far
        $sent = (int) get_option('rsfirewall_log_emails_count', 0);
        // limit per hour
        $limit = $this->get_config('emails_per_hour');
        // after the hour we're allowed to send
        $after = get_option('rsfirewall_log_emails_send_after');
        // now
        $now = gmmktime(gmdate('H'));

        // are we allowed to send?
        if ($now > $after) {
            // do we have emails set?
            if ($this->emails) {
                // loop through emails and attempt sending
                foreach ($this->emails as $email) {
                    $email = trim($email);
                    if (is_email($email) && $sent < $limit) {
                        wp_mail( $email, $subject, $body, array('Content-Type: text/html; charset=UTF-8') );
                        // increment number of sent emails
                        $sent++;
                    }
                }

                // reached the limit?
                if ($sent >= $limit) {
                    // allow to send in the next hour
                    $next_after = gmmktime(gmdate('H')+1, 0, 0, gmdate('n'), gmdate('j'), gmdate('Y'));
                    update_option('rsfirewall_log_emails_send_after', $next_after);
                    update_option('rsfirewall_log_emails_count', 0);
                } else {
                    update_option('rsfirewall_log_emails_count', $sent);
                }
            }
        }
    }
}