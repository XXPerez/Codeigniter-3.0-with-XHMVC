To use ZEND in Codeigniter:

Download Zend and copy all directorios under this one.

Enable Zend autoloader in config/autoload.php
	$autoload['helper'] = array('zendautoload');

To use Zend in a Controller or Model, use as namespaces:

<?php
     use Zend\Stdlib\PriorityQueue;
.
.
.

