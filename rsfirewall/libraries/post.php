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

class RSFirewall_Post extends RSFirewall_Model
{
    /**
     * Loads the necessary css/scripts for the specific post model/view, because here we do not use a controller
     */
    protected $messages_types = array(
        'error', 'info', 'success', 'warning'
    );

    public function load_scripts(){
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
    }

    /**
     * Must be declared in case we do not use them in a specific controller so that warnings would not show up
     */
    public function enqueue_scripts($pagenow){}
    public function enqueue_styles($pagenow){}

    /**
     * Register a custom post type.
     */
    public function register()
    {

        add_action( 'init', array($this, 'init') );
        add_action( 'save_post_' . $this->prefix.$this->filename, array($this, 'save'), 10, 2);
        add_action( 'manage_' . $this->prefix.$this->filename . '_posts_custom_column', array($this, 'column'), 10, 2 );

        add_filter( 'manage_edit-' . $this->prefix.$this->filename . '_columns', array($this, 'columns') );
        add_filter( 'manage_edit-' . $this->prefix.$this->filename . '_sortable_columns', array($this, 'sortable_columns') );

        // Filters for the bulk actions
        add_filter( 'bulk_actions-edit-'.$this->prefix.$this->filename, array($this, 'bulk_actions') );
        add_filter( 'handle_bulk_actions-edit-'.$this->prefix.$this->filename, array($this, 'handle_bulk_actions'), 10, 3 );

        add_action('admin_notices', array($this, 'show_messages'));
    }

    /**
     * Initialize a custom post type
     */
    public function init()
    {
        /* @override this in subclass */
    }

    /**
     * Save post.
     *
     * @param $post_id
     * @param $post
     *
     * @return int|void
     */
    public function save($post_id, $post)
    {
        if ($post->post_type == 'revision')
        {
            return;
        }

        $data = isset($_POST['data']) ? $_POST['data'] : array();

        foreach ($this->form->section->field as $field)
        {
            $name  = (string) $field->attributes()->name;
            $value = isset($data[$name]) ? sanitize_text_field($data[$name]) : '';
            if ( get_post_meta( $post->ID, $name, false ) ) {
                update_post_meta( $post->ID, $name, $value );
            } else {
                add_post_meta( $post->ID, $name, $value );
            }
        }
    }

    /**
     * Display custom column info.
     *
     * @param $column
     * @param $post_id
     */
    public function column($column, $post_id)
    {
        /* @override this in subclass */
    }

    /**
     * Display columns.
     *
     * @param $columns
     *
     * @return array
     */
    public function columns($columns)
    {
        return $columns;
    }

    /**
     * Make columns sortable.
     *
     * @param $columns
     *
     * @return mixed
     */
    public function sortable_columns($columns)
    {
        return $columns;
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
        return $actions;
    }

    /**
     * Handle the bulk actions
     *
     * @param $redirect_to - where to redirect after the action has been done
     * @param $action - the action that is called upon
     * @param $post_ids - the array containing the selected ids
     *
     * @return string - the proper redirect
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        return $redirect_to;
    }

    public function show_messages()
    {
        foreach ($this->messages_types  as $type) {
            if ($errors = $this->get_messages($type)) {
                ?>
                <div class="notice notice-<?php echo $type; ?> is-dismissible">
                    <?php foreach ($errors as $error) { ?>
                        <p><?php echo esc_html($error); ?></p>
                    <?php } ?>
                    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo __('Dismiss this notice.', 'rsfirewall');?></span></button>
                </div>
                <?php
            }

            $this->clear_messages($type);
        }
    }

    protected function clear_messages($type = 'error')
    {
        delete_transient($this->get_transient_name($type));
    }

    public function get_messages($type = 'error')
    {
        return get_transient($this->get_transient_name($type));
    }

    protected function set_message($message, $type = 'error') {
        $messages   = $this->get_messages($type);
        // if no message is set this is a boolean value (false)
        if (is_bool($messages))
        {
            $messages = array();
        }

        $messages[] = $message;

        // Remove empty values
        $messages = array_filter($messages);

        set_transient($this->get_transient_name($type), $messages, 15);
    }

    protected function get_transient_name($type = 'error')
    {
        return 'save_'.$this->prefix.$this->filename.'_'.$type.'_' . get_current_user_id();
    }
}