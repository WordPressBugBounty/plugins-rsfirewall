<?php
/**
* @package RSFirewall!
* @copyright (C) 2009-2014 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

class RSFirewall_IPv4 extends RSFirewall_IP_Base implements RSFirewall_IP_Interface
{
	// Tests if supplied IP address is IPv4.
	/**
	 * @param $ip
	 *
	 * @return bool|mixed
	 */
	public static function test($ip) {
		if (defined('FILTER_VALIDATE_IP') && defined('FILTER_FLAG_IPV4')) {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		} else {
			return (strpos($ip, '.') !== false && strpos($ip, ':') === false);
		}
	}
	
	// Provides an unpacking method for IPv4
	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function to_unpacked() {
		$unpacked = unpack('A4', $this->to_packed());
		if (!isset($unpacked[1])) {
			throw new Exception(sprintf(esc_html__('Could not unpack IP address \'%s\'.','rsfirewall'), $this->ip));
		}
		
		return $unpacked[1];
	}
	
	// Provides a variable that can be used with comparison operators.
	// IPv4 uses float.
	/**
	 * @return float
	 * @throws Exception
	 */
	public function to_comparable() {
		return $this->to_long();
	}
	
	// Provides numeric representation (float) of IPv4 address.
	/**
	 * @return float
	 * @throws Exception
	 */
	public function to_long() {
		$long = ip2long($this->ip);
		if ($long === false) {
			throw new Exception(sprintf(esc_html__('Could not convert IP address \'%s\' to numeric (long) format.', 'rsfirewall'), $this->ip));
		}
		
		return (float) sprintf('%u', $long);
	}
	
	// Makes sure mask is valid.
	/**
	 * @param $mask
	 *
	 * @return int
	 * @throws Exception
	 */
	public function clean_mask($mask) {
		if (strpos($mask, '.') !== false) {
			// We have a /255.255.255.0 notation
			$maskIP = new RSFirewall_IP($mask);
			$baseIP = new RSFirewall_IP('255.255.255.255');
			
            $long = $maskIP->to_long();
            $base = $baseIP->to_long();
            $mask = 32 - log(($long ^ $base) + 1, 2);
		}
		
		$mask = (int) $mask;
		if ($mask > 32 || $mask < 1) {
			throw new Exception(sprintf(esc_html__('Network mask supplied \'%s\' is out of range \'%s\'.', 'rsfirewall'), $mask, '1-32'));
		}
		
		return $mask;
	}

	/**
	 * @param $address
	 *
	 * @return bool|string
	 */
	protected function inet_pton($address) {
		$parts = explode('.', $address);
		if (count($parts) != 4) {
			return false;
		}
		
		return chr((int) $parts[0]).chr((int) $parts[1]).chr((int) $parts[2]).chr((int) $parts[3]);
	}
}