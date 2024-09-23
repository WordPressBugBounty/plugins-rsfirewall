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

class RSFirewall_Controller
{
    public $model;

    /**
     * Holds extended class filename (eg. RSFirewall_Controller_Configuration => configuration)
     */
    protected $filename;

    /**
     * Holds the rsfirewall version
     */
    protected $version;


    /**
     * RSFirewall_Controller constructor.
     * Loads model.
     */
    public function __construct()
    {
        // Get filename
        $this->filename = str_ireplace('RSFirewall_Controller_', '', get_class($this));

        // Load the version number
        $this->version = RSFirewall::get_instance()->get_version();

        // Get proper model name
        $model_name = RSFirewall_Helper::removeProPart($this->filename);

        // Sanitize filename
        $this->filename = strtolower($this->filename);
        $this->filename = preg_replace('/[^a-z0-9_]/', '', $this->filename);

        // Load model
        $this->model = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_'.ucfirst($model_name), 'get_instance'));
    }

    /**
     * Gets an instance to the requested controller
     *
     * @return RSFirewall_Controller
     */
    public static function get_instance()
    {
        static $instances = array();

        $class = get_called_class();

        if ( empty($instances[$class]) )
        {
            $instances[$class] = new $class;
        }

        return $instances[$class];
    }

    /**
     * Loads the necessary css/scripts for the specific controller/view
     */

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
     * Loads default view.
     */
    public function display($tmpl = '')
    {
        $template_name = $this->filename;
        // if the Pro part is present then it must be removed
        $template_name = RSFirewall_Helper::removeProPart($template_name);

        if (is_string($tmpl) && !empty($tmpl)) {
            $template_name .= '_'.$tmpl;
        }

        $path = RSFIREWALL_BASE . 'views/' . $template_name . '.php';
        $pro_path = RSFIREWALL_BASE . 'proversion/views/' . $template_name . '.php';


        if (!file_exists($pro_path) && !file_exists($path))
        {
            wp_die("View {$template_name} does not exist.");
        }

        // include first the pro then the normal
        include (file_exists($pro_path) ? $pro_path : $path);
    }

    /**
     * @param $val
     *
     * @return mixed|string
     */
    protected function json_encode( $val ) {
        if ( is_string( $val ) ) {
            $val = str_replace( array( "\n", "\r", "\t", "\v", "\f" ), array( '\n', '\r', '\t', '\v', '\f' ), $val );

            return '"' . addcslashes( $val, '"\\' ) . '"';
        }

        if ( is_numeric( $val ) ) {
            return $val;
        }

        if ( $val === null ) {
            return 'null';
        }

        if ( $val === true ) {
            return 'true';
        }

        if ( $val === false ) {
            return 'false';
        }

        $assoc = is_array( $val ) ? array_keys( $val ) !== range( 0, count( $val ) - 1 ) : true;

        $res = array();
        foreach ( $val as $k => $v ) {
            $v = $this->json_encode( $v );
            if ( $assoc ) {
                $k = '"' . addcslashes( $k, '"\\' ) . '"';
                $v = $k . ':' . $v;
            }
            $res[] = $v;
        }
        $res = implode( ',', $res );

        return ( $assoc ) ? '{' . $res . '}' : '[' . $res . ']';
    }

    /**
     * @param $response
     *
     * @return mixed|string|void
     */
    protected function encode( $response ) {
        $result = json_encode( $response );

        // Added a failsafe in case the response isn't encoded - at least we'll have something to work with.
        if ( $result === false ) {
            $result = $this->json_encode( $response );
        }

        return $result;
    }

    /**
     * @param      $success
     * @param null $data
     */
    protected function show_response( $success, $data = null ) {
        // compute the response
        $response          = new stdClass();
        $response->success = $success;
        if ( $data ) {
            $response->data = $data;
        }

        // show the response
        echo $this->encode( $response );

        // close
        exit();
    }
}