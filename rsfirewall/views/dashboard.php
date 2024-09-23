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
$error_message = '';
$code                 = $this->model->get_license_code();
$scanner_enabled      = $this->model->get_scanner_status();
try {
	$latest_version = $this->model->get_latest_version();
	$is_outdated = $this->model->get_is_outdated_version();
} catch (Exception $e) {
	$error_message = $e->getMessage();
}
$latest_quick_actions = $this->model->get_latest_quick_actions();
$latest_threats       = $this->model->get_latest_threats();
$correct_code         = $code && strlen( $code ) == 20;
$feeds				  = $this->model->get_feeds();

$current_version      = RSFirewall_Version::get_instance();
?>
<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap rsfirewall">
	<!-- Title -->
	<div class="row margin-bottom-x1">
		<div class="col-md-12">
			<h3><?php echo esc_html__( 'Dashboard', 'rsfirewall' ); ?></h3>
			<hr/>
		</div>
	</div>
	<!-- /.Title -->
	<!-- Info Boxes -->
	<div class="row">
		<div class="col-md-3">
			<div class="rs-info-box mCustomScrollbar" data-mcs-theme="dark">
				<div class="rs-info-box-caption">
					<div class="system-check-badge">
						<span class="dashicons dashicons-<?php echo $scanner_enabled ? 'yes' : 'warning'; ?>"></span>
					</div>
					<div class="system-check-result">
						<?php if ( $scanner_enabled ) { ?>
							<p><?php echo wp_kses_post( __('You are <strong>Protected</strong>', 'rsfirewall' )); ?></p>
						<?php } else { ?>
							<p><?php echo esc_html__( 'Active Scanner is disabled. You will not be protected.', 'rsfirewall' ); ?></p>
						<?php } ?>
						<?php if ( empty($error_message) && !empty($is_outdated) ) { ?>
							<p><?php echo sprintf( esc_html__( 'A newer version (%s%s) is available. Please update to the latest version of the plugin.', 'rsfirewall' ), $latest_version->new_version, (isset($latest_version->ispro) ? esc_html__(' - Pro Version') : '') ); ?></p>
						<?php } else if(!empty($error_message)) { ?>
							<p><?php echo $error_message; ?></p>
						<?php } ?>
					</div>
					<?php if ( $scanner_enabled ) { ?>
						<div class="system-check-link">
							<a class="system-check-button" href="<?php menu_page_url( 'rsfirewall_check' ); ?>"><span
									class="dashicons dashicons-controls-play"></span> <?php echo esc_html__( 'Run System Check', 'rsfirewall' ) ?>
							</a>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="rs-info-box mCustomScrollbar" data-mcs-theme="dark">
				<div class="rs-info-box-caption">
					<h3><?php echo esc_html__( 'Latest system log messages', 'rsfirewall' ) ?></h3>
					<?php if ( $latest_threats ) { ?>
						<ul>
							<?php foreach ( $latest_threats as $threat ) { ?>
								<li><span
										class="alert-badge alert-badge-<?php echo esc_attr( $threat['level'] ); ?>">!</span>
									<strong><?php echo esc_html( $threat['level_text'] ); ?></strong>
									- <?php echo $threat['code']; ?></li>
							<?php } ?>
						</ul>
					<?php } else { ?>
						<p><?php echo esc_html__( 'No messages to show', 'rsfirewall' ); ?></p>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="rs-info-box mCustomScrollbar" data-mcs-theme="dark">
				<img src="<?php echo RSFIREWALL_URL . 'assets/images/rsfirewall.png' ?>"/>
				<div class="rs-info-box-caption bordered-top" style="padding-top:5px">
					<ul style="margin-top:5px">
						<li>
							<strong><?php echo esc_html__( 'Version', 'rsfirewall' ); ?></strong>: <?php echo $current_version->version; ?>
						</li>
						<li>
							<strong><?php echo esc_html__( 'Copyright', 'rsfirewall' ); ?></strong>: &copy; <?php echo gmdate('Y'); ?> <a
								href="https://www.rsjoomla.com" target="_blank">RSJoomla!</a>
						</li>
						<li><strong><?php echo esc_html__( 'License', 'rsfirewall' ); ?></strong>: <a
								href="http://www.gnu.org/licenses/gpl.html" target="_blank">GNU/GPL</a>
						</li>
						<?php if ($code && $correct_code) { ?>
							<li>
								<strong><?php echo esc_html__( 'License Code', 'rsfirewall' ) ?></strong>: <?php echo esc_html( $code ); ?>
								<span class="dashicons dashicons-yes"></span>
							</li>
							<li style="text-align: center; border-top:1px solid #ccc; margin-top:5px; padding-top:10px;">

								<span class="dashicons dashicons-warning"></span> <?php echo esc_html__( 'Please Update the plugin to the Pro Version', 'rsfirewall' ) ?>

							</li>
						<?php } else { ?>
							<li style="text-align: center; border-top:1px solid #ccc; margin-top:5px; padding-top:5px">
								<div><?php echo esc_html__('Need more features?', 'rsfirewall'); ?></div>
								<a class="rsfirewall-btn small" href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html" target="_blank">
									<?php echo esc_html__( 'Purchase the full version of RSFirewall!', 'rsfirewall' ) ?>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<!-- /.Info Boxes -->
	<div class="row">
	<?php if ( $latest_quick_actions ) { ?>
		<div class="col-md-12 margin-top-x2">
			<div class="rs-box quick-actions-box">
				<h3><?php echo esc_html__( 'Quick Actions', 'rsfirewall' ) ?></h3>
				<?php foreach ( $latest_quick_actions as $i => $action ) { ?>
					<div class="row quick-action margin-top-x1 bordered-top" id="dash<?php echo $i; ?>">
						<?php
						$col_message_width = 11;
						$col_buttons_width = 3;
						if (isset($action['data-attributes']) && isset($action['accept_changes']) && $action['accept_changes']) {
							$col_message_width = 8;
							$col_buttons_width = 4;
						} else if (isset($action['data-attributes']) || (isset($action['accept_changes']) && $action['accept_changes'])) {
							$col_message_width = 9;
						}
						?>
						<div class="col-md-<?php echo $col_message_width; ?>">
							<span class="alert-badge alert-badge-<?php echo $action['level'] ?>">!</span> &nbsp;
							<?php echo $action['message'] ?>
						</div>
						<?php if (isset($action['data-attributes']) || $action['accept_changes']) { ?>
							<div class="col-md-<?php echo $col_buttons_width; ?> text-right">
								<?php if (isset($action['data-attributes'])) { ?>
									<?php
									// Building the data attributes
									$data_attr = array();
									foreach ($action['data-attributes'] as $attr => $val) {
										if ($attr == 'hid') {
											$val .= $i; //add the specific hid index
										}
										$data_attr[] = 'data-'.$attr.'="'.$val.'"';
									}
									$data_attr = implode(' ', $data_attr);
									?>
									<button class="rsfirewall-fix-action" <?php echo $data_attr; ?>>
										<span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__( 'Fix', 'rsfirewall' ) ?>
									</button>
								<?php } ?>
								<?php if (isset($action['accept_changes']) && $action['accept_changes']) { ?>
									<?php
									// Building the data attributes
									$data_attr = array();
									foreach ($action['accept_changes'] as $attr => $val) {
										if ($attr == 'hid') {
											$val .= $i; //add the specific hid index
										}
										$data_attr[] = 'data-'.$attr.'="'.$val.'"';
									}
									$data_attr = implode(' ', $data_attr);
									?>
									<button class="rsfirewall-fix-action" <?php echo $data_attr; ?>>
										<span class="dashicons dashicons-yes"></span> <?php echo esc_html__( 'Accept Changes', 'rsfirewall' ) ?>
									</button>
								<?php } ?>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
	</div>
	<!-- Stat Graph -->
	<div class="row margin-top-x2">
		<div class="col-md-12">
			<div class="rs-graphs">
				<h3><?php echo esc_html__( 'Attacks blocked graph', 'rsfirewall' ) ?></h3>
				<div class="rsfirewall-graph"></div>
			</div>
		</div>
	</div>
	<!-- /.Stat Graph -->
	<!-- Actions -->
	<?php if (!empty($feeds)) { ?>
	<div class="row margin-top-x2">
		<div class="col-md-12">
			<div class="rs-box">
				<h3 class="bordered-bottom"><?php echo esc_html__('RSS Feeds', 'rsfirewall')?></h3>
				<?php foreach($feeds as $feed) { ?>
					<h4><?php echo esc_html($feed->name); ?></h4>
					<ul>
						<?php foreach ($feed->items as $item) { ?>
							<li><?php echo $item->date;?> - <a href="<?php echo esc_attr($item->link); ?>" target="_blank"><?php echo esc_html($item->title); ?></a></li>
						<?php } ?>
					</ul>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php } ?>
	<div class="row margin-top-x2">
		<div class="col-md-12">
			<div class="rs-box">
				<h3><?php echo esc_html__( 'Need help?', 'rsfirewall' ); ?></h3>
                <p><?php echo wp_kses_post( __('Please take a look at our extensive <a href="https://www.rsjoomla.com/support/documentation/rsfirewall-wordpress.html" target="_blank">documentation</a>. Or you can check out our <a href="https://www.rsjoomla.com/forum/45-rsfirewall.html" target="_blank">forum section</a>.', 'rsfirewall' )); ?></p>
                <p><?php echo esc_html__( 'If you need priority support please purchase the full version of RSFirewall!', 'rsfirewall' ); ?></p>
                <p>
                    <a href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html"
                       target="_blank"
                       class="button button-primary"><?php echo esc_html__( 'Purchase now', 'rsfirewall' ); ?></a>
                </p>
			</div>
		</div>
	</div>
	<!-- /.Actions -->
</div><!-- /.wrap -->

<?php RSFirewall_Helper::load_rsmodal('output', $this->version);?>