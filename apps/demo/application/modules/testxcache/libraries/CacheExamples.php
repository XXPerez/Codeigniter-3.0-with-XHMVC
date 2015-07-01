<?php

/**
 * Example using xcache in a class
 * 
 * The static var xcache MUST TO EXISTS, this obtains the class itself, and it's used to call any method inside the class.
 * 
 * The 
 */
class CacheExamples
{
    
}
class testCache
{
    // MUST TO EXISTS
    public static $xcache='';

    public function __construct()
    {
        // xcache MUST TO BE ASSIGNED TO THIS CLASS
        self::$xcache=$this;
    }

    public function dumpServerVar($item)
    {
        $XCache = XCache::getXCInstance();
        return $XCache->cache('cache_test','MyTestInfo','MyTestInfoID',get_class($this),'_dumpServerVar',$item);
    }

    public function _dumpServerVar($item)
    {
        if (isset($_SERVER[$item]))
            return $_SERVER[$item];
        else
            return null;
    }

    /**
     * Function to call other class within XCCache
     * 
     * @param type $date
     * @return type
     */
    public function otherClassCache($date)
    {
        // xcache MUST TO BE ASSIGNED TO A CLASS
        $newclass = new OtherClassCache();
        $XCache = XCache::getXCInstance();
        return $XCache->cache('cache_test','MyExternallClassCall','otherClass',get_class($newclass),'otherMethod',$date);
    }
}

/** 
 * Another class to show how can be called any external class
 */
class OtherClassCache
{
    // MUST TO EXISTS
    public static $xcache;

    public function __construct()
    {
        // xcache MUST TO BE ASSIGNED TO THIS CLASS
        self::$xcache = $this;
    }
    
    function otherMethod($date)
    {
        return "External call to other class successfull on $date";
    }
}

/**
 * Load the test class
 */
$testCache = new testCache();

/**
 * Example loading dumServerVar, this method calls XCache->cache.
 * XCache->cache load _dumpServerVar method if it's not cached through xcache static var.
 */
echo 'Cache $_SERVER[\'REQUEST_TIME\'] : '.$testCache->dumpServerVar('REQUEST_TIME')."<br />";
echo '$_SERVER[\'REQUEST_TIME\'] date   '.date('Y-m-d H:i:s',$testCache->dumpServerVar('REQUEST_TIME'))."<br />";
echo '$_SERVER[\'REQUEST_TIME\'] cached '.(time()-$testCache->dumpServerVar('REQUEST_TIME')).' seconds ago'."<br />";
echo 'This cache expires each: '.XCache::getXCInstance()->getCacheItemExpiration('cache_test','MyTestInfo','otherClass').' seconds'."<hr />";

/**
 * Example loading other class inside testCache, this method calls XCache->cache.
 * XCache->cache load otherClassCache->otherMethod method if it's not cached through xcache static var.
 */
echo 'Cache other class method : '.$testCache->otherClassCache(date('Y-m-d H:i:s'))."<br />";
echo 'This cache expires each: '.XCache::getXCInstance()->getCacheItemExpiration('cache_test','MyExternallClassCall','MyTestInfoID').' seconds'."<hr />";

/**
 * Example using increment
 */
echo 'Cache & increment test num     : '.XCache::getXCInstance()->incCache('cache_test','MyTestCount','MyTestCountID',1)."<hr />";

/**
 * Example saving a fixed value
 */
$randNum = rand(1000,9999);
echo 'Cache a rand value    : '.XCache::getXCInstance()->cache('cache_test','MyTestValue','RandVar',$randNum)."<br />";
echo 'This cache expires each: '.XCache::getXCInstance()->getCacheItemExpiration('cache_test','MyTestValue','RandVar').' seconds'."<hr />";

/**
 * Example saving an object
 */
$myObject = new stdClass();
$myObject->id = 24;
$myObject->name = 'Product name';
$myObject->qty_random = rand(1,1000);
$myObject->date_insert = date('Y-m-d H:i:s');

echo 'Cache an object     :';
var_dump(XCache::getXCInstance()->cache('cache_test','MyTestValue','MyObject',$myObject));
echo "<br />";
echo 'This Object cache expires each: '.XCache::getXCInstance()->getCacheItemExpiration('cache_test','MyTestValue','MyObject').' seconds'."<hr />";

/**
 * Memory usage
 */
echo "Memory used: ".number_format(memory_get_usage()/1000,0)."Kb";
?>
<br />
<br />
Press 'refresh' to see how the cache changes ...  
<input type='button' value='Refresh' onclick='location.reload();'>
