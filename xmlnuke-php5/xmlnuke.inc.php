<?php
/**
 * Contains all commands necessary to run XMLNuke. 
 * 
 * To run the XMLNuke as a Web site you need to have the file xmlnuke.php. 
 * 
 * You can use the XMLNuke as a library. In this case, you have to include in your PHP files this files. 
 * 
 * @package xmlnuke
 */

## Profiling Tool ########
# xdebug_enable();
##########################

ob_start();
session_start();

set_include_path(get_include_path() . PATH_SEPARATOR . '.');
// Solve problem Page Expired when Back button was selected
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: post-check=0, pre-check=0",false);
session_cache_limiter("must-revalidate");

header("Content-Type: text/html; charset=utf-8");

define("SESSION_XMLNUKE_AUTHUSER", "SESSION_XMLNUKE_AUTHUSER");
define("SESSION_XMLNUKE_AUTHUSERID", "SESSION_XMLNUKE_AUTHUSERID");
define("SESSION_XMLNUKE_USERCONTEXT", "SESSION_XMLNUKE_USERCONTEXT");

// PHP_VERSION_ID is available as of PHP 5.2.7, if our 
// version is lower than that, then emulate it
if(!defined('PHP_VERSION_ID'))
{
    $version = explode('.',PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	define('PHP_MAJOR_VERSION',     $version[0]);
    define('PHP_MINOR_VERSION',     $version[1]);
    define('PHP_RELEASE_VERSION',     $version[2]);    
}

/* This main of engine */
if (!file_exists("config.inc.php"))
	die("<b>Fatal error:</b> Could not find required 'config.inc.php'");

require_once("config.inc.php");
if (!class_exists('config')) { header("Location: check_install.php"); exit(); }

if (!defined("AUTOLOAD"))
{
	/* Base required files. The most of another required files is in module.basemodule.class.php */
	require_once(PHPXMLNUKEDIR . "src/enum.inc.php");
	require_once(PHPXMLNUKEDIR . "src/processor.inc.php");
	require_once(PHPXMLNUKEDIR . "src/engine.inc.php");
	require_once(PHPXMLNUKEDIR . "src/xmlnukedb.inc.php");
	require_once(PHPXMLNUKEDIR . "src/anydataset.inc.php");
	require_once(PHPXMLNUKEDIR . "src/international.inc.php");

	require_once(PHPXMLNUKEDIR . "src/classes.inc.php");
	require_once(PHPXMLNUKEDIR . "src/database.inc.php");
	require_once(PHPXMLNUKEDIR . "src/util.inc.php");
	require_once(PHPXMLNUKEDIR . "src/admin.inc.php");

	require_once(PHPXMLNUKEDIR . "src/oauth.inc.php");

	require_once(PHPXMLNUKEDIR . "src/modules.inc.php");
}
else
{
	// Activate AutoLoad
	require_once PHPXMLNUKEDIR . "src/com.xmlnuke/engine/engine.autoload.class.php";
	$autoload = new AutoLoad();

	// Start Generic Classes
	require_once PHPXMLNUKEDIR . "src/com.xmlnuke/enum/enum.class.php";
	require_once PHPXMLNUKEDIR . "src/util/util.conversion.class.php";
	require_once PHPXMLNUKEDIR . "src/util/util.exceptions.class.php";

}

/* Fix bad things in PHP */
fixbadthingsinphp();

/* Initialize default XMLNuke CONTEXT */
$context = Context::getInstance();



#################################################################################
###
### Global functions for adjust PHP environment and fixes some bad behaviors
###
#################################################################################


/**
 * Fix some bad behaviors in PHP :(
 */
function fixbadthingsinphp()
{
	// http://br.php.net/manual/pt_BR/ref.info.php#ini.magic-quotes-runtime
	/* if magic_quotes_runtime is enabled all functions will return a backslash before a quote */
	if(get_magic_quotes_runtime())
	{
    	set_magic_quotes_runtime(0);
	}

	if (get_magic_quotes_gpc())
	{
		// $_REQUEST have $_GET, $_POST and $_COOKIE in one variable
		$_REQUEST = array_map("remove_magicquotes", $_REQUEST);
	}

	if (function_exists("ini_get"))
	{
		if(!ini_get("display_errors"))
		{
			ini_set("display_errors", 1);
		}

		if(ini_get("magic_quotes_sybase"))
		{
			ini_set("magic_quotes_sybase", 0);
		}

		// Fixed register_globals behavior!!
		if (ini_get("register_globals"))
		{
			foreach($GLOBALS as $s_variable_name => $m_variable_value)
			{
				if (!in_array($s_variable_name, array("GLOBALS", "argv", "argc", "_FILES", "_COOKIE", "_POST", "_GET", "_REQUEST", "_SERVER", "_ENV", "_SESSION", "s_variable_name", "m_variable_value")))
				{
					unset($GLOBALS[$s_variable_name]);
				}
			}
			unset($GLOBALS["s_variable_name"]);
			unset($GLOBALS["m_variable_value"]);
			echo "<br/><b>Warning</b>: I suppose you do not need enter here. Please deactivate \"register_globals\" directive<br/>";
		}
	}

	error_reporting(E_ALL ^ E_NOTICE);
	//error_reporting(E_STRICT);
}

function remove_magicquotes(&$var)
{
	return is_array($var) ? array_map("remove_magicquotes", $var) : stripslashes($var);
}

?>
