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
$contents = $this->model->get_contents();

?>
<!-- Create a header in the default WordPress 'wrap' container -->
    <!-- Title -->
    <div class="row margin-bottom-x1">
        <div class="col-md-12">
            <h3><?php echo esc_html__( 'File Contents', 'rsfirewall' ); ?></h3>
            <h4><?php echo $contents->status['reason'] ?></h4>
            <hr/>
        </div>
    </div>
    <!-- /.Title -->
    <!-- Info Box -->
    <pre>
	<?php
        if ( $contents->status ) {
            $contents = str_replace( $contents->status['match'], '<strong class="rsfirewall-level-high">' . $contents->status['match'] . '</strong>', esc_html( $contents->file_contents ) );
        } else {
            $contents = esc_html( $contents->file_contents );
        }
        echo $contents;
        ?>
    </pre>
    <!-- /.Info Box -->
<?php  } catch( Exception $e) { ?>
    <div class="notice notice-error"><p><?php echo $e->getMessage(); ?></p></div>
<?php }
