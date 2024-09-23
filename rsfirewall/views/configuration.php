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

$current_section = $this->model->form->get_current_section();
$is_pro = ((isset($this->model->is_pro) && $this->model->is_pro) ? true : false);

$hide_save_on_pages = array();
if (!$is_pro) {
	$hide_save_on_pages = array('rsfirewall_country_blocking', 'rsfirewall_two_factor_auth');
}
?>

<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap rsfirewall">
	<h2><?php _e( 'RSFirewall! Firewall Configuration', 'rsfirewall' ); ?> - <a href="<?php echo wp_nonce_url(admin_url( 'admin.php?page=rsfirewall_configuration'), 'rsfirewall', 'rsf-actions'); ?>&handler=configuration&task=export_configuration" class="button-primary" onclick="RSFirewall.export_configuration()"><span class="dashicons dashicons-download" style="line-height:1.5"></span> <?php echo __('Export current configuration', 'rsfirewall'); ?></a></h2>
	<?php settings_errors(); ?>
	<h2 class="nav-tab-wrapper">
		<?php
		foreach ($this->model->form->sections as $section)
		{
			?>
			<a href="<?php menu_page_url('rsfirewall_configuration'); ?>&amp;section=<?php echo urlencode($section['name']); ?>" class="nav-tab<?php echo $current_section == $section['name'] ? ' nav-tab-active' : ((!$is_pro && in_array($section['name'], $hide_save_on_pages)) ? ' rs-only-pro' : ''); ?>"><?php echo esc_html($section['label']); ?></a>
			<?php
		}
		?>
	</h2>
	<?php
	if ($is_pro && $current_section == 'rsfirewall_country_blocking' && !$this->model->check_php_version()->php_version_compat) {
		echo $this->model->check_php_version()->message;
	} else {
	?>
		<form method="post" action="options.php" enctype="multipart/form-data">
			<?php
			settings_fields( $current_section );
			do_settings_sections( $current_section );
			
			if ($is_pro && $current_section == 'rsfirewall_country_blocking') {
				echo wp_kses_post(__('This product includes GeoLite2 data created by MaxMind, available from <a href="http://www.maxmind.com" target="_blank">http://www.maxmind.com</a>.','rsfirewall'));
			}

			if ($is_pro || (!$is_pro && !in_array($current_section, $hide_save_on_pages))) {
				submit_button();
			}
			?>
		</form>
	<?php } ?>

</div><!-- /.wrap -->

<?php RSFirewall_Helper::load_rsmodal('output', $this->version);?>
