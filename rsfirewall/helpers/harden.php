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
class RSFirewall_Helper_Harden {
    /**
     * Function to retrieve the rules for blocking PHP files
     * Supports Apache 2.2 / 2.4
     *
     * @return string - access control rules.
     */
    private static function rules()
    {
        $rules =  array(
            '<FilesMatch "\.(?i:php)$">',
            '  <IfModule !mod_authz_core.c>',
            '    Order allow,deny',
            '    Deny from all',
            '  </IfModule>',
            '  <IfModule mod_authz_core.c>',
            '    Require all denied',
            '  </IfModule>',
            '</FilesMatch>',
        );

        return implode("\n", $rules);
    }

    /**
     * Function to retrieve the rules for whitelisting PHP files in blocked folders
     * Supports Apache 2.2 / 2.4
     *
     * @return string - access control rules.
     */
    private static function whitelist_rule($file = '')
    {
        // if a user eneters a full path, we do not need it, just the last part with the actual PHP file
        if (strpos($file, '/') !== false) {
            $parts =  explode('/', $file);
            $file = array_pop($parts);
        }

        $file = str_replace(array('<', '>'), '', $file);

        return sprintf(
            "<Files %s>\n"
            . "  <IfModule !mod_authz_core.c>\n"
            . "    Allow from all\n"
            . "  </IfModule>\n"
            . "  <IfModule mod_authz_core.c>\n"
            . "    Require all granted\n"
            . "  </IfModule>\n"
            . "</Files>\n",
            $file
        );
    }

    /**
     * Build the proper .htaccess path
     *
     * @return string - path to the .htaccess file
     */
    protected static function htaccess_path($path) {
        return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.htaccess';
    }

    /**
     * Check whether the site is running over an Apache Server (for making use of .htaccess file).
     *
     * @return bool - true if is Apache, false if not
     */
    public static function is_apache_server() {
        $isNginx     = (stripos(@$_SERVER['SERVER_SOFTWARE'], 'nginx') !== false);
        $isIISServer = (stripos(@$_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);

        return (!$isNginx && !$isIISServer);
    }


    /**
     * Function that actually hardens the directory by adding the access control rules
     *
     * @param  string $path - the path of the directory that should be hardened
     * @return bool - true if is hardened, false if not
     * @throws exception when the path is not available / writable - or the handler could not open properly the path
     */
    public static function harden_directory($path) {
        if (!is_dir($path) || !is_writable($path)) {
           throw new Exception(esc_html__('Directory is not usable', 'rsfirewall'));
        }

        $handler = false;
        // .htaccess path
        $htaccess_file = self::htaccess_path($path);

        if (file_exists($htaccess_file)) {
            // no need to continue because is already hardened
            if (self::is_hardened($path)) {
                return true;
            }
            $handler = @fopen($htaccess_file, 'a');
        } else {
            $handler = @fopen($htaccess_file, 'w');
        }

        if (!$handler) {
            throw new Exception(sprintf(wp_kses_post(__( 'Something is wrong with the path: <em>%s</em>', 'rsfirewall' ), $htaccess_file)));
        }

        $rules = self::rules();
        $added = @fwrite($handler, "\n" . $rules . "\n");

        @fclose($handler);

        return ($added !== false);
    }

    /**
     * Function to unharden a directory
     *
     * @param  string $path - the path of the directory that should be hardened
     * @return bool - true if is hardened, false if not
     * @throws exception when the path is not available / writable - or the handler could not open properly the path
     */
    public static function unharden_directory($path)
    {
        $htaccess_file = self::htaccess_path($path);

        // if the .htaccess file doesn't exist no need do unharden anymore
        if (!file_exists($htaccess_file)) {
            return true;
        }

        if (!self::is_hardened($path)) {
            throw new Exception(esc_html__('Directory is not hardened', 'rsfirewall'));
        }

        $content =  (string) (is_readable($htaccess_file) ? file_get_contents($htaccess_file) : '');
        $rules = self::rules();

        $content = str_replace($rules, '', $content);
        $removed = file_put_contents($htaccess_file, $content);
        $trimmed = trim($content);

        if (!filesize($htaccess_file) || empty($trimmed)) {
            unlink($htaccess_file);
        }

        return ($removed !== false);
    }


    /**
     * checks if the directory is already hardened
     *
     * @param  string $path - the path where the .htaccess file is located
     * @return bool - true if the rules are present / false if not.
     */
    public static function is_hardened($path = '')
    {
        if (!is_dir($path)) {
            return false;
        }

        $htaccess_file = self::htaccess_path($path);
        $content =  (string) (is_readable($htaccess_file) ? file_get_contents($htaccess_file) : '');
        $rules = self::rules();

        return (strpos($content, $rules) !== false);
    }

    /**
     * retrieve the whitelisted files from all the possible hardened paths
     *
     * @param  bool $count - if true then will return the number of files that are whitelisted, else the list of details for the files
     * @return int / array - numbers of files found / array containing the detail list of the files
     */
    public static function whitelisted_php_files($count = false) {
        $allowed_folders = array(WP_CONTENT_DIR . '/uploads', WP_CONTENT_DIR, ABSPATH . '/wp-includes');

        $files_found = 0;
        $files_list = array();
        // Read the .htaccess files located in the hardened folders, if any
        foreach ($allowed_folders as $folder) {
            $files = self::get_whitelisted_from_folder($folder);

            if (is_array($files) && !empty($files)) {
                $files_found += count($files);

                if (!$count) {
                    foreach ($files as $file) {
                        $data = new stdClass();

                        $data->folder  = str_replace(ABSPATH, '', $folder);
                        $data->pattern = sprintf('%s/.*/%s', $data->folder, $file);
                        $data->file    = $file;

                        $files_list[] = $data;
                    }
                }
            }
        }

        return $count ? $files_found : $files_list;
    }

    /**
     * retrieve the whitelisted files from a specific hardened path
     *
     * @param  string $folder_path - the path where we will check for whitelisted PHP files
     * @return bool / array - false if none found / array containing the detail list of the files
     */
    protected static function get_whitelisted_from_folder($folder_path)
    {
        $htaccess_file = self::htaccess_path($folder_path);
        if (file_exists($htaccess_file)) {
            $content =  (string) (is_readable($htaccess_file) ? file_get_contents($htaccess_file) : '');
            @preg_match_all('/<Files (\S+)>/', $content, $matches);

            return $matches[1];
        }

        return false;
    }

    /**
     * removes a PHP file from a specific hardened folder that is whitelisted
     *
     * @param  string $file - the PHP filename
     * @param  string $folder_path - the specific folder where the .htaccess is located
     * @throws exception when something is wrong (wrong file permission, etc.)
     * @return bool - true for successfully rewriting the .htaccess file
     */
    public static function remove_file_from_whitelist($file = '', $folder_path = '')
    {
        $htaccess_file = self::htaccess_path($folder_path);
        $content =  (string) (is_readable($htaccess_file) ? file_get_contents($htaccess_file) : '');

        if (!$content || !is_writable($htaccess_file)) {
           throw new Exception(sprintf(__('(No permissions) - Cannot remove PHP file from: %s', 'rsfirewall'), $htaccess_file));
        }

        $rules = self::whitelist_rule($file);
        $content = str_replace($rules, '', $content);
        $content = rtrim($content) . "\n";

        return (bool) file_put_contents($htaccess_file, $content);
    }

    /**
     * adds a PHP file to the .htaccess file (whitelisting it) of a specific hardened folder
     *
     * @param  string $file - the PHP filename
     * @param  string $folder_path - the specific folder where the .htaccess is located
     * @throws exception when something is wrong (wrong file permission, etc.)
     * @return bool - true for successfully rewriting the .htaccess file
     */
    public static function add_file_to_whitelist($file, $folder_path) {
        $htaccess_file = self::htaccess_path($folder_path);

        if (!file_exists($htaccess_file)) {
            throw new Exception('The .htaccess file does not exist in this folder!');
        }

        if (!is_writable($htaccess_file)) {
            throw new Exception('The .htaccess file is not writable!');
        }

        return (bool) file_put_contents($htaccess_file, "\n" . self::whitelist_rule($file), FILE_APPEND);
    }
}