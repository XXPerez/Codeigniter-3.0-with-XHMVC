<?php
/**
 * XCache MONGODB Caching Class
 *
 * @package		XCache
 * @subpackage	Libraries
 * @category	MongoDB
 * @author		XPerez
 * @link
 */

class XCache_mongodb extends XCache {

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
	public function readCache($type,$name,$ID,$onlyCheck=FALSE)
	{
		$this->getInstance();
        $originalID = $ID;

		if (isset($_POST) && count($_POST)>0)
			$ID = $ID.md5(serialize($_POST));

		self::logMessage('debug', "Reading memcache $type - $name - $ID.");

		$item_expiration = $this->getCacheItemExpiration($type,$name,$originalID);

		if (is_array($item_expiration))
		{
			$item_properties = $item_expiration;
			$name .= '-'.$item_properties[0];
			$item_expiration = $item_properties[1];
		}

		if ($item_expiration == FALSE)
		{
			$item_expiration = $this->getCacheConfigItem('default',$type);
			if ($item_expiration == FALSE)
				return FALSE;
		}

		$cacheResult=$this->getInstance()->findOne(array('ID' => $type.'-'.$name.'-'.$ID));

        if ($cacheResult == FALSE) 
			return FALSE;

        $expires = $cacheResult['expires'];
		$cache = $cacheResult['content'];

                // Has the file expired? If so we'll delete it.
		if (time() >= $expires)
		{
            return false;
        }
        
        if (function_exists('profiler_log')) profiler_log('CACHE','MongoDB Read OK: '.$type.'/'.$name.'/'.$ID);
		
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
	public function writeCache($type,$name,$ID,$output,$depID="")
	{
        $originalID = $ID;

		//if (function_exists('profiler_log')) profiler_log('CACHE','Memcache Write init : '.$type.'/'.$name.'/'.$ID);

		$item_expiration = $this->getCacheItemExpiration($type,$name,$originalID);
		
		if (is_array($item_expiration))
		{
			$item_properties = $item_expiration;
			$name .= '-'.$item_properties[0];
			$item_expiration = $item_properties[1];
		}

		if ($item_expiration == FALSE)
		{
			$item_expiration = $this->getCacheConfigItem('default',$type);
			if ($item_expiration == FALSE)
				return FALSE;
		}

        $expire = time() + ($item_expiration);

		$this->getInstance()->update(array('ID' => $type.'-'.$name.'-'.$ID),array('ID' => $type.'-'.$name.'-'.$ID,'insert' => time(),'expires' => $expire, 'content' => serialize($output)), array("upsert" => true));

		if (function_exists('profiler_log')) profiler_log('CACHE','MongoDB Write OK: '.$type.'/'.$name.'/'.$ID);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of the item in the cache
	 * @param 	boolean		TRUE, simulating success
	 */
	public function deleteCache($type,$name,$ID)
	{
    	$originalID = $ID;
    	
    	if (isset($_POST) && count($_POST)>0)
    		$ID = $ID.md5(serialize($_POST));
		
		$this->getInstance()->remove(array("ID" =>$type.'-'.$name.'-'.$ID));
		
	}

	
	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return 	boolean		TRUE, simulating success
	 */
	public function cleanCache()
	{
		$this->getInstance()->drop();
	}

	// ------------------------------------------------------------------------

	/**
	 * Cache Info
	 *
	 * @param 	string		user/filehits
	 * @return 	boolean		FALSE
	 */
	 public function getCacheInfo($type = NULL)
	 {
		 return FALSE;
	 }

	// ------------------------------------------------------------------------

	/**
	 * Get Cache Metadata
	 *
	 * @param 	mixed		key to get cache metadata on
	 * @return 	boolean		FALSE
	 */
	public function getCacheMetadata($id)
	{
		return FALSE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Is this caching driver supported on the system?
	 * Of course this one is.
	 *
	 * @return TRUE;
	 */
	public function isSupported()
	{
        if (!extension_loaded('mongo'))
		{
			self::logMessage('error', 'The MONGODB PHP extension must be loaded to use MomgoDB Cache.');
			return FALSE;
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * getInstance
	 * Reuse memcache class
	 */
	public function getInstance()
	{ 
        // cache_mongodb must to be:
        //     host:port:user:pass:db
		$cache_db = explode(':',$this->getCacheConfigItem('cache_mongodb'));
		
        $cache_db_host = trim($cache_db[0]);
		$cache_db_port = trim($cache_db[1]);
		$cache_db_user = trim($cache_db[2]);
		$cache_db_pass = trim($cache_db[3]);
		$cache_db_dbname = trim($cache_db[4]);
		$cache_db_collection = trim($cache_db[5]);

		$connection_string = "mongodb://";

		if(empty($cache_db_host)):
			self::logMessage('error', "The Host must be set to connect to MongoDB");
		endif;

		if(empty($cache_db_dbname)):
			self::logMessage('error', "The Database must be set to connect to MongoDB");
		endif;

		if(!empty($cache_db_user) && !empty($cache_db_pass)):
			$connection_string .= "{$cache_db_user}:{$cache_db_pass}@";
		endif;

		if(isset($cache_db_port) && !empty($cache_db_port)):
			$connection_string .= "{$cache_db_host}:{$cache_db_port}";
		else:
			$connection_string .= "{$cache_db_host}";
		endif;

		$connection_string = trim($connection_string."/".$cache_db_dbname);		

		// Check exists current instance
		if (!isset($this->_instance))
				$this->_instance = NULL;

		// Create new instance or return current
		if ($this->_instance == NULL)
		{
            $options = array();
            try {
                $client = new MongoClient();
                $dbconnection = new MongoDB($client,$cache_db_dbname);
                $this->_instance = new MongoCollection($dbconnection,$cache_db_collection);
                // Create base collection for xcache
           		@$this->_instance->update(array('ID' => 'xcache','expires' => 0, 'content' => 'basecollection'), array("upsert" => true));
                @$this->_instance->ensureIndex(array('ID'=> 1), array('unique' => true));

            } 
            catch(MongoConnectionException $e) {
                self::logMessage('error', "Unable to connect to MongoDB: {$e->getMessage()}");
            }
        }

		return $this->_instance;
		
	}
}
// End Class

/* End of file Cache_memcache.php */
/* Location: ./system/libraries/Cache/drivers/Cache_dummy.php */