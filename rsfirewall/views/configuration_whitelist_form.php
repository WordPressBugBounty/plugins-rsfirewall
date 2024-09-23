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

$whitelisted_files = isset($this->whitelisted_files) ? $this->whitelisted_files : array();
$action_message    = $this->action_message;
$postfields        = $this->postfields;

?>
<form id="add_to_whitelist">
    <table>
        <tr valign="top">
            <td scope="row">
                <label for="tablecell"><?php esc_attr_e('Filename:', 'rsfirewall'); ?></label>
            </td>
            <td><input type="text" value="<?php echo (isset($postfields['file']) ? $postfields['file'] : ''); ?>" name="file" id="rsf-whitelist-file" class="regular-text" /></td>
            <td>
                <select name="folder" id="rsf-whitelist-folder">
                    <?php foreach ($this->harden_folders as $folder => $available) { ?>
                        <?php
                            if (!$available) {
                                continue;
                            }
                        ?>
                        <option value="<?php echo strtolower($folder); ?>"<?php echo ((isset($postfields['folder']) && $postfields['folder'] == strtolower($folder)) ? ' selected="'.$postfields['folder'].'"' : ''); ?>><?php echo ucfirst($folder); ?></option>
                    <?php } ?>
                </select>
            </td>
            <td><input class="button-primary" type="submit" name="add_file" value="<?php esc_attr_e( 'Add', 'rsfirewall' ); ?>" /></td>
        </tr>
    </table>
    <input type="hidden" name="security" id="rsf-whitelist-security" value="<?php echo wp_create_nonce( 'add_to_whitelist' ); ?>"/>
</form>
<br/>

<?php if ($action_message) { ?>
    <div class="alert alert-<?php echo $action_message->type; ?>" style="margin:0px">
        <p><?php echo $action_message->message; ?></p>
    </div>
    <br/>
<?php } ?>

<?php  if ($whitelisted_files) { ?>
    <table class="widefat" id="rsf-whitelisted-files-list">
        <thead>
        <tr>
            <th><input type="checkbox" id="rsfirewall-whitelist-check-all"/></th>
            <th><?php esc_attr_e( 'Filename', 'rsfirewall' ); ?></th>
            <th><?php esc_attr_e( 'Directory', 'rsfirewall' ); ?></th>
            <th><?php esc_attr_e( 'Pattern', 'rsfirewall' ); ?></th>
        </tr>
        </thead>
        <tbody class="rsfirewall-whitelisted-files">
        <?php foreach ($whitelisted_files as $i => $file) { ?>
            <tr<?php echo ($i%2 == 1 ? ' class="alternate"' : ''); ?> id="rsfirewall_file_id_<?php echo ($i+1);?>">
                <td><input type="checkbox" class="rsfirewall-whitelist-delete" name="rsfirewall-whitelist-delete-file[]" data-file="<?php echo $file->file; ?>" data-folder="<?php echo $file->folder; ?>" style="margin-left:8px"/></td>
                <td><?php echo $file->file; ?></td>
                <td><?php echo $file->folder; ?></td>
                <td><?php echo $file->pattern; ?></td>
            </tr>
        <?php } ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="4"><input class="rsfirewall-btn danger small" type="button" id="rsfirewall-whitelist-delete" value="<?php esc_attr_e( 'Delete', 'rsfirewall' ); ?>" /></td>
        </tr>
        </tfoot>
        <input type="hidden" id="rsfirewall-whitelist-delete-nonce" value="<?php echo wp_create_nonce( 'delete_whitelisted' ); ?>"/>
    </table>
<?php } else { ?>
    <div class="alert alert-info" <?php echo ($whitelisted_files ? ' style="display:none"' : ''); ?> id="rsfirewall-whitelist-message"><?php echo esc_html__('No files have been safelisted yet!', 'rsfirewall'); ?></div>
<?php } ?>