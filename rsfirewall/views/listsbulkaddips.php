<?php


$prefix = $this->model->getPrefix();
$back_url = admin_url( 'edit.php?post_type='.$prefix.'lists');
$form_url = wp_nonce_url(admin_url( 'admin.php?page=rsfirewall_lists_bulk_add_ips'), 'rsfirewall', 'rsf-actions').'&handler=listsbulkaddips&task=bulk_add_ips';

// Get saved form data if any
$saved_data = $this->model->get_saved_form_data();

?>

<div class="wrap">
    <h1><?php _e('Bulk Add IP Addresses', 'rsfirewall'); ?></h1>
    <p><?php _e('Add multiple IP addresses to your blocklist or safelist at once.', 'rsfirewall'); ?></p>
    
    <form method="post" action="<?php echo esc_url($form_url); ?>" id="rsf-bulk-add-form">
        <table class="form-table">
        <?php
            foreach ( $this->model->form->section->field as $field ) {
                $callback = array( 'RSFirewall_Helper_Fields', (string) $field->attributes()->type );
                $args     = array(
                    'field'   => $field,
                    'section' => (string) $this->model->form->section->attributes()->name,
                    'value'   => $saved_data[(string) $field->attributes()->name],
                    'class'   => (string) $field->attributes()->class
                );

                if ( is_callable( $callback ) ) {
                    ?>
                    <tr>
                        <th scope="row"><?php echo RSFirewall_Helper_Fields::label_for($field); ?></th>
                        <td><?php call_user_func( $callback, $args ); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </table>
        <p class="submit">
            <a href="<?php echo esc_url($back_url); ?>" class="button"><?php _e('Back to the list', 'rsfirewall'); ?></a>
            <button type="submit" class="button button-primary" style="float:right;"><?php _e('Add IPs', 'rsfirewall'); ?></button>
        </p>
    </form>
</div>