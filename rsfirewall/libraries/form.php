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

class RSFirewall_Form
{
    /**
     * @var SimpleXMLElement Raw XML of the form.
     */
    protected $xml;

    /**
     * @var array Clean formatted version of the form's XML.
     */
    public $sections = array();

    public function __construct($filename)
    {
        // Load the XML file
        $this->xml = simplexml_load_file( $filename );

        // Load the locals for this form
        $f_parts = explode('/',$filename);
        $xml_filename = array_pop($f_parts);
        $xml_filename = str_replace('.xml', '', $xml_filename);

        $locals = RSFirewall_i18n::get_locale( 'form_'.$xml_filename );

        // Load the form and translate it
        foreach ($this->xml->section as $section)
        {

            $section_name   = (string) $section->attributes()->name;
            $section_label  = (string) $section->attributes()->label;

            // translate the section label
            $section_label  = isset($locals[$section_label]) ? $locals[$section_label] : $section_label;

            $this->sections[$section_name] = array(
                'name'      => $section_name,
                'label'     => $section_label,
                'fields'    => array()
            );

            if (isset($section->attributes()->callback)) {
                $this->sections[$section_name]['callback']  = (string) $section->attributes()->callback;
            }

            if (isset($section->attributes()->hide_label) && $section->attributes()->hide_label) {
                $this->sections[$section_name]['hide_label']  = true;
            }

            foreach ($section->field as $field)
            {
                $attr       = $field->attributes();

                // Translate the label attributes
                $lbl_trans = (string) $attr->label;
                $lbl_trans = isset($locals[$lbl_trans]) ? $locals[$lbl_trans] : $lbl_trans;
                $field->attributes()->label = $lbl_trans;

                // Translate the description attributes
                $desc_trans = (string) $attr->description;
                $desc_trans = isset($locals[$desc_trans]) ? $locals[$desc_trans] : $desc_trans;
                $field->attributes()->description = $desc_trans;
				
				 // if the field holds roles
                $is_role_field = $field->attributes()->name == 'two_factor_auth_for_roles';

                // Translate the options of the field...if any
                if (!empty($field->option)) {
                    foreach ($field->option as $option) {
                        $option_trans = (string) $option[0];
						if ($is_role_field && $option_trans == 'option_administrator') {
                            $role_translation =  translate_user_role('administrator');
                            $option[0] = ucfirst($role_translation);
                        } else {
                            $option_trans = isset($locals[$option_trans]) ? $locals[$option_trans] : $option_trans;
                            $option[0] = $option_trans;
                        }
                    }

                }

                $field_name = (string) $attr->name;

                $this->sections[$section_name]['fields'][$field_name] = array(
                    'name' => $field_name,
                    'label' => (string) $attr->label,
                    'type'  => (string) $attr->type,
                    'field' => $field
                );
            }
        }
    }

    public function get_sections()
    {
        return array_keys($this->sections);
    }

    public function get_current_section()
    {
        static $result;

        if ($result === null)
        {
            $sections = $this->get_sections();

            if (isset($_GET['section']) && in_array($_GET['section'], $sections))
            {
                $result = sanitize_text_field($_GET['section']);
            }
            else
            {
                $result = reset($sections);
            }
        }

        return $result;
    }

    /**
     * @param $name
     * @return SimpleXMLElement[] Allow access to protected XML attributes
     */
    public function __get($name)
    {
        return $this->xml->$name;
    }
}