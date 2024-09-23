<?php
/**
 * @package       RSFirewall!
 * @copyright (C) 2009-2014 www.rsjoomla.com
 * @license       GPL, http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once dirname( __FILE__ ) . '/protocols/base.php';
require_once dirname( __FILE__ ) . '/protocols/interface.php';
require_once dirname( __FILE__ ) . '/protocols/v4.php';
require_once dirname( __FILE__ ) . '/protocols/v6.php';

/**
 * Class RSFirewall_IP
 */
class RSFirewall_IP {
	// Holds the class that's used to perform operations on the current IP.
	protected $protocol;

	// Holds the version of the protocol.
	public $version;

	/**
	 * RSFirewall_IP constructor.
	 *
	 * @param $ip
	 */
	public function __construct( $ip ) {
		// Determine protocol
		$this->protocol = $this->get_protocol( $ip );
	}

	// Determines protocol version to use.
	/**
	 * @param $ip
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function get_protocol( $ip ) {
		$protocols = array( 4, 6 );

		foreach ( $protocols as $version ) {
			$class = 'RSFirewall_IPv' . $version;
			if ( call_user_func( array( $class, 'test' ), $ip ) ) {
				$this->version = $version;
				return new $class( $ip );
			}
		}

		throw new Exception( sprintf( esc_html__( 'Could not determine protocol version for IP address \'%s\'. Please make sure that the IP address is typed correctly.', 'rsfirewall' ), $ip ) );
	}

	// Allows accessing otherwise protected variables.
	/**
	 * @param $var
	 *
	 * @return null
	 */
	public function __get( $var ) {
		switch ( $var ) {
			case 'protocol':
			case 'version':
				return $this->{$var};
				break;

			default:
				return null;
				break;
		}
	}

	// Allows accessing methods from protocol
	/**
	 * @param $name
	 * @param $args
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call( $name, $args ) {
		$callback = array( $this->protocol, $name );
		if ( is_callable( $callback ) ) {
			return call_user_func_array( $callback, $args );
		}

		throw new Exception( sprintf( esc_html__( 'The current protocol method is not supported', 'rsfirewall' ), $name, get_class( $this->protocol ) ) );
	}

	// Determines if current IP is in specified range
	/**
	 * @param $range
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function match( $range ) {
		if ( strpos( $range, '-' ) !== false ) {
			// We have an IP range (eg. 192.168.1.1 - 192.168.1.255)

			// Get starting and ending IPs
			@list( $from, $to ) = explode( '-', $range, 2 );

			// Clean them up a bit
			$from = trim( $from );
			$to   = trim( $to );

			// No starting IP?
			if ( empty( $from ) || ! strlen( $from ) ) {
				throw new Exception( esc_html__( 'No starting IP supplied.', 'rsfirewall' ) );
			}

			// No ending IP?
			if ( empty( $to ) || ! strlen( $to ) ) {
				throw new Exception( esc_html__( 'No ending IP supplied', 'rsfirewall' ) );
			}

			// Check if protocol versions match.
			$fromIP = new RSFirewall_IP( $from );
			if ( $fromIP->version != $this->version ) {
				throw new Exception( sprintf( esc_html__( 'The starting IP supplied is not an IPv%d address.', 'rsfirewall' ), $this->version ) );
			}

			$toIP = new RSFirewall_IP( $to );
			if ( $toIP->version != $this->version ) {
				throw new Exception( sprintf( esc_html__( 'The ending IP supplied is not an IPv%d address.', 'rsfirewall' ), $this->version ) );
			}

			$ip   = $this->to_comparable();
			$from = $fromIP->to_comparable();
			$to   = $toIP->to_comparable();

			return $ip >= $from && $ip <= $to;
		} elseif ( strpos( $range, '*' ) !== false ) {
			// We have a wildcard notation (eg. 192.168.1.*)
			if ( $this->version == 4 ) {
				// Wildcard notation only works on IPv4
				$haystack = explode( '.', $range, 4 );
				$needle   = explode( '.', $this->to_address(), 4 );

				foreach ( $haystack as $i => $fragment ) {
					if ( $fragment != '*' && $fragment != $needle[ $i ] ) {
						return false;
					}
				}

				return true;
			} elseif ($this->version == 6) {
				$range = preg_quote($range);
				$range = str_replace('\*', '(.*?)', $range);
				if (preg_match('/' . $range . '/', $this->to_address()))
				{
					return true;
				}
			}

			return false;
		} elseif ( strpos( $range, '/' ) !== false ) {
			// We have a CIDR notation (eg. 192.168.1.0/24)

			list( $network, $mask ) = explode( '/', $range, 2 );

			// Clean them up a bit
			$network = trim( $network );
			$mask    = trim( $mask );

			// Check if protocol versions match.
			$networkIP = new RSFirewall_IP( $network );
			if ( $networkIP->version != $this->version ) {
				throw new Exception( sprintf( esc_html__( 'The network IP supplied is not an IPv%d address.', 'rsfirewall' ), $this->version ) );
			}

			// Check if mask bits match on both addresses.
			return $this->apply_mask( $mask ) === $networkIP->apply_mask( $mask );
		}

		// None of the above - single IP mode.
		return $this->to_address() === $range;
	}

	// the old class
	/**
	 * @param bool $check_for_proxy
	 *
	 * @return mixed|null|string
	 */
	public static function get( $check_for_proxy = true ) {
		static $ip;
		if ( ! $ip ) {

			$ip = (string) $_SERVER['REMOTE_ADDR'];

			if ( $check_for_proxy ) {
				// Proxy headers
                $headers = RSFirewall_Config::get('ip_proxy_headers');

				// IPv4 private addresses
				$ipv4ranges = array(
					'10.0.0.0/8',        // 10.0.0.0 - 10.255.255.255
					'172.16.0.0/12',    // 172.16.0.0 - 172.31.255.255
					'192.168.0.0/16'    // 192.168.0.0 - 192.168.255.255
				);

				if ( $headers ) {
					foreach ( $headers as $header ) {
						if ( ! strlen( $header ) ) {
							continue;
						}
						$proxy = null;

						if ( ! empty( $_SERVER[ $header ] ) ) {
							$proxy = (string) $_SERVER[ $header ];
						}

						if ( $proxy ) {
							// let's see if there are multiple IPs
							if ( strpos( $proxy, ',' ) !== false ) {
								$tmp = explode( ', ', $proxy );
								// grab the first IP
								$proxy = reset( $tmp );
								// no longer need this
								unset( $tmp );
							}

							try {
								$class = new RSFirewall_IP( $proxy );

								// Must not grab private IPv4 addresses.
								if ( $class->version == 4 ) {
									foreach ( $ipv4ranges as $range ) {
										if ( $class->match( $range ) ) {
											continue 2;
										}
									}
								}
							} catch ( Exception $e ) {
								// IP malformed, continue to next proxy header.
								continue;
							}

							$ip = $proxy;
							break;
						}
					}
				}
			}
		}

		return $ip;
	}
}