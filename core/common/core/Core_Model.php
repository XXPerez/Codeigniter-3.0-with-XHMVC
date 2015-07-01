<?php
/**
 * Supermodel for caching methods
 * 
 *
 * @author xperez
 */
class Core_Model extends CI_Model{

	/* Common module extender object */
	protected $xtends;

	protected $cache=TRUE;

	public function __construct() {
		parent::__construct();
		$this->load->library('XCache/XDBCache');
		$this->db = $this->xdbcache->getDB();
	}
	
	/**
	 * Get properties from the common module, otherwise, from $APP
	 * 
	 * @param type $myVar
	 * @return var
	 * @throws Exception
	 */
	public function __get($myVar)
	{
		if (isset($this->xtends->$myVar))
			return $this->xtends->$myVar;
		if (isset(CI::$APP->$myVar))
			return CI::$APP->$myVar;
		throw new Exception('No such var: ' . $myVar);		
	}

	/**
	 * Set properties to a var inside the common module, only if exists
	 * 
	 * @param type $myVar
	 * @param type $myValue
	 */
	public function __set($myVar,$myValue='')
	{
		if (isset($this->xtends->$myVar))
			$this->xtends->$myVar = $myValue;
		else
			CI::$APP->$myVar = $myValue;
	}

    /**
	 * Call any method inside common module, else call $APP method
	 * 
	 * @param type $name
	 * @param array $arguments
	 * @return type
	 * @throws Exception
	 */
	public function __call($name, array $arguments) {
	    if (method_exists($this->xtends, $name)) {
		    return call_user_func_array(array($this->xtends, $name), $arguments);
	    }
	    if (method_exists(CI::$APP, $name)) {
		    return call_user_func_array(array(CI::$APP, $name), $arguments);
	    }

	    if (method_exists($this, '_'.$name))
            {
                $ID = md5(json_encode($arguments));
                
                $methodName =  '_'.$name;
                if (($result = $this->xdbcache->xcache->readCache('cache_methods', get_class($this).'_'. $methodName, $ID))  !== FALSE)
                { 
                    if (function_exists('profiler_log')) profiler_log('CACHE',"Cache Method GET: ".get_class($this)."/". $methodName);
                }
                else
                {
                    $result = call_user_func_array(array(&$this,  $methodName), $arguments);
                    $this->xdbcache->xcache->writeCache('cache_methods', get_class($this).'_'. $methodName, $ID, $result);
                    if (function_exists('profiler_log')) profiler_log('CACHE',"Cache Method PUT: ".get_class($this)."/". $methodName);
                }		
                return $result;
           }

           throw new Exception('No such method: ' . $name);		
	}

	
	/**
	 * Common module extender
	 * 
	 * @param type $class
	 * @param type $module
	 * @param type $params
	 */
	public function xtends_loader($class, $module='', $params='')
	{
		$currentPath = $module;
		$currentPath = str_replace('\\','/',$currentPath);
		$appPath = str_replace('\\','/',realpath(APPPATH));
		$commonPath = str_replace('\\','/',realpath(COMMONPATH));
		$currentPath = str_replace( $appPath,$commonPath,$currentPath);

		if (file_exists($currentPath))
		{
			$moduleExtends = file_get_contents($currentPath);
			$moduleExtends = str_ireplace('class '.$class,'class '.ucfirst($class)."_common",$moduleExtends);
			$moduleExtends=preg_replace("/<\?php|<\?|\?>/", "", $moduleExtends);
			eval($moduleExtends);
			
			$newclass = ucfirst($class)."_common"; 
			$this->xtends = new $newclass($params);
		}

	}
	
}