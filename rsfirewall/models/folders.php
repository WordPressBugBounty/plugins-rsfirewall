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

class RSFirewall_Model_Folders extends RSFirewall_Model
{
    public $content = array();
    public $path;
    public $DS = DIRECTORY_SEPARATOR;
    public $previous;
    public $limit_to;

    public function __construct(){
        $this->path = (isset($_POST['path']) && is_dir($_POST['path'])) ? sanitize_text_field($_POST['path']) : ABSPATH;
        $this->limit_to = isset($_POST['limit_to'])  ? urldecode($_POST['limit_to']) : array() ;

        // Check the limits
        if (!empty($this->limit_to)) {
            foreach ($this->limit_to as $i => $limit) {
                $this->limit_to[$i] = sanitize_text_field($limit);
            }

            $this->limit_to  = explode(',', $this->limit_to);
            array_walk($this->limit_to, 'trim');
        }

        // Clean the path - we must use only DIRECTORY_SEPARATOR
        try {
            $this->path = $this->clean($this->path);
        } catch(Exception $e) {
            echo $e->getMessage();
        }

        if (substr($this->path, -1) == $this->DS) {
            $this->path = substr($this->path, 0, -1);
        }

        $this->previous = $this->get_previous();

        parent::__construct();
    }

    /**
     * Gets the current selected path files and folders
     */
    public function open_file_manager(){
        $check_model = RSFirewall_Helper::call_user_func_pro(array('RSFirewall_Model_Check', 'get_instance'));

        $folders = $check_model->get_folders($this->path, false, true, false, true);
        $files = $check_model->get_files($this->path, false, true, false, array(), true);

        if (!is_array($folders) || !is_array($files)) {
           return;
        }

        $this->content =  array_merge($folders,$files);

        $this->content = $this->get_content_data($this->content, $this->path);
    }

    /**
     * Parse the content of the path for additional info
     *
     * @param $content - array() - the array of files and folders
     *
     * @return array with the info for the files and folders for the current path
     */
    protected function get_content_data($content) {
        $dataContent = array();
        foreach ($content as $file) {
            $newPath = realpath($this->path.$this->DS.$file);
            $perms   = fileperms($newPath);

            $dataContent[$file]['octal'] = substr(sprintf('%o', $perms), -4);
            $dataContent[$file]['full']  = $this->get_permissions($newPath);
            if (is_file($newPath)) {
                $dataContent[$file]['is_file']   = true;
                $dataContent[$file]['filesize']   = $this->nice_filesize(filesize($newPath), 2);
            } else {
                $dataContent[$file]['is_file']   = false;
            }
        }
        return $dataContent;
    }

    /**
     * Outputs the file size in a human readable format
     *
     * @param $bytes - the file size in bytes
     * @param $decimals - how many decimals will output
     * @return string - human readable file size format
     */

    protected function nice_filesize($bytes, $decimals = 2) {
        $scale = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .' '.$scale[$factor];
    }


    /**
     * Get the permissions of the file/folder for the specific path.
     *
     * @param   $path -  The path of a file/folder.
     *
     * @return  string  Filesystem permissions.
     */
    protected function get_permissions($path) {
        $mode = @ decoct(@ fileperms($path) & 0777);

        if (strlen($mode) < 3)
        {
            return '---------';
        }

        $parsed_mode = '';

        for ($i = 0; $i < 3; $i++)
        {
            // Read
            $parsed_mode .= ($mode[$i] & 04) ? 'r' : '-';

            // Write
            $parsed_mode .= ($mode[$i] & 02) ? 'w' : '-';

            // Execute
            $parsed_mode .= ($mode[$i] & 01) ? 'x' : '-';
        }

        return $parsed_mode;
    }

    /**
     * Get the path elements and the individual paths for each one
     *
     * @return  array - the elements of the current path.
     */

    public function get_elements() {
        $elements 	= explode($this->DS, $this->path);
        $navigation_path = '';
        foreach ($elements as $i => $element) {
            if (empty($element)) {
                unset($elements[$i]);
                continue;
            }
            $navigation_path .= $element;
            $newelement = new stdClass();
            $newelement->name = $element;
            $newelement->fullpath = $navigation_path;
            $elements[$i] = (object)array(
                'name' => $element,
                'fullpath' => $navigation_path
            );
            $navigation_path .= $this->DS;
        }

        return $elements;
    }

    /**
     * Cleans the given path, replacing all the separator with the DIRECTORY_SEPARATOR
     *
     * @param $path - string - the path that needs to be cleaned
     *
     * @throws exception if the path is not a string
     *
     * @return  string - the cleaned path
     */
    protected function clean($path){
        if (!is_string($path) && !empty($path))
        {
            throw new Exception(__('The path is not a string', 'rsfirewall'));
        }

        $path = trim($path);

        if (($this->DS == '\\') && substr($path, 0, 2) == '\\\\')
        {
            $path = "\\" . preg_replace('#[/\\\\]+#', $this->DS, $path);
        }
        else
        {
            $path = preg_replace('#[/\\\\]+#', $this->DS, $path);
        }

        return $path;
    }

    /**
     * Get the previous path
     *
     * @return  string - the previous path
     */
    protected function get_previous() {
        $path = explode($this->DS, $this->path);
        array_pop($path);

        return implode($this->DS, $path);
    }

}