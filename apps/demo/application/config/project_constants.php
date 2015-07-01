<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include(strtolower(ENVIRONMENT).'/env_project_constants.php');

/*
 * Detect AJAX Request for MY_Session
 */
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'); 


/* End of project_constants.php */
