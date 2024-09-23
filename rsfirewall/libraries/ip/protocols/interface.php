<?php
/**
* @package RSFirewall!
* @copyright (C) 2009-2014 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

interface RSFirewall_IP_Interface
{	
	// Test returns true if IP matches current protocol.
	// @return boolean
	/**
	 * @param $ip
	 *
	 * @return mixed
	 */
	public static function test($ip);
	
	// Provides an unpacking method for IP. Used by toBinary().
	// @return string
	public function to_unpacked();
	
	// Provides a variable that can be used with comparison operators.
	// @return mixed
	public function to_comparable();
	
	// Makes sure mask is clean. Returns cleaned mask as a result.
	// @return int
	/**
	 * @param $mask
	 *
	 * @return mixed
	 */
	public function clean_mask($mask);
}