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

class RSFirewall_Autoloader
{
    public function __construct()
    {
        spl_autoload_register(array($this, 'load'));
    }

    public function load($class)
    {
        /**
         * All classes are prefixed with RSFirewall_
         */
        $parts = explode('_', $class);

        if ($parts[0] == 'RSFirewall')
        {
            /**
             * Controller autoload.
             */
            if (in_array('Controller', $parts))
            {
                $this->loadMVC('controller', $parts);
            }

            /**
             * Model autoload.
             */
            if (in_array('Model', $parts))
            {
                $this->loadMVC('model', $parts);
            }

            /**
             * Helper autoload.
             */
            if (in_array('Helper', $parts))
            {
                $pro_path = '';
                if (isset($parts[2])) {
                    $file_name = $parts[2];

                    $file_name = RSFirewall_Helper::removeProPart($file_name);

                    $path = RSFIREWALL_BASE . 'helpers/' . strtolower($file_name) . '.php';
                    $pro_path = RSFIREWALL_BASE . 'proversion/helpers/' . strtolower($file_name) . '.php';
                } else {
                    $path = RSFIREWALL_BASE . 'helpers/rsfirewall.php';
                }

                if (file_exists($path))
                {
                    require_once $path;
                }

                if (!empty($pro_path) && file_exists($pro_path)) {
                    require_once $pro_path;
                }
            }

            /**
             * System autoload.
             */
            if (in_array('Core', $parts))
            {

                if (isset($parts[2])) {
                    $file_name = array_slice($parts, 2);
                    $file_name = implode('-', $file_name);

                    $file_name = RSFirewall_Helper::removeProPart($file_name);

                    $path       = RSFIREWALL_BASE . 'libraries/core/' . strtolower($file_name) . '.php';
                    $pro_path   = RSFIREWALL_BASE . 'proversion/libraries/core/' . strtolower($file_name) . '.php';
                } else {
                    $file_name = $parts[1];

                    $file_name = RSFirewall_Helper::removeProPart($file_name);

                    $path       = RSFIREWALL_BASE . 'libraries/core/' . strtolower($file_name) . '.php';
                    $pro_path   = RSFIREWALL_BASE . 'proversion/libraries/core/' . strtolower($file_name) . '.php';
                }

                if (file_exists($path)) {
                    require_once $path;
                }

                // load the pro library if found
                if (file_exists($pro_path)) {
                    require_once $pro_path;
                }
            }

            /*
             * Core library autoload.
             */
            if (isset($parts[1]))
            {
                $file_name = $parts[1];
                $file_name = RSFirewall_Helper::removeProPart($file_name);

                $path       = RSFIREWALL_BASE . 'libraries/' . strtolower($file_name);
                $pro_path   = RSFIREWALL_BASE . 'proversion/libraries/' . strtolower($file_name);

                if (is_dir($path)) {
                    $path .= '/'.strtolower($file_name).'.php';
                } else {
                    $path .= '.php';
                }

                if (file_exists($path))
                {
                    require_once $path;
                }

                // handle pro
                if (is_dir($pro_path)) {
                    $pro_path .= '/'.strtolower($file_name).'.php';
                } else {
                    $pro_path .= '.php';
                }

                if (file_exists($pro_path))
                {
                    require_once $pro_path;
                }
            }
        }
    }

    protected function loadMVC($base, $parts)
    {
        /**
         * Load base class
         */
        require_once RSFIREWALL_BASE . 'libraries/' . $base . '.php';
        /**
         * Load extended class
         */
        if (isset($parts[2]))
        {
            $name = $parts[2];
            $name = RSFirewall_Helper::removeProPart($name);
            $name = strtolower($name);

            $path = RSFIREWALL_BASE . $base . 's/' . $name . '.php';
            if (file_exists($path))
            {
                require_once $path;
            }

            // if the pro constroller is detected load this as well
            $pro_path = RSFIREWALL_BASE .'proversion/'. $base . 's/' . $name . '.php';

            if (file_exists($pro_path)) {
                require_once $pro_path;
            }
        }
    }
}

$autoloader = new RSFirewall_Autoloader();