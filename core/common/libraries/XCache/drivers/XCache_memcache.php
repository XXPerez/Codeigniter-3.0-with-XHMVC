<?php

/**
 * XCache MEMCACHE Caching Class
 *
 * @package		XCache
 * @subpackage	Libraries
 * @category	Memcache
 * @author		XPerez
 * @link
 */
class XCache_memcache extends XCache {

	private $_instance;

	public function __construct() {
		
	}

	/**
	 * Get
	 *
	 * Since this is the dummy class, it's always going to return FALSE.
	 *
	 * @param 	string
	 * @return 	Boolean		FALSE
	 */
	public function readCache($type, $name, $ID, $onlyCheck = FALSE) {
		$this->getInstance();
		$originalID = $ID;

		if (isset($_POST) && count($_POST) > 0)
			$ID = $ID . md5(serialize($_POST));

		self::logMessage('debug', "Reading memcache $type - $name - $ID.");

		$item_expiration = $this->getCacheItemExpiration($type, $name, $originalID);

		if (is_array($item_expiration)) {
			$item_properties = $item_expiration;
			$name .= '-' . $item_properties[0];
			$item_expiration = $item_properties[1];
		}

		if ($item_expiration == FALSE) {
			$item_expiration = $this->getCacheConfigItem('default', $type);
			if ($item_expiration == FALSE)
				return FALSE;
		}

		$cache = $this->getInstance()->get($type . '-' . $name . '-' . $ID);

		if ($cache == FALSE)
			return FALSE;

		if (function_exists('profiler_log'))
			profiler_log('CACHE', 'Memcache Read OK: ' . $type . '/' . $name . '/' . $ID);

		if ($cache && $onlyCheck)
			return TRUE;

		return unserialize($cache);
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Save
	 *
	 * @param 	string		Unique Key
	 * @param 	mixed		Data to store
	 * @param 	int			Length of time (in seconds) to cache the data
	 *
	 * @return 	boolean		TRUE, Simulating success
	 */
	public function writeCache($type, $name, $ID, $output, $depID = "") {
		$originalID = $ID;

		//if (function_exists('profiler_log')) profiler_log('CACHE','Memcache Write init : '.$type.'/'.$name.'/'.$ID);

		$item_expiration = $this->getCacheItemExpiration($type, $name, $originalID);

		if (is_array($item_expiration)) {
			$item_properties = $item_expiration;
			$name .= '-' . $item_properties[0];
			$item_expiration = $item_properties[1];
		}

		if ($item_expiration == FALSE) {
			$item_expiration = $this->getCacheConfigItem('default', $type);
			if ($item_expiration == FALSE)
				return FALSE;
		}

		$this->getInstance()->set($type . '-' . $name . '-' . $ID, serialize($output), MEMCACHE_COMPRESSED, $item_expiration);

		if (function_exists('profiler_log'))
			profiler_log('CACHE', 'Memcache Write OK: ' . $type . '/' . $name . '/' . $ID);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of the item in the cache
	 * @param 	boolean		TRUE, simulating success
	 */
	public function deleteCache($type, $name, $ID) {
		$originalID = $ID;

		if (isset($_POST) && count($_POST) > 0)
			$ID = $ID . md5(serialize($_POST));

		$this->getInstance()->delete($type . '-' . $name . '-' . $ID);
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return 	boolean		TRUE, simulating success
	 */
	public function cleanCache() {
		$this->getInstance()->flush();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param 	string		user/filehits
	 * @return 	boolean		FALSE
	 */
	public function getCacheInfo($type = NULL) {
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	boolean		FALSE
	 */
	public function getCacheMetadata($id) {
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Is this caching driver supported on the system?
	 * Of course this one is.
	 *
	 * @return TRUE;
	 */
	public function isSupported() {
		if (!extension_loaded('memcache')) {
			self::logMessage('error', 'The MEMCACHE PHP extension must be loaded to use Memcache Cache.');
			return FALSE;
		} else {
			return TRUE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * getInstance
	 * Reuse memcache class
	 */
	public function getInstance() {
		$cache_path = explode(':', $this->getCacheConfigItem('cache_memcache'));

		$host = $cache_path[0];
		$port = $cache_path[1];

		// Check exists current instance
		if (!isset($this->_instance))
			$this->_instance = NULL;

		// Create new instance or return current
		if ($this->_instance == NULL) {
			$this->_instance = new Memcache;
			$this->_instance->connect($host, $port);
		}

		return $this->_instance;
	}

}

// End Class

/* End of file Cache_memcache.php */
/* Location: ./system/libraries/Cache/drivers/Cache_dummy.php */