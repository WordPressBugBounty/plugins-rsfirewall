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

class RSFirewall_Model
{
    /**
     * Holds extended class filename (eg. RSFirewall_Model_Configuration => configuration)
     */
    protected $filename;

    /**
     * Holds form properties
     */
    public $form;

    /**
     * Holds the rsfirewall version
     */
    protected $version;

    /**
     * Holds the post types prefix
     */
    protected $prefix = RSFIREWALL_POSTS_PREFIX;

    public function __construct()
    {
        // Get filename
        $this->filename = str_ireplace('RSFirewall_Model_', '', strtolower(get_class($this)));

        // Sanitize filename
        $this->filename = preg_replace('/[^a-z0-9_]/', '', $this->filename);

        // Remove the Pro part if any
        $this->filename = RSFirewall_Helper::removeProPart($this->filename);

        // Load the version number
        $this->version = RSFirewall::get_instance()->get_version();

        /**
         * Load form
         */
        $path = RSFIREWALL_BASE . 'models/' . $this->filename . '.xml';
        $pro_path = RSFIREWALL_BASE . 'proversion/models/' . $this->filename . '.xml';

        // Load the Pro one, if exists
        if (file_exists($pro_path))
        {
            $this->form = new RSFirewall_Form($pro_path);
        } else if (file_exists($path))
        {
            $this->form = new RSFirewall_Form($path);
        }
    }

    /**
     * Gets an instance to the requested model
     *
     * @return RSFirewall_Model
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
}