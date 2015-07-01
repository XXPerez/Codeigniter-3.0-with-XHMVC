<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "welcome";
$route['404_override'] = '';

global $_ROUTES;

$_ROUTES = array (
	'welcome' => 'welcome/index',
	'welcome/(:any)' => 'welcome/$1',
	'testxcache' => 'testxcache/index',
	'404_override' => 'staticpage/error404',
    
	'auth' => 'ion_auth/auth/index',
	'auth/(:any)' => 'ion_auth/auth/$1',
	'auth/(:any)/(:any)' => 'ion_auth/auth/$1/$2',
);

if(!isset($route) || !is_array($route)) $route = array();
foreach($_ROUTES as $r => $data){
	$route[$r] = $data;
}

/* End of file routes.php */
/* Location: ./application/config/routes.php */