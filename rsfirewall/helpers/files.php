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


class RSFirewall_Helper_Files {
    protected $ud_array_files = array(); // the uni-dimensional array of all the files
    protected $files;
    /**
     * The class constructor.
     *
     */
    public function __construct() {
        // Set the files source.
        $this->files = !empty($_FILES) ? $_FILES : null;
    }

    public static function get_instance() {
        static $inst;

        if (is_null($inst)) {
            $inst =  new RSFirewall_Helper_Files();
        }

        return $inst;
    }

    /**
     * Gets a value from the files data.
     *
     * @param   string  $name     The name of the input property (usually the name of the files INPUT tag) to get.
     * @param   mixed   $default  The default value to return if the named property does not exist.
     *
     * @return  mixed  The filtered input value.
     */
    public function get($name = null, $default = null)
    {

        if (is_null($this->files)) {
            return $default;
        }

        if (!is_null($name) && isset($this->files[$name]))
        {
            $results = $this->decode_files(
                array(
                    $this->files[$name]['name'],
                    $this->files[$name]['type'],
                    $this->files[$name]['tmp_name'],
                    $this->files[$name]['error'],
                    $this->files[$name]['size'],
                )
            );

            return $results;
        } else if (is_null($name)) {
            $results = array();
            foreach ($this->files as $k => $file) {
                $results[$k] = $this->decode_files(
                    array(
                        $file['name'],
                        $file['type'],
                        $file['tmp_name'],
                        $file['error'],
                        $file['size'],
                    )
                );
            }
            return $results;
        }

        return $default;
    }

    /**
     * Method to put all the files in a single uni-dimensional array.
     */
    public function compact_files($files) {
        if (is_null($files)) {
            return array();
        }

        foreach ($files as $file) {
            if (!isset($file['name']) || is_array($file['name'])) {
                $this->compact_files($file);
            } else  {
                if (!empty($file['name'])) {
                    $this->ud_array_files[] = (object) $file;
                }
            }
        }

        return $this->ud_array_files;
    }

    /**
     * Method to decode files array.
     *
     * @param   array  $files  The files array to decode.
     *
     * @return  array
     */
    protected function decode_files(array $files)
    {
        $result = array();

        if (is_array($files[0]))
        {
            foreach ($files[0] as $k => $v)
            {
                $result[$k] = $this->decode_files(array($files[0][$k], $files[1][$k], $files[2][$k], $files[3][$k], $files[4][$k]));
            }

            return $result;
        }

        return array('name' => $files[0], 'type' => $files[1], 'tmp_name' => $files[2], 'error' => $files[3], 'size' => $files[4]);
    }
}