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

class RSFirewall_Model_Exceptions extends RSFirewall_Post
{
    /**
     * Create a custom post type to hold the exceptions
     */
    public function init()
    {
        // Set UI labels for Custom Post Type
        $labels = array(
            'name'               => _x( 'Exceptions', 'Post Type General Name', 'rsfirewall' ),
            'singular_name'      => _x( 'Exception', 'Post Type Singular Name', 'rsfirewall' ),
            'menu_name'          => esc_html__( 'Exception', 'rsfirewall' ),
            'add_new'      		 => esc_html__( 'Add New Exception', 'rsfirewall' ),
            'add_new_item'       => esc_html__( 'Add New Exception', 'rsfirewall' ),
            'all_items'          => esc_html__( 'All Exceptions', 'rsfirewall' ),
            'view_item'          => esc_html__( 'View Exception', 'rsfirewall' ),
            'edit_item'          => esc_html__( 'Edit Exception', 'rsfirewall' ),
            'update_item'        => esc_html__( 'Update Exception', 'rsfirewall' ),
            'search_items'       => esc_html__( 'Search Exception', 'rsfirewall' ),
            'not_found'          => esc_html__( 'Not Found', 'rsfirewall' ),
            'not_found_in_trash' => esc_html__( 'Not found in Trash', 'rsfirewall' ),
        );

        // Set other options for Custom Post Type
        $args = array(
            'label'                => esc_html__( 'Exceptions', 'rsfirewall' ),
            'description'          => esc_html__( 'Exceptions added on your website', 'rsfirewall' ),
            'labels'               => $labels,
            'supports'             => false,
            'hierarchical'         => false,
            'show_ui'              => true,
            'show_in_menu'         => false,
            'show_in_nav_menus'    => false,
            'show_in_admin_bar'    => true,
            'menu_position'        => 5,
            'can_export'           => true,
            'has_archive'          => true,
            'exclude_from_search'  => true,
            'publicly_queryable'   => false,
            'rewrite'              => array( "slug" => $this->prefix."exceptions" ),
            'capabilities'         => array(
                'create_posts' => true,
                'delete_posts' => true,
                'delete_post' => true,
                // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
            ),
            'map_meta_cap'         => true,
            'register_meta_box_cb' => array( $this, 'add_metabox' )
        );

        // Registering your Custom Post Type
        register_post_type( $this->prefix.'exceptions', $args );

        // Show the current ip when adding
        add_action( 'admin_notices', array($this, 'admin_notice') );

        // Since we do not use the title or description, need to hide the body-content div
        add_action( 'admin_head', array($this, 'remove_content_div') );

        // Remove the publishing
        add_action( 'admin_menu', array($this, 'remove_meta_box'));

        // Remove the screen options to avoid selecting the slug box
        add_filter('screen_options_show_screen', array($this, 'remove_screen_options'));

        // Set the Layout column mode for the add/edit to 1 column
        add_filter('get_user_option_screen_layout_'.$this->prefix.'exceptions', function (){return 1;} );

        // Modify the search so that results are shown
        add_filter('pre_get_posts', array($this, 'refine_query'));

        // Modify the get_search_query for proper display
        add_filter('get_search_query', array($this, 'get_search_query'));

        // Modify the standard messages when updating/publishing
        add_filter('post_updated_messages', array($this, 'updated_messages'));

        if (function_exists('wp_untrash_post_set_previous_status'))
        {
            // When restoring a keep last post status
            add_filter('wp_untrash_post_status', 'wp_untrash_post_set_previous_status', 10, 3);
        }
        
        // Add custom filters
        add_action( 'restrict_manage_posts', array($this, 'add_filters') );
    }

    public function remove_meta_box() {
        remove_meta_box( 'submitdiv', $this->prefix.'exceptions', 'side' );
    }

    public function enqueue_styles($pagenow) {
        $screen_id    = RSFirewall_Helper::get_current_screen();
        if (($pagenow != 'post-new.php' || $pagenow != 'post.php') && $screen_id != $this->prefix.'exceptions') {
            return;
        }

        wp_enqueue_style( 'switchery', RSFIREWALL_URL . 'assets/js/vendors/switchery.min.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts($pagenow) {
        $screen_id    = RSFirewall_Helper::get_current_screen();

        if (($pagenow != 'post-new.php' || $pagenow != 'post.php') && $screen_id != $this->prefix.'exceptions') {
            return;
        }

        wp_enqueue_script('switchery', RSFIREWALL_URL . 'assets/js/vendors/switchery.min.js', array('jquery'), $this->version, false);
    }

    public function remove_content_div(){
        global $pagenow, $typenow;
        if (($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == $this->prefix.'exceptions') {
            echo '<style type="text/css"> #post-body-content { display:none; }</style>';
        }
    }

    public function remove_screen_options($options) {
        global $pagenow, $typenow;
        if (($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == $this->prefix.'exceptions') {
            return false;
        }

        return $options;
    }

    /**
     * Function used in the filter of changing the messages
     */
    public function updated_messages($messages) {
        $messages['exceptions'] = $messages['post'];

        $messages['exceptions'][1] = __('The Exception has been updated!', 'rsfirewall');
        $messages['exceptions'][4] = __('The Exception has been updated!', 'rsfirewall');
        $messages['exceptions'][6] = __('The Exception has been added!', 'rsfirewall');

        return $messages;
    }

    /**
     * Function to add the filters
     */
    function add_filters(){
        global $pagenow, $typenow;

        if ($pagenow == 'edit.php' && $typenow == $this->prefix.'exceptions') {
            $filters = array(
                'exception_type' => array(
                    esc_attr__('Select Type', 'rsfirewall') => 'all',
                    esc_attr__('User Agent', 'rsfirewall')   => 'ua',
                    esc_attr__('URL', 'rsfirewall')   => 'url',
                    esc_attr__('Plugin', 'rsfirewall')   => 'plug',
                )
            );

            foreach ($filters as $filter => $options) {
                ?>
                <select name="rsf_filter[<?php echo esc_attr($filter);?>]">
                    <?php
                    $current_v = (isset($_GET['rsf_filter']) && isset($_GET['rsf_filter'][$filter])) ? $_GET['rsf_filter'][$filter] : '';
                    foreach ($options as $label => $value) {
                        printf
                        (
                            '<option value="%s"%s>%s</option>',
                            $value,
                            $value == $current_v ? ' selected="selected"' : '',
                            $label
                        );
                    }
                    ?>
                </select>
                <?php
            }
        }
    }

    /**
     * Function used for handling the
     */
    public function refine_query($query) {
        global $pagenow, $typenow;

        if ($pagenow == 'edit.php' && $typenow == $this->prefix.'exceptions') {
            $custom_fields = array(
                "rsfirewall_match",
                "rsfirewall_reason"
            );
            // Handle the search term
            $searchterm = $query->query_vars['s'];

            // unset the 's' value from the query, because we do not need it anymore and interferes with our query
            $query->query_vars['s'] = "";

            $meta_query_search = array('relation' => 'OR');
            if (strlen($searchterm) != 0) {
                foreach($custom_fields as $cf) {
                    array_push($meta_query_search, array(
                        'key' => $cf,
                        'value' => esc_sql($searchterm),
                        'compare' => 'LIKE'
                    ));
                }
            };

            // Handle our own filter values
            $meta_query_filters = array('relation' => 'AND');
            if (isset($_GET['rsf_filter']) && !empty($_GET['rsf_filter'])) {
                foreach ($_GET['rsf_filter'] as $field => $value){
                    // in case the default value of a filter is set to 'all'
                    if ($value == 'all') {
                        continue;
                    }

                    array_push($meta_query_filters, array(
                        'key' => 'rsfirewall_' . esc_sql($field),
                        'value' => esc_sql($value),
                        'compare' => '='
                    ));

                }
            }

            $is_search = count($meta_query_search) > 1 ? 1 : 0;
            $is_filters = count($meta_query_filters) > 1 ? 3 : 0;

            $meta_query = array();
            switch ($combine = ($is_search + $is_filters)) {
                // when only the search is used
                case 1:
                    $meta_query = $meta_query_search;
                    break;

                // when only the filters are used
                case 3:
                    $meta_query = $meta_query_filters;
                    break;

                // when both filters and search are used
                case 4:
                    array_push($meta_query_filters, $meta_query_search);
                    $meta_query = $meta_query_filters;
                    break;
            }

            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Function used for handling the text of the query that is outputed in the "Search results for" statement
     */
    public function get_search_query($search_term) {
        global $pagenow, $typenow;

        if ($pagenow == 'edit.php' && $typenow == $this->prefix.'exceptions') {
            // Empty the search term in case there is a value
            $search_term = '';

            if (isset($_GET['s']) && !empty($_GET['s'])) {
                $search_term = esc_attr($_GET['s']);
            }
        }

        return $search_term;
    }

    /**
     * Build the proper metabox
     */
    public function add_metabox() {
        add_meta_box( 'rsfirewall_exceptions_metaboxes', esc_html__( 'Exception', 'rsfirewall' ), array(
            $this,
            'show_metabox'
        ), $this->prefix.'exceptions', 'normal', 'default' );

        // Publish metabox
        add_meta_box( 'rsfirewall_submitdiv', __( 'Publish', 'rsfirewall' ), array($this, 'show_metabox_publish'), $this->prefix.'exceptions', 'normal', 'core' );
    }

    /**
     * Actual Content of the metabox
     */
    public function show_metabox() {
        global $post;
        ?>
        <table class="form-table">
            <?php
            foreach ( $this->form->section->field as $field ) {
                $callback = array( 'RSFirewall_Helper_Fields', (string) $field->attributes()->type );
                $args     = array(
                    'field'   => $field,
                    'section' => (string) $this->form->section->attributes()->name,
                    'value'   => metadata_exists( 'post', $post->ID, (string) $field->attributes()->name ) ? get_post_meta( $post->ID, (string) $field->attributes()->name, true ) : (string) $field->attributes()->default
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
        <?php
    }

    /**
     * Actual Content of the publish metabox
     */
    public function show_metabox_publish() {
        global $post;

        $post_type = $post->post_type;
        $post_type_object = get_post_type_object($post_type);
        $can_publish = current_user_can($post_type_object->cap->publish_posts);
        $back_url = admin_url( 'edit.php?post_type='.$this->prefix.'exceptions');

        ?>
        <div id="back-action" style="float:left;">
            <a class="button button-primary" href="<?php echo $back_url; ?>"><?php echo __('Back to the list', 'rsfirewall'); ?></a>
        </div>

        <div id="publishing-action">
        <?php if ( !in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
                // We will only use the publish action
                if ( $can_publish ) { ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Publish') ?>"/>
                    <?php submit_button(__('Publish'), 'primary large', 'publish', false); ?>
                <?php } else { ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Submit for Review') ?>"/>
                    <?php submit_button(__('Submit for Review'), 'primary large', 'publish', false); ?>
                    <?php
                }
            } else { ?>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e('Update') ?>" />
                <input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update' ) ?>" />
            <?php } ?>
        </div>
        <div id="delete-action" style="float:right; margin-right:50px">
            <?php
            if ( current_user_can( "delete_post", $post->ID ) ) {
                if ( !EMPTY_TRASH_DAYS )
                    $delete_text = __('Delete Permanently', 'rsfirewall');
                else
                    $delete_text = __('Move to Trash', 'rsfirewall');
                ?>
                <a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo $delete_text; ?></a><?php
            }
            ?>
        </div>
        <div class="clear"></div>
        <?php
    }

    /**
     * Display bulk actions.
     *
     * @param $actions
     *
     * @return array
     */
    public function bulk_actions($actions)
    {
        // Remove the edit action
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }

        return $actions;
    }

    /**
     * Save post.
     *
     * @param $post_id
     * @param $post
     *
     * @return int|void
     */
    public function save( $post_id, $post ) {
        if (isset($_POST['data'])) {
            $is_update = $this->check_if_update($post);

            if (strlen($_POST['data']['rsfirewall_match'])) {
                parent::save($post_id, $post);
            } else  {
                // If the Match field is empty show this error
                $this->set_message(__('You must enter a Match!', 'rsfirewall'));

                if (!$is_update) {
                    // Get and delete other auto-saves/revisions if any
                    if ($revisions = wp_get_post_revisions($post_id)) {
                        foreach($revisions as $rev_post) {
                            wp_delete_post($rev_post->ID);
                        }
                    }

                    // Finally delete the post itself
                    wp_delete_post($post->ID);

                    // Redirect to the form
                    wp_redirect( wp_get_referer() );
                    exit();
                } else {
                    wp_redirect(get_edit_post_link($post_id, 'url'));
                    exit();
                }
            }
        }
    }

    /**
     * Function to check if the post is an update or an insert.
     */
    protected function check_if_update($post){
        return strtotime($post->post_date_gmt) != strtotime($post->post_modified_gmt);
    }

    /**
     * Add Columns to the Threat Tables
     *
     * @param $columns
     *
     * @return array
     */
    public function columns( $columns ) {
        $columns = array(
            'cb'                          => '<input type="checkbox" />',
            'date'                        => esc_html__( 'Date Added', 'rsfirewall' ),
            'rsfirewall_match'            => esc_html__( 'Match', 'rsfirewall' ),
            'rsfirewall_reason'           => esc_html__( 'Reason', 'rsfirewall' ),
            'rsfirewall_exception_type'   => esc_html__( 'Exception Type', 'rsfirewall' ),
            'actions'                     => esc_html__( 'Change Status', 'rsfirewall' )
        );

        return $columns;
    }

    /**
     * Display data in the backend table
     *
     * @param $column
     * @param $post_id
     */
    public function column( $column, $post_id ) {
        $value = get_post_meta( $post_id, $column, true );

        switch ( $column ) {
            case 'rsfirewall_match':
                $value = '<a href="'.get_edit_post_link(($post_id)).'">'.esc_html($value).'</a>';
                break;

            case 'rsfirewall_exception_type':
                if ( $value == 'ua' ) {
                    $value = esc_html__( 'User Agent', 'rsfirewall' );
                } else if ($value == 'url') {
                    $value = esc_html__( 'Url', 'rsfirewall' );
                } else if ($value == 'plug') {
                    $value = esc_html__( 'Plugin', 'rsfirewall' );
                }
                break;
				
			case 'rsfirewall_reason':
				$value = esc_html($value);
			break;

            case 'actions':
                if ($status = get_post_status($post_id)) {
                    $edit_url = admin_url( 'edit.php?post_type='.$this->prefix.'exceptions');
                    if ($status == 'publish' || $status == 'trash') {
                        $value = '<a href="' . wp_nonce_url($edit_url, 'rsfirewall', 'rsf-actions') . '&handler=exceptions&task=change_status&id=' . $post_id . '" class="rsfirewall-btn' . ($status == 'publish' ? ' danger' : '') . ' small" type="button" id="rsf-list-status" data-pid="' . $post_id . '">' . __(($status == 'publish' ? 'Add to trash' : 'Publish'), 'rsfirewall') . '</a>';
                    } 
                    else if ($status == 'draft')
                    {
                        $value = '<a href="' . wp_nonce_url($edit_url, 'rsfirewall', 'rsf-actions') . '&handler=exceptions&task=change_status&id=' . $post_id . '" class="rsfirewall-btn small" type="button" id="rsf-list-status" data-pid="' . $post_id . '">'.__('Publish', 'rsfirewall').'</a>';
                    } 
                    else
                    {
                        $value = __($status, 'rsfirewall');
                    }
                }
                break;
        }
        if ( ! empty( $value ) ) {
            echo $value;
        }
    }

    /**
     * Function to change the current status of the selected post
     */
    public function change_status() {
        $post_id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
        $back_url = wp_get_referer();

        $change_status = true;
        // Check if the id is not empty (0)
        if (empty($post_id)) {
            $this->set_message(__('The post id is not correct!', 'rsfirewall'));
            $change_status = false;
        }

        // Check if the id is available
        if (!get_post_status($post_id)) {
            $this->set_message(__('This post does not exist!', 'rsfirewall'));
            $change_status = false;
        }

        // change post status
        if ($change_status ) {
            $current_status = get_post_status($post_id);
            $new_status = $current_status == 'publish' ? 'trash' : 'publish';

            if ($new_status == 'trash')
            {
                wp_trash_post($post_id);
            }
            else
            {
                wp_update_post(array('ID' => $post_id, 'post_status' => $new_status));
            }
        }

        // Redirect to the list
        RSFirewall_Helper::redirect($back_url);
    }

    /**
     * Make columns sortable
     *
     * @param $columns
     *
     * @return mixed
     */
    public function sortable_columns( $columns ) {
        $columns['rsfirewall_match']            = 'rsfirewall_match';
        $columns['rsfirewall_reason']           = 'rsfirewall_reason';
        $columns['rsfirewall_exception_type']   = 'rsfirewall_exception_type';

        return $columns;
    }

   /**
     * Function to display notices
     */
    public function admin_notice() {
        $screen_id    = RSFirewall_Helper::get_current_screen();

        if ($screen_id == $this->prefix.'exceptions') {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><?php echo wp_kses_post(__('Your IP address is currently detected as '.RSFirewall_Helper::get_ip().'.', 'rsfirewall')); ?></p>
            </div>
            <?php
        }
    }
}