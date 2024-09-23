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

/**
 * Class RSFirewall_Helper_Fields
 */
abstract class RSFirewall_Helper_Fields
{
    /**
     * Checkbox list.
     *
     * @param $args array containing:
     * 'section' => section name,
     * 'field' => JSimpleXMLElement,
     * 'value' => value set in the database or null
     */
    public static function checkboxes( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];
        $values     = (array) $args['value'];
        $html       = '<fieldset>';
        $i          = 0;


        foreach ($field->option as $option)
        {
            $id     = (string) $field->attributes()->name;
            $label  = (string) $option;

            $attributes = array(
                'id'      => $id.$i,
                'name'    => sprintf('%s[%s][]', $section, $id),
                'value'   => (string) $option->attributes()->value,
            );


            if (!strlen($attributes['value']))
            {
                $attributes['value'] = $label;
            }

            // handle the checked attribute
            if (in_array($attributes['value'], $values) || (!$values && $option->attributes()->checked)) {
                $attributes['checked'] = 'checked';
            }

            // Handle the rest of the attributes
            foreach ($field->attributes() as $attr => $value) {
                // ignore the type
                if ($attr == 'type') continue;

                $value = (string) $value;
                if (!isset($attributes[$attr]) && !empty($value)) {
                    $attributes[$attr] = $value;
                }
            }

            $output_attrs = array();
            foreach ($attributes as $attr => $value) {
                $output_attrs[] = esc_attr($attr).'="'.esc_attr($value).'"';
            }

            $html .= '
                <label for="' . esc_attr($id . $i) . '">
                    <input type="checkbox" '.implode(' ', $output_attrs).' autocomplete="off"/>
                ' . esc_html__($label, 'rsfirewall') . '
                </label>
                <br />';

            $i++;
        }

        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Switchery - simple Yes/No radio. With callbacks if needed
     *
     * @param $args
     */
    public static function switchery( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];
        $value      = empty($args['value']) ? '0' : 1;

        $id         = (string) $field->attributes()->name;
        $name       = sprintf('%s[%s]', $section, $id);
        $onchange   = (string) $field->attributes()->onchange;

        if (isset($field->attributes()->check_before)) {
            $callback_data = explode('|', $field->attributes()->check_before);
            $callback = array_shift($callback_data);

            // add the necessary variables
            $additional_args = array($id, $value, $onchange, $name);
            $callback_data = array_merge($callback_data, $additional_args);

            if(method_exists('RSFirewall_Helper_Fields', $callback) && $html = call_user_func_array(array('RSFirewall_Helper_Fields', $callback), $callback_data)) {
                echo $html;
                return;
            }
        }


        $html = '
            <fieldset>
                <input class="rsfirewall-switch-field" data-id="' . esc_attr($id) . '" type="checkbox" ' . checked($value, 1, false) . ($onchange ? ' onchange="' . esc_attr($onchange) . '"' : '') . '/>
                <input class="rsfirewall-switch-value-holder" type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />
            </fieldset>
        ';


        echo $html;
    }

    /**
     * We will not rely on the database to get the value
     *
     * @param $path , the path to the directory which needs to be checked
     * @param $id
     * @param $value
     * @param $onchange
     * @param $name
     *
     * @return string $html
     */
    protected static function is_harden($path, $id, $value, $onchange, $name) {
        // check if the path is hardened
        $path = rtrim(ABSPATH, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
        if (RSFirewall_Helper_Harden::is_hardened($path)) {
            $value = 1;
        } else {
            $value = '0';
        }

        $html = '
            <fieldset>
                <input class="rsfirewall-switch-field" data-id="' . esc_attr($id) . '" type="checkbox" ' . checked($value, 1, false) . ($onchange ? ' onchange="' . esc_attr($onchange) . '"' : '') . '/>
                <input class="rsfirewall-switch-value-holder" type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />
            </fieldset>
        ';

        return $html;
    }

    /**
     * Password field.
     *
     * @param $args
     */
    public static function password( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];

        $id     = (string) $field->attributes()->name;
        $name   = sprintf('%s[%s]', $section, $id);

        echo '<input type="password" autocomplete="off" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" />';
    }

	/**
	 * TextBox Field
	 *
	 * @param $args
	 */
	public static function textbox( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];
        $value      = $args['value'];

        $id     = (string) $field->attributes()->name;
        $name   = sprintf('%s[%s]', $section, $id);

        echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" />';
	}

    /**
     * Textarea Field
     *
     * @param $args
     */
    public static function textarea( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];
        $value      = $args['value'];

        $id     = (string) $field->attributes()->name;
        $name   = sprintf('%s[%s]', $section, $id);
        $rows   = isset($field->attributes()->rows) ? ' rows="'.(string) $field->attributes()->rows.'"' : '';
        $cols   = isset($field->attributes()->cols) ? ' cols="'.(string) $field->attributes()->cols.'"' : '';

        echo '<textarea type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '"'.$rows.$cols.'>' . esc_html($value) . '</textarea>';
    }

    /**
     * Separator field.
     *
     * @param $args
     */
    public static function separator( $args )
    {
        echo '<hr />';
    }

    public static function only_pro() {
        $html = '<div class="alert alert-info">';
        $html .= '	<h4 style="margin-top:5px; margin-bottom:5px;">' . __('This feature is not available in the free version of RSFirewall!', 'rsfirewall') . '</h4>';
        $html .= '	<p>' . esc_attr__('If you wish to use this feature please consider purchasing the full version of RSFirewall!', 'rsfirewall') . '</p>';
        $html .= '	<p><a href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html" class="button-primary">' . __('Purchase the full version of RSFirewall!', 'rsfirewall') . '</a></p>';
        $html .= '</div>';

        echo $html;
    }

    public static function only_pro_field() {
        $html = '<div class="alert alert-info" style="max-width:635px">';
        $html .= '	<p>' . __('This feature is not available in the free version of RSFirewall!', 'rsfirewall') . ' <a href="https://www.rsjoomla.com/wordpress-plugins/wordpress-security-plugin.html" class="button-primary">' . __('Purchase the full version of RSFirewall!', 'rsfirewall') . '</a></p>';
        $html .= '</div>';

        echo $html;
    }

    /**
     * Selectize field.
     *
     * @param $args
     */
    public static function select( $args )
    {
        $field      = $args['field'];
        $section    = $args['section'];
        $values     = (array) $args['value'];

        $id       = (string) $field->attributes()->name;
        $name     = sprintf('%s[%s]', $section, $id);
        $multiple = (string) $field->attributes()->multiple ? 'multiple="multiple"' : '';
        if ($multiple)
        {
            $name .= '[]';
        }

        $html = '<select ' . $multiple . ' id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';

        $options = array();
        // Get options from the xml
        if ($field->option)
        {
            $options_field = RSFirewall_Helper::select_options($field->option);
            $options = array_merge($options, $options_field);
        }

        // Get options from the callback
        if ($callback = (string) $field->attributes()->options)
        {
            list($class, $function) = explode('::', $callback, 2);
            // remove the instance because it is always present and keep the clean function - legacy reasons (in case it is still present the old configuration.xml)
            $function  = str_replace(array('get_instance()->', '()', ';'), '', $function);

            // remove any unwanted spaces
            $function = trim($function);

            $handler = RSFirewall_Helper::call_user_func_pro(array($class, 'get_instance'));
            if (is_callable(array($handler, $function))) {
                $options_callback = call_user_func(array($handler, $function));
                $options = array_merge($options, $options_callback);
            }
        }

        if (!empty($options)) {
            foreach ($options as $option) {
                $label = $option->label;
                $value = $option->value;

                $checked = in_array($value, $values) ? 'selected="selected"' : '';

                $html .= '<option ' . $checked . ' value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
            }
        }

        $html .= '</select>';

        echo $html;
    }

	/**
	 * Upload Field
	 *
	 * @param $args
	 */
	public static function upload( $args )
    {
		$check = array();

		if ( ! empty( $args['action'] ) ) {
			$class_name    = $args['action'][0];
			$function_name = $args['action'][1];
			if ( method_exists( $class_name, $function_name ) ) {
				$check = $class_name::$function_name();
			}
		}

		$html = '<input type="file" id="' . $args['field'] . '" name="' . $args['section'] . '[' . $args['field'] . ']" />';
		if ( ! empty( $check['message'] ) ) {
			$html .= '<br /><small>' . $check['message'] . '</small>';
		}
		echo $html;
	}

    /**
     * Textarea with filemanager
     *
     * @param $args
     */

    public static function textarea_filemanager( $args ) {
        $field      = $args['field'];
        $section    = $args['section'];
        $value      = $args['value'];

        $id     = (string) $field->attributes()->name;
        $name   = sprintf('%s[%s]', $section, $id);

        $limit_to = is_null($field->attributes()->limit_to) ? '' : (string) $field->attributes()->limit_to;

        // the modal button
        echo '<button type="button" class="button-primary" data-filemanager="1"'.(!empty($limit_to) ? ' data-limitto="'.esc_attr($limit_to).'"' : '').' data-selection="#'.esc_attr($id).'" data-toggle="rsmodal" data-target="#rsmodal" data-title="'.__('File Manager', 'rsfirewall').'" data-usefooter="1" data-showclose="1" data-size="large">'.__('Open File Manager', 'rsfirewall').'</button>';
        // separator
        echo '<br/><br/>';
        // the actual textarea
        echo '<textarea type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" style="width:100%; min-height:200px">' . esc_html($value) . '</textarea>';
    }

    /* Handle the labels */
    /**
     * Function to determine the custom label if exists
     *
     * @param $field
     *
     * @return string $label, the actual label
     *
     */
    public static function label_for($field) {
        $label = '';
        $attr = $field->attributes();
        $description = (isset($attr->description) && !empty($attr->description)) ? self::add_helptip_markup($attr->description) : '';
        $required = (isset($attr->required ) && !empty($attr->required )) ? ' *' : '';

        if (isset($attr->label)) {
            if (method_exists('RSFirewall_Helper_Fields', 'label_'.$attr->type)) {
                $label = call_user_func( array( 'RSFirewall_Helper_Fields', 'label_'.$attr->type ), $attr, $description, $required );
            }

            if (empty($label)) {
                $label = $attr->label.$required.$description;
            }
        } else  {
            $label = $attr->name.$required.$description;
        }


        return $label;

    }

    public static function add_helptip_markup($description = '') {
        if (!empty($description)) {
            $description = ' <span class="rsfirewall-help-tip" data-tip="'.esc_attr__($description, 'rsfirewall').'"></span>';
        }

        return $description;
    }

    /**
     * Format the field separator label
     *
     * @param $attr, $description, $required
     *
     * @return string - the field separator label formatted
     */
    public static function label_separator($attr, $description = '', $required = '') {
        return '<h3>'.$attr->label.$required.$description.'</h3>';
    }
	
	/**
     * Get the current admin slug
     *
     * @param $args
     *
     * @return string - the URL for the backend
     */
	public static function current_slug($args) {
		$blog_id 	= get_current_blog_id();
		$admin_url  = get_site_url($blog_id, 'wp-admin');
		$slug 		= trim(RSFirewall_Config::get( 'admin_slug_text', '' ));

		if ((int) RSFirewall_Config::get( 'enable_admin_slug', 0 ) && strlen($slug) > 0) {
			$admin_url = get_site_url($blog_id) . '/' . htmlentities($slug, ENT_COMPAT, 'utf-8');
		}

		echo $admin_url;
	}

    public static function label_only_pro($attr, $description = '', $required = '') {
        return '<h3>'.$attr->label.$required.$description.'</h3>';
    }

    /**
     * Generate a modal button with functionality based on the callback, if any
     *
     * @param $args
     *
     * @return string - the field separator label formatted
     */
    public static function modal_custom($args) {
        $field      = $args['field'];

        if (!isset($field->attributes()->callback)) {
            echo 'No callback defined';
        } else {
            $callback_data = explode('|', $field->attributes()->callback);
            $callback = array_shift($callback_data);

            if(method_exists('RSFirewall_Helper_Fields', $callback) && $html = call_user_func_array(array('RSFirewall_Helper_Fields', $callback), $callback_data)) {
                echo $html;
                return;
            }
        }
    }

    // Modal Custom Callbacks

    protected static function add_whitelisted_php_files() {
        // check which directory is hardened
        $check_harden_folders = RSFirewall_Helper::check_hardened_directories();

        if (in_array(true, $check_harden_folders)) {

            $files_count = RSFirewall_Helper_Harden::whitelisted_php_files(true);

            // the modal button
            echo '<button type="button" class="button-primary" data-whitelistfiles="1" data-toggle="rsmodal" data-target="#rsmodal" data-title="'.__('Safelist Blocked PHP Files', 'rsfirewall').'" data-usefooter="1" data-showclose="1" data-size="large">'.sprintf(esc_html__('Safelist Files (%s)', 'rsfirewall'), '<span id="rsf-whitelisted-count">'.$files_count.'</span>').'</button>';
        } else {
            echo '<div class="alert alert-info">'.esc_html__('There are no folders hardened! You can safelist PHP files only if any of the folders above are hardened.', 'rsfirewall').'</div>';
        }
    }
}