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

class RSFirewall_Menu
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;

        add_action('admin_menu', array($this, 'init'));
    }

    public function init()
    {
        $instance = $this->get_item_instance();
        if (!is_null($instance))
        {
            $callback = array($instance, $this->item['function']);
        }
        else
        {
            $callback = '';
        }

        /**
         * Adds the RSFirewall! menu page
         */
        add_menu_page(
            $this->item['page_title'],
            $this->item['menu_title'],
            $this->item['capability'],
            $this->item['menu_slug'],
            $callback,
            RSFIREWALL_URL . $this->item['icon']
        );
    }

    protected function get_item_instance() {
        static $instance = array();

        if (!isset($instance[$this->item['name']])) {
            if ($this->item['function']) {
                $instance[$this->item['name']] = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Controller_' . ucfirst($this->item['name']), 'get_instance'));
            } else {
                $instance[$this->item['name']] = null;
            }
        }

        return $instance[$this->item['name']];
    }

    public function load_scripts(){
        $instance = $this->get_item_instance();
        if (!is_null($instance)) {
            if (method_exists($instance, 'load_scripts')) {
                $instance->load_scripts();
            }
        }
    }
}