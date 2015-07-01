<?php
/**
 * XCache File Caching Class
 *
 * @package		XCache
 * @subpackage	Libraries
 * @category	File
 * @author		XPerez
 * @link
 */

class XCache_file extends XCache {

	protected $compress = true;
	
	public function  __construct()
	{
	}
	

	/**
	 * Read cache
	 * 
	 * @param string $type Cache type
	 * @param string $name Cache module
	 * @param string $ID  Cache ID
	 * @param boolean $onlyCheck Only cjeck if cache it's valid
	 * @return string 
	 */
	public function readCache($type,$name,$ID,$onlyCheck=FALSE)
	{
		$item_properties = array();

        $originalID = $ID;

		if (isset($_POST) && count($_POST)>0)
			$ID = $ID.md5(serialize($_POST));

		$cache_path = $this->getCacheConfigItem('cache_path');
		$this->compress = $this->getCacheConfigItem('cache_compress');

		self::logMessage('debug', "Reading file $type - $name - $ID.");

		if (!is_dir($cache_path))
			@mkdir($cache_path,0777,TRUE);

		$cache_path = $cache_path.$type.'/'.$name.'/'.substr(md5($ID),0,2).'/'.substr(md5($ID),2,2).'/'.substr(md5($ID),4,2).'/'.substr(md5($ID),6,2).'/';

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

		$filepath = realpath($cache_path).'/'.md5($ID);

		self::logMessage('debug', "Checking file $type - $name - $ID on $filepath.");

		if ( !file_exists($filepath))
		{
			return FALSE;
		}

		if ( ! $fp = @fopen($filepath, 'rb'))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$cache = '';
		if (filesize($filepath) > 0)
		{
			$cache = fread($fp, filesize($filepath));
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		// Strip out the embedded timestamp
		if ( ! preg_match("/(\d+TS--->)/", $cache, $match))
		{
			return FALSE;
		}

		// Has the file expired? If so we'll delete it.
		if (time() >= trim(str_replace('TS--->', '', $match['1'])))
		{
			@unlink($filepath);
			self::logMessage('debug', "Cache file $type - $name has expired. File deleted: ".$filepath);
			if (count($item_properties) > 1)
			{
				foreach (explode('|',$item_properties[2]) as $key => $val)
				{
					foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname($filepath))) as $filename=>$objFile)
					{
					    $val = str_replace('[ID]',$ID,$val);
                                            $regexp = '/^'.trim($val).'/';
                                            if (preg_match($regexp,$objFile->getFileName()))
                                            {
                                                    @unlink($filename);
                                                    self::logMessage('debug', "Cache dependency file deleted: ".$filename);
                                            }
					}
				}
			}
			return FALSE;
		}
		else
		{
			self::logMessage('debug', "Cache file $type - $name is current. Sending it to browser.");
		}

		if ($onlyCheck)
			return TRUE;
		$cache = str_replace($match['0'], '', $cache);
		if (function_exists('profiler_log')) profiler_log('CACHE','Cache Read OK : '.$type.'/'.$name.'/'.$ID);
		
		try
		{
			if ($this->compress==TRUE)
				$output = @unserialize(gzinflate($cache));
			else
				$output = @unserialize($cache);
		}
		catch (Exception $e)
		{
			return false;
		}
		return $output;
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

		if (isset($_POST) && count($_POST)>0)
			$ID = $ID.md5(serialize($_POST));

       	self::logMessage('debug', "Writting file $type $name $ID");

		$cache_path = $this->getCacheConfigItem('cache_path');

		if (!is_dir($cache_path))
			@mkdir($cache_path,0777,TRUE);

		$cache_path = $cache_path.$type.'/'.$name.'/'.substr(md5($ID),0,2).'/'.substr(md5($ID),2,2).'/'.substr(md5($ID),4,2).'/'.substr(md5($ID),6,2).'/';

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

		if (!is_dir($cache_path))
			@mkdir($cache_path,0777,TRUE);

        if ( !is_writable($cache_path))
			return FALSE;

		$filepath = realpath($cache_path).'/'.md5($ID);

		if ( ! $fp = fopen($filepath, 'wb'))
		{
			self::logMessage('error', "Unable to write cache file: ".$filepath);
			return FALSE;
		}
		$expire = time() + ($item_expiration);

		if (flock($fp, LOCK_EX))
		{
			self::logMessage('debug', "Cache file writting: ".$filepath);
			if ($this->compress==TRUE)
				fwrite($fp, $expire.'TS--->'.gzdeflate(serialize($output)));
			else
				fwrite($fp, $expire.'TS--->'.serialize($output));
			flock($fp, LOCK_UN);
		}
		else
		{
			self::logMessage('error', "Unable to secure a file lock for file at: ".$cache_path);
			return FALSE;
		}

		fclose($fp);
		@chmod($filepath, 0777);

		self::logMessage('debug', "Cache file written: ".$filepath);

		if (function_exists('profiler_log')) profiler_log('CACHE','Cache File Write OK: '.$type.'/'.$name.'/'.$ID);

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete from Cache
	 *
	 * @param 	mixed		unique identifier of the item in the cache
	 * @param 	boolean		TRUE, simulating success
	 */
	public function deleteCache($type,$name='',$ID='')
	{
		$cache_path = $this->getCacheConfigItem('cache_path');
		$cache_dir_path = $cache_path.$type.'/'.$name.'/'.substr(md5($ID),0,2).'/'.substr(md5($ID),2,2).'/'.substr(md5($ID),4,2).'/'.substr(md5($ID),6,2).'/';
        if ($ID=='')
    		$cache_dir_path = $cache_path.$type.'/'.$name.'/';
        if ($name=='')
    		$cache_dir_path = $cache_path.$type.'/';
        
        $this->deleteCachefiles($cache_dir_path,TRUE);
    }

	private function deleteCachefiles($path, $del_dir = FALSE, $level = 0)
	{
		// Trim the trailing slash
		$path = rtrim($path, DIRECTORY_SEPARATOR);

		if ( ! $current_dir = @opendir($path))
		{
			return FALSE;
		}

		while (FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename != "." and $filename != "..")
			{
				if (is_dir($path.DIRECTORY_SEPARATOR.$filename))
				{
					// Ignore empty folders
					if (substr($filename, 0, 1) != '.')
					{
						$this->deleteCacheFiles($path.DIRECTORY_SEPARATOR.$filename, $del_dir, $level + 1);
					}
				}
				else
				{
					unlink($path.DIRECTORY_SEPARATOR.$filename);
				}
			}
		}
		@closedir($current_dir);

		if ($del_dir == TRUE AND $level > 0)
		{
			return @rmdir($path);
		}

		return TRUE;
	}

	// ------------------------------------------------------------------------

	/**
	 * Clean the cache
	 *
	 * @return 	boolean		TRUE, simulating success
	 */
	public function cleanCache()
	{
		$cache_path = $this->getCacheConfigItem('cache_path');
        $this->deleteCachefiles($cache_path,TRUE);
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
	public function isSupported($driver)
	{
		return TRUE;
	}
	
	public function getInstance()
	{
		return TRUE;
	}

	// ------------------------------------------------------------------------

}
// End Class

/* End of file Cache_dummy.php */
/* Location: ./system/libraries/Cache/drivers/Cache_dummy.php */