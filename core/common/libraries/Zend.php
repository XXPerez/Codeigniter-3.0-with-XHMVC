<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

/**
 * Zend Framework Loader
 *
 *
 * Usage:
 *   1) $this->load->library('Zend', 'Zend/Package/Name');
 *		or
 *   2) $this->load->library('Zend');
 *      then $this->zend->load('Zend/Package/Name');
 * 
 *   3) Use Zend library as :
 * 		$this->load->library('Zend','Zend/Debug/Debug');
 *		$this->Zend_Debug = new Zend\Debug\Debug();
 *
 * * the second usage is useful for autoloading the Zend Framework library
 * * Zend/Package/Name does not need the '.php' at the end
 */
class Zend
{
	/**
	 * Constructor
	 *
	 * @param	string $class class name
	 */
	function __construct($class = NULL)
	{ 
		// include path for Zend Framework
		// alter it accordingly if you have put the 'Zend' folder elsewhere
        if (!strstr(ini_get('include_path'),COMMONPATH . 'third_party'))
        {
            ini_set('include_path',
            ini_get('include_path') . PATH_SEPARATOR . COMMONPATH . 'third_party');
        }

		if ($class)
		{
			require_once (string) $class . EXT;
			log_message('debug', "Zend Class $class Loaded");
		}
		else
		{
			log_message('debug', "Zend Class Initialized");
		}
	}

	/**
	 * Zend Class Loader
	 *
	 * @param	string $class class name
	 */
	function load($class)
	{
		require_once (string) $class . EXT;
		log_message('debug', "Zend Class $class Loaded");
	}
}

