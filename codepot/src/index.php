<?php
require_once dirname (__FILE__) . '/config.php';

error_reporting(E_ALL);

$system_folder = "system";
if (strpos($system_folder, '/') === FALSE)
{
	if (function_exists('realpath') AND 
	    @realpath(dirname(__FILE__)) !== FALSE)
	{
		$system_folder = realpath(dirname(__FILE__)) . 
		                 "/{$system_folder}";
	}
}
else
{
	// Swap directory separators to Unix style for consistency
	$system_folder = str_replace("\\", "/", $system_folder); 
}

$application_folder = dirname (__FILE__) . '/codepot';

/*
|---------------------------------------------------------------
| SET DEFAULT TIMZEONE USING THE SYSTEM TIMEZONE
|---------------------------------------------------------------
*/
if (function_exists('date_default_timezone_set') && !ini_get('date.timezone'))
{
	$tz = '';
  
	// On many systems (Mac, for instance) "/etc/localtime" is a symlink
	// to the file with the timezone info
	if (@is_link('/etc/localtime') === TRUE) 
	{
		// If it is, that file's name is actually the "Olsen" format timezone
		$filename = @readlink('/etc/localtime');
		if ($filename !== FALSE)
		{
			$pos = strpos($filename, 'zoneinfo');
			if ($pos !== FALSE) 
			{
				// When it is, it's in the "/usr/share/zoneinfo/" folder
				$tz = substr($filename, $pos + 9);
			} 
		}
	}
	else if (file_exists('/etc/timezone'))
	{
		// Debian
		$tz = @file_get_contents('/etc/timezone');
		if ($tz !== FALSE && strlen($tz) > 0)
		{
			$tz = substr ($tz, 0, strpos ($tz, "\n"));
		}
	}
	else if (file_exists('/etc/sysconfig/clock'))
	{
		// CentOS
		$clock = @parse_ini_file ('/etc/sysconfig/clock');
		if ($clock !== FALSE && array_key_exists('ZONE', $clock)) $tz = $clock['ZONE'];
	}

	if (strlen($tz) <= 0) $tz = 'GMT';
	date_default_timezone_set ($tz);
}

/*
|---------------------------------------------------------------
| DEFINE APPLICATION CONSTANTS
|---------------------------------------------------------------
|
| EXT		- The file extension.  Typically ".php"
| SELF		- The name of THIS file (typically "index.php")
| FCPATH	- The full server path to THIS file
| BASEPATH	- The full server path to the "system" folder
| APPPATH	- The full server path to the "application" folder
|
*/
define('EXT', '.php');
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, '', __FILE__));
define('BASEPATH', $system_folder.'/');
define('APPPATH', $application_folder.'/');

/*
|---------------------------------------------------------------
| COMPULSORY HTTPS 
|---------------------------------------------------------------
*/
if (CODEPOT_HTTPS_COMPULSORY)
{
	// this option is not affected by X-Forwared-Proto or other similar headers.
	// this option mandates SSL over the direct connection to the origin server.
	// it doesn't care if the client is using SSL when the connection is relayed
	// by intermediate proxy servers.
	if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') 
	{
		/* force https except api calls */

		$tail = substr ($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
		//$tail = $_SERVER['PATH_INFO'];
		if (strncmp ($tail, "/api/", 5) != 0)
		{
			require_once dirname(__FILE__) . '/codepot/libraries/converter.php';
			$converter = new Converter ();
			$url = $converter->expand (CODEPOT_HTTPS_URL, $_SERVER);
			header("Location: $url");
			exit;
		}
	}
}

/*
|---------------------------------------------------------------
| LOAD THE FRONT CONTROLLER
|---------------------------------------------------------------
*/
require_once BASEPATH.'codeigniter/CodeIgniter'.EXT;

/* End of file index.php */
/* Location: ./index.php */
