<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');
/**
 * XCache base class
 *
 * @package        	CodeIgniter
 * @subpackage    	XDBCache
 * @category    	DataBase
 * @author        	Xavier Perez
 * @license         WTFPL :  http://www.wtfpl.net/about/
 *  
 */
/*
 * XDBCache
 *
 * Database cache system
 * 
 * Uses identifiers to allow different expiration time for each query
 * 
 * Load library: $this->load->library('XCache/XDBCache');
 *
 * Use with getQuery (SQL direct command): 
 *      $result = $this->xdbcache->getQuery(QUERY_GROUP,CACHEKEY,SELECT COMMAND);
 *      $result = $this->xdbcache->getQuery('MyQueryGroup','ID=1 order by ID','SELECT * FROM users where id = 1');
 * 
 * 		Params;
 * 			QUERY GROUP = ('MyQueryGroup') - Group Identifier. Is the group of query to execute. Normally refers to the model method.  See this group in xcacheconf file.
 * 			CACHEKEY = ('ID=1 order by ID') - Unique ID for this query, allow to cache each different ID's and orders.
 * 				For complex queries, put all where+sort+limit clause. XCache will make an unique ID (md5) of all the string.
 * 
 * Use with activeRecord: 
 *      $result = $this->xdbcache->cache(QUERY GROUP,CACHEKEY)->where(WHERE)->get(TABLE);
 *      $result = $this->xdbcache->cache('MyqueryGroup','ID=1')->where('id',1)->get('users');
 * 
 * More examples:
 * 		$result = $this->xdbcache->getQuery('GetTest1','ID=1 order by ID','SELECT * FROM xdbcache_test where id = 1 order by id');
 * 		$result = $this->xdbcache->cacheDB('GetTest1','ID=1 order by ID')->where('id',1)->order_by('id')->get('xdbcache_test');
 * 		** This results share same cache 'GetTest1' and it's results.
 * 		
 * Return:
 * 		Allways return CI_DB_mysqli_result object, you can obtain:
 * 			$result->result_array() // Return an array of elements
 * 			$result->result_object() // Return an array of objects
 * 			$result->result() // Return default result (object)
 * 			$result->num_tows() // Return result rows fetched
 * 
 * Delete cache:
 * 		All query group:
 * 			$this->xdbcache->deleteCache('MyQueryGroup'); 
 * 		Only a specific query:
 * 			$this->xdbcache->deleteCache('MyQueryGroup','ID=1 order by ID'); 
 * 
 * Select another DB:
 *      $this->xdbcache->getDB('otherDB'); // As configured in config/database.php
 * 
 * Disable cache for an ActiveRecord query:
 *      $result = $this->xdbcache->nocache()->where('id',1)->order_by('id')->get('xdbcache_test');
 * 		
 */
require_once __DIR__ . "/XCache.php";

class XDBCache 
{

	protected $cache = false;
	protected $dbs;
	protected $dbname;
	protected $cacheType = 'cache_dbquery';

	/**
	 * 
	 */
	public function __construct()
	{
		//parent::__construct();
		$this->CI = get_instance();
		$this->CI->load->driver('XCache/XCache');
		$this->xcache = $this->CI->xcache;
	}

	/**
	 * Initialize the Cache Class
	 *
	 * @access	private
	 * @return	boolean
	 */
	private function cache_init()
	{
		if (is_object($this->getDB()->CACHE) AND class_exists('CI_DB_Cache')) {
			return TRUE;
		}
		if (!class_exists('CI_DB_Cache')) {
			return $this->cache_off();
		}

		$this->getDB()->CACHE = new CI_DB_Cache($this->dbs); // pass db object to support multiple db connections and returned db objects

		return TRUE;
	}

	/**
	 * Disable cache for next call
	 * 
	 * @return XDBCache 
	 */
	public function nocacheDB()
	{
		$this->getDB()->cache_off();
		return $this;
	}

	/**
	 * ActiveRecord cache call
	 * 
	 * @param string $cacheCode		Cache group ID
	 * @param string $ID			Unique ID for this group
	 * @return XDBCache 
	 * 
	 */
	public function cacheDB($cacheCode, $cacheID)
	{
		$this->cache_init();
		$this->getDB()->cache_on();
		$this->getDB()->CACHE->CACHE_CODE = array('cacheCode' => $cacheCode, 'cacheID' => $cacheID);
		return $this;
	}

	/**
	 * Change database group
	 * 
	 * @param string $dbname		DB group name, as defined in config/database.php
	 * @return XDBCache 
	 * 
	 */
	public function useDB($dbname)
	{
		if ($dbname != '')
		{
			$this->dbname = $dbname;
			$this->getDB($dbname, true);
		}
		return $this;
	}

	/**
	 * Get CI DB object 
	 * 
	 * @param string $dbname		DB group name, as defined in config/database.php
	 * @return CI_DB 
	 * 
	 */
	public function getDB($dbname = 'default', $force=false)
	{
		if (!isset($this->dbs) || $force == true) {
			$this->dbs = $this->CI->load->database($dbname, TRUE);
			//$this->dbs->load_rdriver();
			$this->CI->db = $this->dbs;
		}

		return $this->dbs;
	}

	/**
	 * Delete Cache group or cache group/id
	 * 
	 * @param string $cacheCode		Cache group ID
	 * @param string $cacheID		Unique ID for this group
	 * 
	 * @return boolean 
	 * 
	 */
	public function delCacheDB($cacheCode, $cacheID = '')
	{
		$this->cache_init();
		return $this->getDB()->CACHE->delete_cache($this->cacheType, $cacheCode, $cacheID);
	}

	/**
	 * Cache SQL commands
	 *
	 * @param string $name		Cache group ID
	 * @param string $ID		Unique ID for this group
	 * @param string $sql		Sql command
	 * 
	 * @return CI_DB_result 
	 */
	public function getQueryDB($cacheCode, $cacheID, $sql, $returnObject = FALSE, $returnField = '')
	{
		if (!is_object($this->dbs))
			$this->getDB();

		if (($result = $this->CI->xcache->readCache($this->cacheType, $cacheCode, $cacheID)) === FALSE) {
			$query = $this->getDB()->query($sql);
			$CR = new CI_DB_result();
			$CR->num_rows = $query->num_rows();
			$CR->result_object = $query->result_object();
			$CR->result_array = $query->result_array();
			$CR->conn_id = NULL;
			$CR->result_id = NULL;
			$result = $CR;
			$this->CI->xcache->writeCache($this->cacheType, $cacheCode, $cacheID, $query);
			$query->free_result();
		} else {
			$this->getDB()->queries[] = $sql;
			$this->getDB()->query_times[] = 0;
		}



		if ($returnObject === FALSE)
			return $result;
		else {
			if ($returnField != '') {
				if (isset($result[0]->$returnField)) {
					return $result[0]->$returnField;
				}
			}
			return $result[0];
		}
	}

	/**
	 * Call all not defined methods throught CI_DB object
	 *
	 * */
	public function __call($method, $arguments)
	{
		if (!method_exists($this->getDB(), $method)) {
			throw new Exception('Undefined method DBCache::' . $method . '() called');
		}
		return call_user_func_array(array($this->getDB(), $method), $arguments);
	}

}

// ------------------------------------------------------------------------

/**
 * Database Cache Class
 *
 * @category	Database
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/database/
 */
class CI_DB_Cache
{

	public $CI;
	public $db; // allows passing of db object so that multiple database connections and returned db objects can be supported
	public $CACHE_CODE = array();
	public $CACHE_DRIVER;

	/**
	 * Constructor
	 *
	 * Grabs the CI super object instance so we can access it.
	 *
	 */
	function __construct(&$db)
	{
		// Assign the main CI object to $this->CI
		// and load the file helper since we use it a lot
		$this->CI = & get_instance();
		$this->db = & $db;
		$this->CI->load->driver('XCache/XCache');

		// TODO
		//$this->CACHE_DRIVER = $this->CI->load->driver('XCache', array('adapter' => 'file'));
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieve a cached query
	 *
	 * The URI being requested will become the name of the cache sub-folder.
	 * An MD5 hash of the SQL statement will become the cache file name
	 *
	 * @access	public
	 * @return	string
	 */
	function read($sql)
	{
		if (!isset($this->CACHE_CODE['cacheCode']))
			return FALSE;
		if (!isset($this->CACHE_CODE['cacheID']))
			return FALSE;

		if (($cachedata = $this->CI->xcache->readCache('cache_dbquery', $this->CACHE_CODE['cacheCode'], $this->CACHE_CODE['cacheID'])) === FALSE)
			return FALSE;
		else {
			$this->CACHE_CODE = array();
			$this->db->cache_off();

			return $cachedata;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Write a query to a cache file
	 *
	 * @access	public
	 * @return	bool
	 */
	function write($sql, $object)
	{
		if (!isset($this->CACHE_CODE['cacheCode']))
			$this->CACHE_CODE['cacheCode'] = 'default';
		if (!isset($this->CACHE_CODE['cacheID']))
			$this->CACHE_CODE['cacheID'] = md5($sql);

		$this->CI->xcache->writeCache('cache_dbquery', $this->CACHE_CODE['cacheCode'], $this->CACHE_CODE['cacheID'], $object);
		log_message('debug', 'Cache: ' . $this->CACHE_CODE['cacheCode'] . "() : \n" . $sql);

		$this->CACHE_CODE = array();
		$this->db->cache_off();

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete cache files within a particular directory
	 *
	 * @access	public
	 * @return	bool
	 */
	function delete($segment_one = '', $segment_two = '')
	{

		/*  NOT USED, ONLY HERE FOR BACKWARDS COMPATIBILITY
		  delete_files($dir_path, TRUE);
		 * 
		 */
	}

	function delete_cache($cacheType, $cacheCode = '', $cacheID = '')
	{
		return $this->CI->xcache->deleteCache($cacheType, $cacheCode, $cacheID);
	}

	// --------------------------------------------------------------------

	/**
	 * Delete all existing cache files
	 *
	 * @access	public
	 * @return	bool
	 */
	function delete_all()
	{
		return $this->CI->xcache->deleteAllCache();
	}

}

/* End of file XDBCache.php */


