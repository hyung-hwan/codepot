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
