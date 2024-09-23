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

class RSFirewall_Helper_URL
{
	protected static $instances = array();
	/**
	 * @var
	 */
	protected $uri;
	/**
	 * @var
	 */
	protected $scheme;
	/**
	 * @var
	 */
	protected $user;
	/**
	 * @var
	 */
	protected $pass;
	/**
	 * @var
	 */
	protected $host;
	/**
	 * @var
	 */
	protected $port;
	/**
	 * @var
	 */
	protected $path;
	/**
	 * @var
	 */
	protected $query;
	/**
	 * @var
	 */
	protected $fragment;

	/**
	 * @return mixed
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * @param mixed $uri
	 */
	public function setUri( $uri ) {
		$this->uri = $uri;
	}

	/**
	 * @return mixed
	 */
	public function getScheme() {
		return $this->scheme;
	}

	/**
	 * @param mixed $scheme
	 */
	public function setScheme( $scheme ) {
		$this->scheme = $scheme;
	}

	/**
	 * @return mixed
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @param mixed $user
	 */
	public function setUser( $user ) {
		$this->user = $user;
	}

	/**
	 * @return mixed
	 */
	public function getPass() {
		return $this->pass;
	}

	/**
	 * @param mixed $pass
	 */
	public function setPass( $pass ) {
		$this->pass = $pass;
	}

	/**
	 * @return mixed
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @param mixed $host
	 */
	public function setHost( $host ) {
		$this->host = $host;
	}

	/**
	 * @return mixed
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * @param mixed $port
	 */
	public function setPort( $port ) {
		$this->port = $port;
	}

	/**
	 * @return mixed
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param mixed $path
	 */
	public function setPath( $path ) {
		$this->path = $path;
	}

	/**
	 * @return mixed
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * @param mixed $query
	 */
	public function setQuery( $query ) {
		$this->query = $query;
	}

	/**
	 * @return mixed
	 */
	public function getFragment() {
		return $this->fragment;
	}

	/**
	 * @param mixed $fragment
	 */
	public function setFragment( $fragment ) {
		$this->fragment = $fragment;
	}

	/**
	 * RSJoomla_URL_Helper constructor.
	 *
	 * @param $uri
	 */
	public function __construct( $uri = 'SERVER') {
		$this->parse( $uri );
	}

	/**
	 * @param $uri
	 *
	 * @return bool
	 */
	protected function parse( $uri ) {
		// Set the original URI to fall back on
		$this->uri = $uri;

		/*
		 * Parse the URI and populate the object fields. If URI is parsed properly,
		 * set method return value to true.
		 */

		$parts = self::parse_url( $uri );

		$retval = ( $parts ) ? true : false;

		// We need to replace &amp; with & for parse_str to work right...
		if ( isset( $parts['query'] ) && strpos( $parts['query'], '&amp;' ) ) {
			$parts['query'] = str_replace( '&amp;', '&', $parts['query'] );
		}

		$this->scheme   = isset( $parts['scheme'] ) ? $parts['scheme'] : null;
		$this->user     = isset( $parts['user'] ) ? $parts['user'] : null;
		$this->pass     = isset( $parts['pass'] ) ? $parts['pass'] : null;
		$this->host     = isset( $parts['host'] ) ? $parts['host'] : null;
		$this->port     = isset( $parts['port'] ) ? $parts['port'] : null;
		$this->path     = isset( $parts['path'] ) ? $parts['path'] : null;
		$this->query    = isset( $parts['query'] ) ? $parts['query'] : null;
		$this->fragment = isset( $parts['fragment'] ) ? $parts['fragment'] : null;

		// Parse the query
		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $this->vars );
		}

		return $retval;
	}

	/**
	 * Does a UTF-8 safe version of PHP parse_url function
	 *
	 * @param   string $url URL to parse
	 *
	 * @return  mixed  Associative array or false if badly formed URL.
	 *
	 * @see     http://us3.php.net/manual/en/function.parse-url.php
	 * @since   1.0
	 */
	public static function parse_url( $url ) {
		$result = false;

		// Build arrays of values we need to decode before parsing
		$entities     = array(
			'%21',
			'%2A',
			'%27',
			'%28',
			'%29',
			'%3B',
			'%3A',
			'%40',
			'%26',
			'%3D',
			'%24',
			'%2C',
			'%2F',
			'%3F',
			'%23',
			'%5B',
			'%5D'
		);
		$replacements = array( '!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "$", ",", "/", "?", "#", "[", "]" );

		// Create encoded URL with special URL characters decoded so it can be parsed
		// All other characters will be encoded
		$encodedURL = str_replace( $entities, $replacements, urlencode( $url ) );

		// Parse the encoded URL
		$encodedParts = parse_url( $encodedURL );

		// Now, decode each value of the resulting array
		if ( $encodedParts ) {
			foreach ( $encodedParts as $key => $value ) {
				$result[ $key ] = urldecode( str_replace( $replacements, $entities, $value ) );
			}
		}

		return $result;
	}
	
}