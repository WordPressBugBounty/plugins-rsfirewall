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

class RSFirewall_Model_Diff extends RSFirewall_Model
{
    /**
     * This is the URL for the github WP repository, from where we download the files
     */
    const RAW_URL = 'https://raw.githubusercontent.com/WordPress/WordPress/%s/%s';

    /**
     * @return bool|string
     * @since 1.0.0
     */
    public function get_hash() {
        if ( isset( $_POST['hid'] ) ) {
            return esc_html( $_POST['hid'] );
        }

        return false;
    }

    /**
     * @return bool|string
     * @since 1.0.0
     */
    public function get_file() {
        if ( isset( $_POST['file'] ) ) {
            $file = sanitize_text_field($_POST['file']);
            $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_\.-]+[A-Za-z0-9_\.-]*)*$/';
            if (preg_match( $pattern, (string) $file, $matches ))
			{
				$result = (string) $matches[0];

				return $result;
			}
        }

        return false;
    }

    /**
     * @return string
     * @since 1.0.0
     */
    public function get_local_filename() {
        return RSFIREWALL_SITE . '/' . $this->get_file();
    }

    /**
     * @return string
     * @since 1.0.0
     */
    protected function get_wp_version() {
        global $wp_version;

        return $wp_version;
    }

    /**
     * @return string
     * @throws Exception
     * @since 1.0.0
     */
    public function get_local_file() {
        $path = $this->get_local_filename();

        if ( ! file_exists( $path ) ) {
            throw new Exception( sprintf( esc_html__( 'Couldn\'t find %s .', 'rsfirewall' ), $path ) );
        }

        if ( ! is_readable( $path ) ) {
            throw new Exception( sprintf( esc_html__( '%s is not readable.', 'rsfirewall' ), $path ) );
        }

        if ( ! is_file( $path ) ) {
            throw new Exception( sprintf( esc_html__( '%s is not a file.', 'rsfirewall' ), $path ) );
        }

        return file_get_contents( $path );
    }

    /**
     * @return string
     * @since 1.0.0
     */
    public function get_remote_filename() {
        return sprintf( self::RAW_URL, $this->get_wp_version(), $this->get_file() );
    }

    /**
     * @return mixed
     * @throws Exception
     * @since 1.0.0
     */
    public function get_remote_file() {
        $url = $this->get_remote_filename();

        // Try to connect
        $response = wp_remote_get( $url );

        // Error in response code
        if ( $response['response']['code'] != 200 ) {
            throw new Exception( sprintf( esc_html__( 'RSFirewall! could not connect to the GitHub server: %s - %s.', 'rsfirewall' ), $response['response']['code'], $response['response']['message'] ) );
        }

        return $response['body'];
    }

    /**
     * @param $file
     * @return array()
     * @since 1.0.0
     */
    public function download_original_file( $file ) {
        $return = array(
            'status' => false,
            'files'  => array(
                'localFile' => $file
            )
        );

        if (!$file) {
            $return['message'] = esc_html__('There is no file to download!', 'rsfirewall');
            return $return;
        }

        $return['files']['remoteFile'] = sprintf( self::RAW_URL, $this->get_wp_version(), $return['files']['localFile'] );

        try {
            $response = wp_remote_get( $return['files']['remoteFile'] );
            // Error in response code
            if ( $response['response']['code'] != 200 ) {
                throw new Exception( sprintf( wp_kses_post(__( 'RSFirewall! could not connect to the GitHub server. Response code: %s; Message: %s', 'rsfirewall' )), $response['response']['code'], $response['response']['message'] ) );
            }

            WP_Filesystem();
            global $wp_filesystem, $wpdb, $wp_version;

            // check if the file exists
            $is_missing = false;
            if (!file_exists(RSFIREWALL_SITE . '/' . $return['files']['localFile'])) {
                $is_missing = true;
            }

            // Rewrite the localfile with the remote file
            if ( ! $wp_filesystem->put_contents( RSFIREWALL_SITE . '/' . $return['files']['localFile'], $response['body'], FS_CHMOD_FILE ) ) {
                throw new Exception( esc_html__( 'RSFirewall! could not overwrite the local file.', 'rsfirewall' ) );
            }

            $table  = $wpdb->prefix . 'rsfirewall_hashes';

            $id = $wpdb->get_var("SELECT `id` FROM $table  WHERE `file`='".$return['files']['localFile']."' AND (`type`='ignore' OR `type`='".$wp_version."') LIMIT 1");

            if (!is_null($id)) {
                $wpdb->update(
                    $table,
                    array(
                        'hash' => md5_file(RSFIREWALL_SITE . '/' . $return['files']['localFile']),
                        'date' => current_time('mysql'),
                    ),
                    array(
                        'id' => $id,
                    )
                );
            }


            $return['status']  = true;
            $return['message'] = (!$is_missing ? esc_html__( 'File overwritten succesfully.', 'rsfirewall' ) : esc_html__( 'File added succesfully.', 'rsfirewall' ));

        } catch ( Exception $e ) {
            $return['message'] = $e->getMessage();
        }

        return $return ;
    }
}