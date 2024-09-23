<?php
/**
* @package RSFirewall!
* @copyright (C) 2009-2014 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

class RSFirewall_IP_Base
{
	// Hold our IP address in human readable format.
	protected $ip;
	
	// Constructor
	/**
	 * RSFirewall_IP_Base constructor.
	 *
	 * @param $ip
	 */
	public function __construct($ip) {
		// Assign provided IP for class access.
		$this->ip = $ip;
	}
	
	// Returns the human readable format of the IP address.
	// @return string
	public function to_address() {
		return $this->ip;
	}
	
	// Returns the binary representation of the IP address.
	// @return string
	/**
	 * @return string
	 */
	public function to_binary() {
		$unpacked 	= str_split($this->to_unpacked());
		$bin 		= '';
		foreach ($unpacked as $char) {
			$bin .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
		}
		
		return $bin;
	}
	
	// Returns the packed representation of IP (in_addr).
	// @return string
	/**
	 * @return string
	 * @throws Exception
	 */
	public function to_packed() {
		if (function_exists('inet_pton')) {
			$packed = @inet_pton($this->ip);
		} else {
			$packed = $this->inet_pton($this->ip);
		}
		if ($packed === false) {
			throw new Exception(sprintf(esc_html__('Could not transform IP address \'%s\' to packed representation.', 'rsfirewall'), $this->ip));
		}
		
		return $packed;
	}
	
	// Applies a mask to current IP to get the bits.
	// @return string
	/**
	 * @param $mask
	 *
	 * @return string
	 */
	public function apply_mask($mask) {
		return substr($this->to_binary(), 0, $this->clean_mask($mask));
	}
}