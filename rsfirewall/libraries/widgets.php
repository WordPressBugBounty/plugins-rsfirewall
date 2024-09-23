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

class RSFirewall_Widgets
{
    /**
     * Create dashboard widgets in the admin area
     */
    public static function setup()
    {
		if (!current_user_can('manage_options'))
		{
			return;
		}
		
        wp_add_dashboard_widget(
            'rsfirewall_widget_dashboard',
            esc_html__('RSFirewall! Control Panel', 'rsfirewall'),
            array( 'RSFirewall_Widgets', 'display_system_logs' )
        );
    }

    /**
     * System Logs widget contents
     */
    public static function display_system_logs()
    {
        // Get the latest threats
        $latest_threats = RSFirewall_Model_Dashboard::get_instance()->get_latest_threats();

        // Get the last system scan grade
        $grade = get_option('rsfirewall_grade', false);

        if (!$grade) {
            $color = '#000';
        }
        elseif ($grade <= 75) {
            $color = '#ED7A53';
        } elseif ($grade <= 90) {
            $color = '#88BBC8';
        } elseif ($grade <= 100) {
            $color = '#9FC569';
        }
        ?>
        <div class="widget-data">
            <span class="rsf-widget-icon dashicons dashicons-shield"></span>
            <strong class="rsf-eq-width"><?php echo esc_html__('Grade', 'rsfirewall'); ?></strong> -
            <span class="com-rsfirewall-icon-16-spacer"></span><span class="mod-rsfirewall-float-left" style="color: <?php echo $color; ?>;"><?php echo $grade > 0 ? sprintf(wp_kses_post(__('Your website grade is <strong>%s</strong>', 'rsfirewall')), $grade) :esc_html__('Not computed yet.', 'rsfirewall'); ?></span>
        </div>

        <div class="widget-data">
            <span class="rsf-widget-icon icon-16-rsfirewall"></span>
            <strong class="rsf-eq-width" >RSFirewall!</strong> -
		<span id="widget-rsfirewall-firewall-version">
			<span class="dashicons dashicons-update rsfirewall-animated"></span>
		</span>
        </div>

        <div class="widget-data">
            <span class="rsf-widget-icon dashicons dashicons-wordpress"></span>
            <strong class="rsf-eq-width">WordPress</strong> -
		<span id="widget-rsfirewall-wp-version">
			<span class="dashicons dashicons-update rsfirewall-animated"></span>
		</span>
        </div>

        <hr/>
        <h3><?php echo esc_html__('Latest system log messages', 'rsfirewall');?></h3>
        <hr/>
        <div class="widget-data">
            <?php if ( $latest_threats ) { ?>
                <ul>
                    <?php foreach ($latest_threats as $threat) { ?>
                        <li><span class="alert-badge alert-badge-<?php echo esc_attr($threat['level']); ?>">!</span> <strong><?php echo esc_html($threat['level_text']); ?></strong> - <?php echo wp_kses_post($threat['code']); ?></li>
                    <?php } ?>
                </ul>
                <p><a class="button button-primary" href="edit.php?post_type=rsf_threats"><?php echo esc_html__('View more', 'rsfirewall'); ?></a></p>
            <?php } else { ?>
                <p><?php echo esc_html__( 'No messages to show', 'rsfirewall' ); ?></p>
            <?php } ?>
        </div>
        <?php
    }
}