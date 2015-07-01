<?php
$config = array(
	'urlArgs'=>'v='.THEME_JS_VERSION,
	'baseUrl' => '/media/themes/views/'.USE_THEME.'/' . ((defined('DEBUG_JS') && DEBUG_JS) ? 'js':'jsmin'),
	'paths' => array(
		'jquery'=>'libs/jquery',
		'jquery-ui'=>'libs/jquery-ui',
	),
	'shim' => array(
		'jquery-ui' => array('jquery'),
	)
);