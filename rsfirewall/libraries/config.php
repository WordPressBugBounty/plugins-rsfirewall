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

class RSFirewall_Config
{
    public static function get($value, $default = false)
    {
        static $values;

        if ($values === null)
        {
            $values = array();
            $model  = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Configuration', 'get_instance'));

            foreach ($model->form->get_sections() as $section)
            {
                $options = get_option($section);
                if (is_array($options))
                {
                    $values = array_merge($values, $options);
                }
            }
        }

        return isset($values[$value]) ? $values[$value] : $default;
    }
}