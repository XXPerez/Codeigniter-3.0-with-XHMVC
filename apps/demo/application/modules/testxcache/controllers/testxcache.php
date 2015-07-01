<?php

class Testxcache extends MY_Controller 
{
    public function __construct()
    {
	parent::__construct();
    }
    
    public function index()
    {
	$this->load->library('testxcache/CacheExamples');

    }
}