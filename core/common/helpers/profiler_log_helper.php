<?php
/* 
 * Profiler log
 *
 * Allow to display log in the TRACE tab of the main profiler
 */

if (!function_exists('profiler_log'))
{
	/**
	 * Set a line in the profiler log
	 *
	 * @param string $line
	 */
	function profiler_log($type,$line,$vars = '')
	{
        if (!isset(CI::$APP->profiler_log))
            CI::$APP->profiler_log = array();
		if (CI::$APP->config->item('log_profiler'))
		{
			if ($vars == '')
				CI::$APP->profiler_log[$type][] = $line;
			else
				CI::$APP->profiler_log[$type][] = '<b>'.$line.'</b><hr><pre>'.htmlspecialchars(print_r($vars,TRUE)).'</pre>';
		}
	}
}

if (!function_exists('dd'))
{
	/**
	 * DumpDie. Dumps de given function arguments and dies.
	 * Also prints the file and line that called it, in case you wonder where it is :)
	 * @return void
	 */
	function dd() {
		$callerTrace = reset(debug_backtrace(0, 1));
		printf('DumpDie called in file %s line %u', $callerTrace['file'], $callerTrace['line']);
		call_user_func_array('var_dump', func_get_args());
		die();
	}
}

/**
 * Sets the given parameter into the debug bar Variables -> DEBUG VARS section
 * @param mixed $vars
 * @return void
 */
function debug_vars($vars) {
	$callerTrace = reset(debug_backtrace(0, 1));
	profiler_log('DEBUG VARS', $callerTrace['file'] . ': ' . $callerTrace['line'], $vars);
}