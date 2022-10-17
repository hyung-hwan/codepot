<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/* SVN FILE: $Id: lang_detect.php 135 2008-11-09 05:49:10Z Roland $ */
/*
|-----------------------------------------------------------------------------
| Configure language detect mechanism
|-----------------------------------------------------------------------------
|
*/

// Mapping browser's primary language id to supported language directory.

$config['lang_avail'] = array(
	'en'    => 'english',
	//'id'    => 'indonesian',
	'ko'    => 'korean'
);

// define the default language code. This language MUST be supported!
$config['lang_default'] = 'en';

// the selected language code. Is set by the language detection
$config['lang_selected'] = 'en';

// Language cookie parameters:
// 'lang_cookie_name' = the name you want for the cookie
// 'lang_expiration'  = the number of SECONDS you want the language to be
//                      remembered. by default 2 years. 
//                      Set zero for expiration when the browser is closed.
$config['lang_cookie_name'] = 'codepot_select_language';
$config['lang_expiration']  = 63072000;

?>
