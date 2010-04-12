<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the "Database Connection"
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the "default" group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = "default";
$active_record = TRUE;

$db['default']['hostname'] = CODEPOT_DATABASE_HOSTNAME;
$db['default']['username'] = CODEPOT_DATABASE_USERNAME;
$db['default']['password'] = CODEPOT_DATABASE_PASSWORD;
$db['default']['database'] = CODEPOT_DATABASE_NAME;
$db['default']['dbdriver'] = CODEPOT_DATABASE_DRIVER;
$db['default']['dbprefix'] = CODEPOT_DATABASE_PREFIX;
$db['default']['pconnect'] = FALSE;
$db['default']['db_debug'] = FALSE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = "";
$db['default']['char_set'] = "utf8";
$db['default']['dbcollat'] = "utf8_general_ci";

$db['auth-mysql']['hostname'] = CODEPOT_AUTH_MYSQL_HOSTNAME;
$db['auth-mysql']['username'] = CODEPOT_AUTH_MYSQL_USERNAME;
$db['auth-mysql']['password'] = CODEPOT_AUTH_MYSQL_PASSWORD;
$db['auth-mysql']['database'] = CODEPOT_AUTH_MYSQL_NAME;
$db['auth-mysql']['dbdriver'] = "mysql";
$db['auth-mysql']['dbprefix'] = CODEPOT_AUTH_MYSQL_PREFIX;
$db['auth-mysql']['pconnect'] = FALSE;
$db['auth-mysql']['db_debug'] = FALSE;
$db['auth-mysql']['cache_on'] = FALSE;
$db['auth-mysql']['cachedir'] = "";
$db['auth-mysql']['char_set'] = "utf8";
$db['auth-mysql']['dbcollat'] = "utf8_general_ci";

/* End of file database.php */
/* Location: ./system/application/config/database.php */
