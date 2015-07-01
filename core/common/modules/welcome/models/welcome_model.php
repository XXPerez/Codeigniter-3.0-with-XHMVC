<?php

class Welcome_model extends Core_Model
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function testdb()
	{
		//$this->xdbcache->useDB('direct');
		$result = $this->xdbcache->cacheDB('GetTest','order by post_content')->order_by('post_content')->get('wp_posts');
		return $result->result();
	}
}
