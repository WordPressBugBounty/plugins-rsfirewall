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

try {
$original_file = $this->model->get_remote_file();
$local_file    = $this->model->get_local_file();
$filename      = $this->model->get_file();
$hash          = $this->model->get_hash();

?>

<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap rsfirewall">
	<!-- Title -->
	<div class="row margin-bottom-x1">
		<div class="col-md-12">
			<h3><?php echo esc_html__( 'File Differences', 'rsfirewall' ); ?></h3>
			<hr/>
		</div>
	</div>
	<div class="rsfirewall-replace-original text-center">
		<button type="button" id="replace-original" class="rsfirewall-fix-action"
		        onclick="RSFirewall.diffs.download('<?php echo esc_html( $filename ); ?>', '<?php echo $hash; ?>', this)"><?php echo esc_html__( 'Download Original', 'rsfirewall' ) ?></button>
	</div>
	<!-- /.Title -->
	<!-- Info Boxes -->
	<?php
	echo RSFirewall_Helper_Diff::toTable( RSFirewall_Helper_Diff::compare( $original_file, $local_file ), '', '', array(
		sprintf( esc_html__( 'Original (Remote file): %s', 'rsfirewall' ), $this->model->get_remote_filename() ),
		sprintf( esc_html__( 'Your version (Local file): %s', 'rsfirewall' ), realpath( $this->model->get_local_filename() ) )
	) );
	?>
	<!-- /.Info Boxes -->
</div><!-- /.wrap -->
<?php } catch(Exception $e) { ?>
	<div class="notice notice-error"><p><?php echo $e->getMessage(); ?></p></div>
<?php }
