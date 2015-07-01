<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

/* load the MX_Loader class */
require COMMONPATH . "third_party/MX/Loader.php";

class Core_Loader extends MX_Loader
{

	/**
	 * CORE LOAD CONSTRUCTOR
	 * 
	 * Assigns all CI paths to allos common functionality
	 * 
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_ci_library_paths = array(APPPATH, COMMONPATH, BASEPATH);
		$this->_ci_helper_paths = array(APPPATH, COMMONPATH, BASEPATH);
		$this->_ci_model_paths = array(APPPATH, COMMONPATH);
		$this->_ci_view_paths = array(APPPATH . 'views/' => TRUE, COMMONPATH . 'views/' => TRUE);
		$this->config->_config_paths = array(APPPATH, COMMONPATH);
	}

	/*
	 * DATABASE: Load database drivers. 
	 * 
	 * Used to collect queries for different database connections to profiler. 
	 *
	 * @param string $params
	 * @param boolean $return
	 * @param boolean $active_record
	 * @return object Return DB object
	 */

	public function database($params = '', $return = FALSE, $active_record = NULL) {
	    if ($params == '') $params = 'default';

	    if (isset(CI::$APP->dbLoaded[$params]))
	    { 
		    CI::$APP->db = CI::$APP->dbLoaded[$params];
		    return CI::$APP->db;
	    }

	     if (class_exists('CI_DB', FALSE) AND $return == FALSE AND $active_record == NULL AND isset(CI::$APP->db) AND is_object(CI::$APP->db)) 
			    return;

	    require_once BASEPATH.'database/DB'.EXT;

	    CI::$APP->db = DB($params, $active_record);

	    // Set Timezone only for mysqli, for mysql(& sphinx, don'0t apply)
	    if (CI::$APP->db->dbdriver == 'mysqli')
	    {
		//CI::$APP->db->query('SET time_zone = "America/Argentina/Buenos_Aires";');
	    }
	    
	    CI::$APP->dbLoaded[$params] = CI::$APP->db;

		if ($return === TRUE) 
		    return CI::$APP->db;
	}
	

}
