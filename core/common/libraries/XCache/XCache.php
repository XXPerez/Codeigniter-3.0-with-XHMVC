<?php

/**
 * XCache base class
 *
 * @package        	XCache
 * @subpackage    	BaseClass
 * @category    	Cache
 * @author        	Xavier Perez
 * @license         MIT License (MIT) : http://opensource.org/licenses/MIT
 * @version			2.1
 */
if (class_exists('XCache')) {
	return;
}

class XCache {

	protected $valid_drivers = array(
		'apc', 'file', 'memcache', 'dummy', 'mongodb', 'xcache' # ,'redis' TODO in next version
	);
	private $_adapter = 'file';
	private $_backup_driver = 'file';
	private $config;
	private $cache_type;
	private $cache_name;
	private $enabled = TRUE;
	public static $xcinstance;

	/**
	 * constructor
	 */
	public function __construct($filepath = '') {
		$this->getCacheConfigFile($filepath);
		$this->enabled = $this->getCacheConfigItem('cache_enabled');
		$this->_adapter = $this->getCacheConfigItem('cache_type');
		self::$xcinstance = $this;
	}

	/**
	 * getCacheIsAvailable
	 * 
	 * get Avaliability of cache for a complete cache page (index.php)
	 * 
	 * @param type $direct
	 * @return boolean
	 */
	private function getCacheIsAvailable($direct = FALSE) {
		if ($this->enabled == FALSE)
			return FALSE;

		if ($direct == TRUE)
			return TRUE;

		if (isset($_GET) && count($_GET) > 0 && $this->getCacheConfigItem('cache_get') === FALSE)
			return FALSE;

		if (isset($_POST) && count($_POST) > 0 && $this->getCacheConfigItem('cache_post') === FALSE)
			return FALSE;

		if (isset($_GET['nocache']))
			return FALSE;

		foreach ($_COOKIE as $key => $val) {
			if (preg_match('/' . $this->config['cache_logged_cookie'] . '/', $key, $matches)) {
				return $this->config['cache_only_not_logged_pages'];
			}
		}

		return TRUE;
	}

	/**
	 * getCacheConfigFile
	 * 
	 * get cache config file params
	 * 
	 */
	protected function getCacheConfigFile($filepath = '') {
		if (!is_array($this->config)) {
			if (defined('ENVIRONMENT') && defined('APPPATH'))
				$baseDir = array(APPPATH . "config", COMMONPATH . "config");
			elseif (defined('COMMONPATH'))
				$baseDir = array(__DIR__, COMMONPATH . "config");
			elseif ($filepath != '')
				$baseDir = array($_SERVER['DOCUMENT_ROOT'] . $filepath);
			else
				$baseDir = array(__DIR__);

			foreach ($baseDir as $path) {
				if (file_exists($path . '/xcacheconf.php')) {
					$findPath = $path . '/xcacheconf.php';
					break;
				}
			}

			if (!isset($findPath))
				throw new Exception('Error: xcacheconf.php config file not found');

			require $findPath;

			$this->config = $config;
		}
	}

	/**
	 * getCacheConfigItem
	 * 
	 * get an specific item in cache config
	 * 
	 * @param varchar $item
	 * @param varchar $index
	 * @return varchar
	 */
	public function getCacheConfigItem($item, $index = '') {
		$this->getCacheConfigFile();

		if ($index == '') {
			if (!isset($this->config[$item])) {
				return FALSE;
			}

			$pref = $this->config[$item];
		} else {
			if (isset($this->config[$index][$item])) {
			    $pref = $this->config[$index][$item];
			}
			elseif (isset($this->config[$index]['default'])) {
			    $pref = $this->config[$index]['default'];
			}
			elseif (!isset($this->config[$index])) {
				$pref = FALSE;
			}
		}

		return $pref;
	}

	/**
	 * checkCache
	 * Check if cache exists and it's valid
	 * 
	 * @param string $type Cache type
	 * @param string $name Cache module
	 * @param string $ID  Cache ID
	 * @return string 
	 */
	public function checkCache($type, $name, $ID) {
		return $this->{$this->_adapter}->readCache($type, $name, $ID, TRUE);
	}

	/**
	 * deleteCache
	 * 
	 * delete a cache item or group
	 * 
	 * @param type $type
	 * @param type $name
	 * @param type $ID
	 * @return type
	 */
	public function deleteCache($type, $name = '', $ID = '') {
		return $this->{$this->_adapter}->deleteCache($type, $name, $ID);
	}

	/**
	 * cleanCache
	 * 
	 * Clean all files or references
	 * 
	 * @return type
	 */
	public function cleanCache() {
		return $this->{$this->_adapter}->cleanCache();
	}

	/**
	 * Read cache
	 * 
	 * @param string $type Cache type
	 * @param string $name Cache module
	 * @param string $ID  Cache ID
	 * @param boolean $onlyCheck Only check if cache it's valid
	 * @return string 
	 */
	public function readCache($type, $name, $ID, $onlyCheck = FALSE) {
		if ($this->enabled == FALSE)
			return FALSE;

		$cache_enabled = $this->getCacheConfigItem('cache_enabled');

		if ($cache_enabled == FALSE)
			return FALSE;

		return $this->{$this->_adapter}->readCache($type, $name, $ID, $onlyCheck = FALSE);
	}

	/** incCache
	 * 
	 * Increment a cache value
	 * @param type $type
	 * @param type $name
	 * @param type $ID
	 * @param type $increment
	 * @return type
	 */
	public function incCache($type, $name, $ID, $increment = 1) {
		$count = $this->readCache($type, $name, $ID);
		if (is_numeric($count)) {
			$count = $count + $increment;
			$this->writeCache($type, $name, $ID, $count);
			return $count;
		} else {
			$this->writeCache($type, $name, $ID, $increment);
			return $increment;
		}
	}

	/**
	 * getCacheItemExpiration
	 * 
	 * get TTL for an ID and group
	 * 
	 * @param type $type
	 * @param type $name
	 * @param type $originalID
	 * @return type
	 */
	public function getCacheItemExpiration($type, $name, $originalID) {
		$expirations = $this->getCacheConfigItem($name, $type);
		if (!is_array($expirations))
			return $expirations;

		// Get only first part of ID (supose comes from xview)
		$ID = explode('|', $originalID);
		$ID = $ID[0];
		$expirations = array_reverse($expirations);
		$expire_value = 0;
		foreach ($expirations as $key => $val) {
			$expr = '/' . str_replace('/', '\/', $key) . '/';
			if (preg_match($expr, $ID)) {
				$expire_value = $val;
				break;
			}
		}

		return $expire_value;
	}

	/**
	 * Write cache
	 * 
	 * @param string $type Cache type
	 * @param string $name Cache module
	 * @param string $ID Cache ID
	 * @param string $output Ouput to save
	 * @param string $depID Dependency ID
	 * @return string 
	 */
	public function writeCache($type, $name, $ID, $output, $depID = "") {
		if ($this->enabled == FALSE)
			return FALSE;

		if ($this->getCacheConfigItem('cache_enabled') == FALSE)
			return FALSE;

		return $this->{$this->_adapter}->writeCache($type, $name, $ID, $output, $depID = "");
	}

	/**
	 * Disable cache, cache is not read or written
	 */
	public function disableNow() {
		$this->enabled = FALSE;
	}

	/**
	 * setCacheHeaders
	 * 
	 * Set cache-control header for a specific page
	 * 
	 * @param type CacheGroup
	 * @param string Cachesubgroup
	 * @param type PageID
	 * @return boolean
	 */
	public function setCacheHeaders($type = 'cache_pages', $name = 'pageexpr', $ID = '') {
		if ($this->enabled == FALSE)
			return FALSE;

		$this->cache_type = $type;
		$this->cache_name = $name;

		if ($ID == '')
			$ID = $_SERVER['REQUEST_URI'];

		if ($this->getCacheIsAvailable() === FALSE) {
			return FALSE;
		}

		$item_properties = array();

		$originalID = $ID;

		$cache_enabled = $this->getCacheConfigItem('cache_enabled');

		if ($cache_enabled == FALSE)
			return FALSE;

		self::logMessage('debug', "Setting headers $type - $name - $ID.");

		$item_expiration = $this->getCacheItemExpiration($type, $name, $originalID);

		if (is_array($item_expiration)) {
			$item_properties = $item_expiration;
			$name .= '-' . $item_properties[0];
			$item_expiration = $item_properties[1];
		}
		if ($item_expiration === FALSE) {
			$item_expiration = $this->getCacheConfigItem('default', $type);
			if ($item_expiration === FALSE)
				return FALSE;
		}

		if ($item_expiration == 0)
			header("Cache-Control: no-cache, must-revalidate");
		else
			header("Cache-Control: max-age=" . $item_expiration . ", public");
		return TRUE;
	}

	/**
	 * __get()
	 *
	 * @param 	child
	 * @return 	object
	 */
	public function __get($child) {
		$obj = new stdClass();
		// Check if it's a valid driver
		if (!in_array($child, array_map('strtolower', $this->valid_drivers)))
			$child = $this->_backup_driver;

		// Locate driver
		$filepath = __DIR__ . '/drivers/XCache_' . $child . '.php';

		// If exists, load it
		if (file_exists($filepath)) {
			include_once $filepath;
			$this->_adapter = $child;
			$childClass = 'XCache_' . $child;
			$obj = new $childClass;
			$this->{$child} = $obj;
		}

		// If not exists, obj not loaded or not supported, back to backup driver
		if (!file_exists($filepath) or ! is_object($obj) or ( is_object($obj) && !$this->isSupported($child))) {
			$filepath = __DIR__ . '/drivers/XCache_' . $this->_backup_driver . '.php';
			include_once $filepath;
			$this->_adapter = $this->_backup_driver;
			$childClass = 'XCache_' . $this->_backup_driver;
			$obj = new $childClass;
			$this->{$child} = $obj;
		}

		return $obj;
	}

	/**
	 * Is the requested driver supported in this environment?
	 *
	 * @param 	string	The driver to test.
	 * @return 	array
	 */
	public function isSupported($driver) {
		static $support = array();

		if (!isset($support[$driver])) {
			$support[$driver] = $this->{$driver}->isSupported($driver);
		}

		return $support[$driver];
	}

	/** enableCache 
	 * 
	 * Set cache for or whatever where are located.
	 * Put writeAndFlushCache at end of ppocess
	 * 
	 * @param varchar $type
	 * @param varchar $name
	 */
	public function enableCache($type = 'cache_pages', $name = 'pageexpr', $ID = '') {
		if ($this->getCacheConfigItem('cache_enabled') == FALSE)
			return FALSE;

		$this->cache_type = $type;
		$this->cache_name = $name;

		if ($ID == '')
			$ID = $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'];

		if (($view = $this->readCache($this->cache_type, $this->cache_name, $ID)) !== FALSE) {
			echo $view;
			return TRUE;
		} else {
			ob_start();
			return FALSE;
		}
	}

	/**
	 * enableCache view
	 * 
	 * Enable cache inside a view.
	 * End with writeAndFlushCacheView
	 * 
	 * @param varchar $type
	 * @param varchar $name
	 */
	public function enableCacheView($type = 'cache_pages', $name = 'pageexpr', $ID = '') {
		if ($this->getCacheConfigItem('cache_enabled') == FALSE)
			return FALSE;

		if (!$this->enabled)
			return FALSE;

		$this->cache_type = $type;
		$this->cache_name = $name;

		if ($ID == '')
			$ID = $_SERVER['REQUEST_URI'];

		if ($this->enabled == TRUE) {
			$view = $this->readCache($this->cache_type, $this->cache_name, $ID);

			if ($view != FALSE && $view != '') {
				return $view;
			} else {
				ob_start();
				return FALSE;
			}
		}
		return FALSE;
	}

	/**
	 * Flush current cache, only for full page cache
	 * @param string $ID
	 */
	public function writeAndFlushCache($type = '', $name = '', $ID = '') {
		if ($this->getCacheConfigItem('cache_enabled') == FALSE)
			return FALSE;

		if ($ID == '')
			$ID = $_SERVER["REQUEST_URI"];

		if ($type == '')
			$type = $this->cache_type;

		if ($name == '')
			$name = $this->cache_name;

		if ($this->enabled == TRUE) {
			$view = ob_get_contents();
			ob_end_clean();
			$this->writeCache($this->cache_type, $this->cache_name, $ID, $view, '', FALSE);
			echo $view;
		}
	}

	/**
	 * Flush current cache, only for full page cache
	 * @param string $ID
	 */
	public function writeAndFlushCacheView($type = '', $name = '', $ID = '', $JS = '', $CSS = '') {
		if ($this->getCacheConfigItem('cache_enabled') == FALSE)
			return FALSE;

		if ($ID == '')
			$ID = $_SERVER["REQUEST_URI"];

		if ($type == '')
			$type = $this->cache_type;

		if ($name == '')
			$name = $this->cache_name;

		if ($this->enabled == TRUE) {
			$view = ob_get_contents();
			ob_end_clean();
			$this->writeCache($this->cache_type, $this->cache_name, $ID, array('JS' => $JS, 'CSS' => $CSS, 'HTML' => htmlspecialchars($view)), '', FALSE);
			if ($view != '')
				echo $view;
		}
	}

	/**
	 * getXCInstance
	 * 
	 * Return current intance of XCache
	 * 
	 * @return object
	 */
	static public function getXCInstance() {
		if (!isset(self::$xcinstance))
			self::$xcinstance = new self;
		return self::$xcinstance;
	}

	/**
	 * cache
	 * Get or Write cache and return result
	 *
	 * @param string $type
	 * @param string $name
	 * @param string $ID
	 * @param var $objectorvalue
	 * @param string $method
	 * @param array $methodParams
	 * @return var
	 */
	public function cache($type, $name, $ID, $objectorvalue, $method = '', $methodParams = '') {
		$this->getXCInstance();
		if (($cachedata = $this->readCache($type, $name, $ID)) === false) {
			if (isset($objectorvalue) && $method != '' && !isset($objectorvalue::$xcache)) {
				$objectInstance = new $objectorvalue();
			}
			if (isset($objectorvalue) && $method != '' && isset($objectorvalue::$xcache)) {
				if (!is_array($methodParams))
					$methodParams = array($methodParams);
				$cachedata = call_user_func_array(array($objectorvalue::$xcache, $method), $methodParams);
			} else
				$cachedata = $objectorvalue;

			$this->writeCache($type, $name, $ID, $cachedata);
		}
		return ($cachedata);
	}

	/**
	 * logMessage
	 * 
	 * Define your own message function 'log_message' to include it in your framework
	 * @param varchar $type
	 * @param varchar $msg
	 * @return varchar
	 */
	static public function logMessage($type, $msg, $populate = '', $exception = '') {
		if (function_exists('log_message'))
			log_message($type, $msg);
		if ($populate == 'die')
			die("$type: $msg");
		if ($populate == 'exception' && $exception != '')
			throw new Exception("XCacheError: $exception - $type : $msg");
	}

}
