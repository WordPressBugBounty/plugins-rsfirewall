<?php
/**
* @package RSFirewall!
* @copyright (C) 2009-2014 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

class RSFirewall_IPv6 extends RSFirewall_IP_Base implements RSFirewall_IP_Interface
{
	// Tests if supplied IP address is IPv6.
	/**
	 * @param $ip
	 *
	 * @return bool|mixed
	 */
	public static function test($ip) {
		if (defined('FILTER_VALIDATE_IP') && defined('FILTER_FLAG_IPV6')) {
			return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
		} else {
			return (strpos($ip, ':') !== false);
		}
	}
	
	// Provides an unpacking method for IPv6
	public function to_unpacked() {
		$unpacked = unpack('A16', $this->toPacked());
		if (!isset($unpacked[1])) {
			throw new Exception(sprintf(esc_html__('Could not unpack IP address \'%s\'.','rsfirewall'), $this->ip));
		}
		
		return $unpacked[1];
	}
	
	// Provides a variable that can be used with comparison operators.
	// IPv6 uses in_addr for string comparison.
	/**
	 * @return string
	 * @throws Exception
	 */
	public function to_comparable() {
		return $this->to_packed();
	}
	
	// Makes sure mask is valid.
	/**
	 * @param $mask
	 *
	 * @return int
	 * @throws Exception
	 */
	public function clean_mask($mask) {
		$mask = (int) $mask;
		if ($mask > 128 || $mask < 1) {
			throw new Exception(sprintf(esc_html__('Network mask supplied \'%s\' is out of range \'%s\'.', 'rsfirewall'), $mask, '1-32'));
		}
		
		return $mask;
	}

	/**
	 * @param $address
	 *
	 * @return array|mixed
	 */
	protected function inet_pton($address) {
		// Create an array with the delimited substrings
		$r = explode(':', $address);

		// Count the number of items
		$rcount = count($r);

		// If we have empty items, fetch the position of the first one
		if (($doub = array_search('', $r, 1)) !== false) {

			// We fill a $length variable with this rule:
			// - If it's the first or last item ---> 2
			// - Otherwhise                     ---> 1
			$length = (!$doub || $doub == $rcount - 1 ? 2 : 1);

			// Remove a portion of the array and replace it with something else
			array_splice($r,

				// We skip items before the empty one
				$doub,

				// We remove one or two items
				$length,

				// We replace each removed value with zeros
				array_fill(0, 8 + $length - $rcount, 0)

			);
		}

		// We convert each item from hexadecimal to decimal
		$r = array_map('hexdec', $r);
		// We add 'n*' at the beginning of the array (just a trick to use pack on all the items)
		array_unshift($r, 'n*');
		// We pack all the items as unsigned shorts (always 16 bit, big endian byte order)
		$r = call_user_func_array('pack', $r);
		// Return the resulting string
		return $r;
	}
}