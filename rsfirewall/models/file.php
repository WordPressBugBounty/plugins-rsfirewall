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

class RSFirewall_Model_File extends RSFirewall_Model
{
    /**
     * @return object
     * @throws Exception
     */
    public function get_contents()
    {
        return (object) array(
            'file'          => $this->get_local_filename(),
            'file_contents' => $this->get_file_contents(),
            'status'        => $this->get_status()
        );
    }

    /**
     * @return bool|string
     * @since 1.0.0
     */
    public function get_filename()
    {
        if (isset($_REQUEST['file'])) {

            return urldecode($_REQUEST['file']);
        }

        return false;
    }

    /**
     * @return string
     * @since 1.0.0
     */
    protected function get_local_filename()
    {
        return RSFIREWALL_SITE . '/' . $this->get_filename();
    }

    /**
     * @since 1.0.0
     * @return string
     * @throws Exception
     */
    public function get_file_contents()
    {
        $path = $this->get_local_filename();

        if (!file_exists($path)) {
            throw new Exception(sprintf(__('Couldn\'t find %s .', 'rsfirewall'), $path));
        }

        if (!is_readable($path)) {
            throw new Exception(sprintf(__('%s is not readable.', 'rsfirewall'), $path));
        }

        if (!is_file($path)) {
            throw new Exception(sprintf(__('%s is not a file.', 'rsfirewall'), $path));
        }

        return file_get_contents($path);
    }

    /**
     * @since 1.0.0
     * @return array|bool
     * @throws Exception
     */
    public function get_status()
    {
        $path = $this->get_local_filename();

        if (!file_exists($path)) {
            throw new Exception(sprintf(__('Couldn\'t find %s .', 'rsfirewall'), $path));
        }

        if (!is_readable($path)) {
            throw new Exception(sprintf(__('%s is not readable.', 'rsfirewall'), $path));
        }

        if (!is_file($path)) {
            throw new Exception(sprintf(__('%s is not a file.', 'rsfirewall'), $path));
        }

        $check_model = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Check', 'get_instance'));

        return $check_model->signatures_check($path);
    }
}