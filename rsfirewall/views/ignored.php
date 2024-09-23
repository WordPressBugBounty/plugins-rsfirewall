<?php
$files = $this->model->get_files();
?>
<table class="widefat">
    <thead>
    <tr>
        <th><?php echo __('Date', 'rsfirewall'); ?></th>
        <th><?php echo __('File', 'rsfirewall'); ?></th>
        <th><?php echo __('Reason', 'rsfirewall'); ?></th>
        <th>&shy;</th>
    </tr>
    </thead>
    <?php foreach ($files as $file) { ?>
        <tr>
            <td><?php echo $file->date; ?></td>
            <td><?php echo $file->file; ?></td>
            <td>
                <?php
                    if ($file->flag == 'C') {
                        echo __('Changes accepted', 'rsfirewall');
                    } else {
                        echo __('Missing file', 'rsfirewall');
                    }

                ?>
            </td>
            <td>
                <button class="rsfirewall-btn danger small" id="remove_ignored<?php echo $file->id ?>" onclick="RSFirewall.ignore.remove('<?php echo $file->id ?>')"><?php echo __('Remove', 'rsfirewall'); ?></button>
            </td>
        </tr>
    <?php } ?>
</table>
