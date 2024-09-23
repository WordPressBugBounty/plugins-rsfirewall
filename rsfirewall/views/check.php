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

$system_check_steps 		= $this->model->get_steps('system_check');
$server_check_steps 		= $this->model->get_steps('server_check');
$all_steps					= $this->model->get_steps();

// is Xdebug loaded?
$hasXdebug = extension_loaded('xdebug');

$last_run = get_option('rsfirewall_system_check_last_run');
$suffix = 'ago';
if ($last_run) {
	$last_run = human_time_diff($last_run, current_time('timestamp'));
} else {
	$last_run = __('Never', 'rsfirewall');
	$suffix = '';
}
?>
	<!-- Create a header in the default WordPress 'wrap' container -->
	<div class="wrap rsfirewall">
		<!-- Title -->
		<div class="row margin-bottom-x1">
			<div class="col-md-12">
				<h3><?php echo esc_html__( 'System Check', 'rsfirewall' ); ?></h3>
				<hr/>
			</div>
		</div>
		<div class="row margin-bottom-x1">
			<div class="col-md-12">
				<div class="notice notice-info"><p><?php echo sprintf(esc_html__('Last run: %s %s', 'rsfirewall'), $last_run, $suffix);?></p></div>
				<hr/>
			</div>
		</div>
		<!-- /.Title -->
		<!-- Info Boxes -->
		<div class="row margin-bottom-x1">
			<div class="col-md-12">
				<div class="row">
					<div class="col-md-12">
						<div class="loading-holder">
							<span class="dashicons dashicons-update"></span>
						</div>
						<div class="scanning-message">
							<h3></h3>
						</div>
						<div class="scan-results-holder rs-box">
							<div class="knob-holder" style="float:left">
								<input autocomplete="off" disabled type="text" value="0" class="rsfirewall-knob-score" />
							</div>
							<p>
								<?php echo esc_html__('RSFirewall! has computed a grade based on your website\'s security level. Please keep in mind that
						there are areas that RSFirewall! cannot cover, so don\'t rely on this grade as a definite
						indicator of your website\'s security', 'rsfirewall'); ?>
							</p>
						</div>
						<?php if ($hasXdebug) { ?>
							<div class="notice notice-error" id="com-rsfirewall-xdebug-warning">
								<p><strong><?php echo  esc_html__('This server has the PHP \'Xdebug\' module enabled.'); ?></strong></p>
								<p><?php echo esc_html__('This PHP module prevents the correct functioning of the System Check. Please disable it (ask your hosting provider for details on how to do this) and try again. If you cannot disable it, please increase the xdebug.max_nesting_level to a higher value if the System Check fails, as this process eats up a lot of memory and Xdebug interferes with it.'); ?></p>
								<p><button class="rsfirewall-btn danger" type="button" onclick="document.getElementById('start-check').removeAttribute('disabled'); document.getElementById('com-rsfirewall-xdebug-warning').style.display = 'none';"><?php echo esc_html__('I understand the risks and want to continue'); ?></button></p>
							</div>
							<hr/>
						<?php } ?>
						<button class="rsfirewall-fix-action" id="start-check"<?php if ($hasXdebug) { ?> disabled="disabled"<?php } ?>><?php echo esc_html__('Perform the System Check', 'rsfirewall'); ?></button>
					</div>
				</div>
			</div>
		</div>
		<div class="row margin-bottom-x1" id="system-check-container">
			<div class="col-md-12">
				<div class="rs-box-transparent">
					<span class="system-check-progress" id="system-check-progress">&nbsp;</span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="rs-box margin-bottom-x1">
					<h3><span class="dashicons dashicons-wordpress"></span> <?php echo esc_html__( 'WordPress Configuration', 'rsfirewall' ) ?></h3>
					<div class="bordered-sub-box rsfirewall-hidden">
						<?php foreach ( $system_check_steps as $step ) { ?>
							<div class="system-check-step rsfirewall-hidden" data-step="<?php echo esc_attr($step['name']); ?>">
								<a class="system-check-dropdown-btn" href="#" data-target="#<?php echo esc_attr($step['name']); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
								<div class="row">
									<div class="col-md-5">
										<p><?php echo $step['description']; ?></p>
									</div>
									<div class="col-md-7">
										<p class="short-message-holder"></p>
									</div>
								</div>
								<div class="row rsfirewall-hidden" id="<?php echo esc_attr($step['name']); ?>">
									<div class="col-md-12">
										<p></p>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
				<div class="rs-box margin-bottom-x1">
					<h3><span class="dashicons dashicons-cloud"></span> <?php echo esc_html__( 'Server Configuration', 'rsfirewall' ) ?></h3>
					<div class="bordered-sub-box rsfirewall-hidden">
						<?php foreach ( $server_check_steps as $step ) { ?>
							<div class="system-check-step rsfirewall-hidden" data-step="<?php echo esc_attr($step['name']); ?>">
								<a class="system-check-dropdown-btn" href="#" data-target="#<?php echo esc_attr($step['name']); ?>"><span class="dashicons dashicons-arrow-down-alt2"></span></a>
								<div class="row">
									<div class="col-md-5">
										<p><?php echo $step['description']; ?></p>
									</div>
									<div class="col-md-7">
										<p class="short-message-holder"></p>
									</div>
								</div>
								<div class="row rsfirewall-hidden" id="<?php echo esc_attr($step['name']); ?>">
									<div class="col-md-12">
										<p></p>
									</div>
								</div>
							</div>
						<?php } ?>
						<div class="rsfirewall-hidden margin-top-x1">
							<button class="rsfirewall-fix-action" onclick="RSFirewall.System.Fix.fixPhpConfiguration(this)"
									id="fix-php-configuration"><?php echo esc_html__( 'Attempt to fix PHP Configuration', 'rsfirewall' ) ?></button>
							<div class="alert alert-info">
								<?php echo esc_html__( 'RSFirewall! will attempt to fix your PHP settings if the configuration is not secure by creating a local php.ini file in the root of your hosting account. If the hosting provider allows this, the php.ini file will be read and the new settings will take effect. If it does not work, then it means that the hosting provider does not allow the reading of local php.ini files. Please contact your hosting provider and/or system administrator in order to get this enabled.', 'rsfirewall' ) ?>
							</div>
						</div>
					</div>
				</div>
				<div class="rs-box margin-bottom-x1">
					<h3><span class="dashicons dashicons-shield"></span> <?php echo esc_html__( 'File Integrity', 'rsfirewall' ) ?></h3>
					<div class="alert alert-info">
						<h4 style="margin-top:5px; margin-bottom:5px;"><?php echo __('This feature is not available in the free version of RSFirewall!', 'rsfirewall'); ?></h4>
						<p><?php echo esc_attr__('If you wish to use this feature please consider purchasing the full version of RSFirewall!', 'rsfirewall'); ?></p>
						<p><a href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html" class="button-primary"><?php echo __('Purchase the full version of RSFirewall!', 'rsfirewall'); ?></a></p>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			RSFirewall.requestTimeOut.Seconds = '<?php echo (float) RSFirewall_Config::get( 'pause_between_requests' ); ?>';

			RSFirewall.System.Check.steps = <?php echo json_encode(array_keys($all_steps)); ?>;

			RSFirewall.Grade.importance = {

				high: [<?php
					foreach ( $all_steps as $step ) {
						if ( $step['importance'] == 'high' ) {
							echo '"' . $step['name'] . '",';
						}
					} ?> ],
				medium: [<?php
					foreach ( $all_steps as $step ) {
						if ( $step['importance'] == 'medium' ) {
							echo '"' . $step['name'] . '",';
						}
					} ?> ],
				low: [<?php
					foreach ( $all_steps as $step ) {
						if ( $step['importance'] == 'low' ) {
							echo '"' . $step['name'] . '",';
						}
					} ?> ]

			}
		</script>
		<!-- /.Info Boxes -->
	</div><!-- /.wrap -->

<?php RSFirewall_Helper::load_rsmodal('output', $this->version);?>