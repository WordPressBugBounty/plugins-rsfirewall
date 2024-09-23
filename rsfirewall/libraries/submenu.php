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

class RSFirewall_Submenu {
	protected $item;
	protected $isPost = false;
	protected $postModel;

	public function __construct($item) {
		$this->item = $item;

		add_action( 'admin_menu', array( $this, 'init' ) );

		/**
		 * Register custom posts if model extends RSFirewall_Post
		 */
		$class = 'RSFirewall_Model_' . ucfirst($this->item['name']);
		$model = RSFirewall_Helper::call_user_func_pro(array( $class, 'get_instance' ));
		if ( !is_null($model) ) {
			if ( $model instanceof RSFirewall_Post && is_callable( array( $model, 'register' ) ) ) {
				$model->register();
				$this->isPost = true;
				$this->postModel = $model;
			}
		}
	}

	public function init() {
		$instance = $this->get_item_instance();
		if (!is_null($instance)) {
			$callback = array( $instance, $this->item['function'] );
		} else {
			$callback = '';
		}

		/**
		 * Adds submenu pages
		 *
		 */
		add_submenu_page(
			$this->item['hidden'] ? '' : 'rsfirewall',
			$this->item['page_title'],
			$this->item['menu_title'],
			$this->item['capability'],
			$this->item['menu_slug'],
			$callback
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
		if (!$this->isPost) {
			$instance = $this->get_item_instance();
			if (!is_null($instance)) {
				if (method_exists($instance, 'load_scripts')) {
					$instance->load_scripts();
				}
			}
		} else {
			if (method_exists($this->postModel, 'load_scripts')){
				$this->postModel->load_scripts();
			}
		}
	}
}