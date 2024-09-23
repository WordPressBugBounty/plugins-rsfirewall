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

$content = $this->model->content;
$DS = $this->model->DS;
$elements = $this->model->get_elements();
$previous = $this->model->previous;
$limit_to = $this->model->limit_to;

?>
<div id="rsfirewall-explorer-header">
    <strong><?php echo __('Current location:', 'rsfirewall'); ?></strong>
    <?php foreach ($elements as $element) { ?>
        <a href="" onclick="RSFirewall.file_manager.load_path(event, this)" data-path="<?php echo RSFirewall_Helper::escape($element->fullpath); ?>"><?php echo RSFirewall_Helper::escape($element->name); ?></a> <?php echo $DS; ?>
    <?php } ?>
</div>
<table class="widefat">
    <thead>
        <tr>
            <th><?php echo __('Select', 'rsfirewall'); ?></th>
            <th><?php echo __('Folders / Files', 'rsfirewall'); ?></th>
            <th><?php echo __('Permissions', 'rsfirewall'); ?></th>
            <th><?php echo __('Size', 'rsfirewall'); ?></th>
        </tr>
    </thead>
    <?php if ($previous) { ?>
    <tr>
        <td>
            <input type="checkbox" onclick="RSFirewall.file_manager.checkAll()" />
        </td>
        <td>
            <span class="dashicons dashicons-category"></span>
            <a href="" onclick="RSFirewall.file_manager.load_path(event, this)" data-path="<?php echo RSFirewall_Helper::escape($previous); ?>">..</a>
        </td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <?php } ?>
    <?php
    $i = 0;
    foreach ($content as $file => $data) {
        $type = $data['is_file'] ? 'files' : 'folders';
    ?>
        <?php $fullpath = $this->model->path.$DS.$file; ?>
        <tr>
            <td>
                <?php if (empty($limit_to) || in_array($type, $limit_to)) { ?>
                    <input type="checkbox" name="cid[]" value="<?php echo $fullpath; ?>" /></td>
                <?php } else { ?>
                    &nbsp;
                <?php } ?>
            <td>
                <?php if ($data['is_file']) { ?>
                    <span class="dashicons dashicons-media-default"></span>
                    <label for="file<?php echo $i; ?>"><?php echo RSFirewall_Helper::escape($file); ?></label>
                <?php } else { ?>
                    <span class="dashicons dashicons-category"></span>
                    <a href="" onclick="RSFirewall.file_manager.load_path(event, this)" data-path="<?php echo RSFirewall_Helper::escape($fullpath); ?>"><?php echo RSFirewall_Helper::escape($file); ?></a>
                <?php } ?>
            </td>
            <td><?php echo $data['octal']?> (<?php echo $data['full']?>)</td>
            <td><?php echo (isset($data['filesize']) ? $data['filesize'] : '&nbsp;')?></td>
        </tr>
    <?php
        if ($data['is_file']) {
            $i++;
        }
    } ?>
</table>
