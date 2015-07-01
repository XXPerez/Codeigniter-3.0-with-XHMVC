<?php

require_once COMMONPATH."third_party/MX/Controller.php";

/**
 * Description of Common_Controller
 *
 */
class Core_Controller extends MX_Controller
{
	/* Common module extender object */
	protected $xtends;
	

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
       throw new Exception('No such method: ' . $name);		
	}
	
	/**
	 * Remap any call to an existing method in common module
	 * 
	 * @param type $method
	 * @param type $params
	 * @return type
	 */
	public function _remap( $method , $params = array())
	{
		if (method_exists($this, $method)) {
            return call_user_func_array(array($this, $method), $params);
		}
		if (method_exists($this->xtends, $method)) {
            return call_user_func_array(array($this->xtends, $method), $params);
		}
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

