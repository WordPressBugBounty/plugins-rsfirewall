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
class RSFirewall_Autoupdate
{
    /**
     * The plugin current version
     * @var string
     */
    private $current_version;
    /**
     * RSJoomla! Update Path
     * @var string
     */

    private $update_path = 'https://www.rsjoomla.com/updates/wp_rsfirewall/rsfirewall.php';

    /**
     * Plugin File Root (plugin_directory/plugin_file.php)
     * @var string
     */
    private $plugin_file_root;

    /**
     * Plugin name (plugin_file)
     * @var string
     */
    private $slug;

    /**
     * Update Code
     * @var string
     */
    private $update_code;

    /**
     * Update Key, specific to each plugin
     * @var string
     */
    private $key;
	
	/**
     * Holds if the current version is a lite version or not
     * @var bool
     */
    private $is_lite = false;

    public function __construct($args = array(), $enable_filters = true)
    {
        // Set the Plugin Slug
        $this->slug = isset($args['slug']) ? $args['slug'] : null;
        if (!is_null($this->slug)) {
            $this->plugin_file_root = plugin_basename(RSFIREWALL_BASE) . '/' . $args['slug'] . '.php';
        }

        // Set the current version
        $this->current_version = isset($args['current_version']) ? $args['current_version'] : null;
		
		// Check if it's the lite version
		if(isset($args['is_lite']) && $args['is_lite']) {
            $this->is_lite = true;
        }

        // Set the Update Code and Key
        $this->update_code = isset($args['update_code']) ? $args['update_code'] : null;;
        $this->key         = isset($args['key']) ? $args['key'] : null;;

        if ($enable_filters) {
            // filter for checking if a new version is available
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            // Define the alternative response for information checking
            add_filter('plugins_api', array($this, 'check_info'), 10, 3);
        }
    }

    /**
     * Add our self-hosted autoupdate plugin to the filter transient
     *
     * @param $transient
     * @return object $ transient
     */
    public function check_update( $transient )
    {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Get the remote version
        $remote_version = $this->get_url('version', true);

        // If a newer version is available, add the update
        if ( $remote_version && ( $this->is_lite || ( !$this->is_lite && version_compare( $this->current_version, $remote_version->new_version, '<' ) ) ) ) {
            // Add / Overwrite the slug
            $remote_version->slug = $this->slug;

            // Add / Overwrite the plugin
            $remote_version->plugin = $this->plugin_file_root;

            // Set the new transient
            $transient->response[$this->plugin_file_root] =  $remote_version;
        }

        return $transient;
    }

    /**
     * Get the description for the filter
     *
     * @param array $action
     * @param object $args
     * @return bool|object
     */
    public function check_info($obj, $action, $args)
    {
        if (!isset($args->slug) || $args->slug !== $this->slug) {
            return $obj;
        }

        // Get our info only for these 2 actions
        switch($action) {
            case 'query_plugins':
            case 'plugin_information':
                return $this->get_url('info');
            break;
        }

        return $obj;
    }

    /**
     * Return the contents of the url requested
     *
     * @param string $action
     * @param boolean $credentials
     *
     * @return string|boolean
     */
    public function get_url($action, $credentials = false) {
        $params = array(
            'body' => array(
                'action'       => $action,
            )
        );

        // Add the credentials if needed
        if ($credentials && !empty($this->update_code)) {
            $site_url = get_site_url();
            $site_url = RSFirewall_Helper::parse_url($site_url);

            $params['body']['hash']     = md5($this->update_code.$this->key);
            $params['body']['domain']   = $site_url['host'];
            $params['body']['code']     = $this->update_code;
        }

        // Make the POST request
        $request = wp_remote_post($this->update_path, $params );

        // Check if response is valid
        if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
            return @unserialize( $request['body'] );
        }

        return false;
    }
}