<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *
 *  This file is part of XMLNuke project. Visit http://www.xmlnuke.com
 *  for more information.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 */

/**
 * Context class get data from HttpContext class and Config.php file and put all in propeties and methods to make easy access their contents
 * @package xmlnuke
 */
namespace Xmlnuke\Core\Engine;

use ByJG\AnyDataset\Enum\Relation;
use ByJG\AnyDataset\Model\DumpToArrayInterface;
use ByJG\AnyDataset\Repository\AnyDataset;
use ByJG\AnyDataset\Repository\IteratorFilter;
use ByJG\Authenticate\UserContext;
use ByJG\Authenticate\UsersAnyDataset;
use ByJG\Authenticate\UsersBase;
use ByJG\Authenticate\UsersDBDataset;
use ByJG\Cache\ICacheEngine;
use ByJG\Cache\NoCacheEngine;
use InvalidArgumentException;
use Negotiation\LanguageNegotiator;
use UnexpectedValueException;
use Xmlnuke\Core\Admin\IUsersBase;
use Xmlnuke\Core\Enum\OutputData;
use Xmlnuke\Core\Exception\UploadUtilException;
use Xmlnuke\Core\Locale\CultureInfo;
use Xmlnuke\Core\Locale\LocaleFactory;
use Xmlnuke\Core\Processor\AnydatasetFilenameProcessor;
use Xmlnuke\Core\Processor\AnydatasetSetupFilenameProcessor;
use Xmlnuke\Core\Processor\IProcessParameter;
use Xmlnuke\Core\Processor\ParamProcessor;
use Xmlnuke\Core\Processor\UploadFilenameProcessor;
use Xmlnuke\Util\Debug;
use Xmlnuke\Util\FileUtil;
use Xmlnuke\XmlFS\XmlnukeDB;

class Context implements DumpToArrayInterface
{
	use \ByJG\DesignPattern\Singleton {
		getInstance as traitGetInstance;
	}

	/**
	* @access private
	* @var string
	*/
	private $_XmlNukeVersion = "XMLNuke 3.x PHP5 Edition";
	/**
	* Name Value Collection
	* @access private
	* @var array
	*/
	private $_config = array();
	/**
	* @access private
	* @var string
	*/
	private $_xml = "";
	/**
	* @access private
	* @var string
	*/
	private $_xsl = "";
	/**
	* @access private
	* @var CultureInfo
	*/
	private $_lang = null;
	/**
	* @access private
	* @var string
	*/
	private $_site = "";
	/**
	 * @var string
	 */
	private $_module = "";
	/**
	* @access private
	* @var bool
	*/
	private $_reset = false;
	/**
	* @access private
	* @var bool
	*/
	private $_nocache = false;
	/**
	* @access private
	* @var XmlnukeDB
	*/
	private $_xmlnukedb;
	/**
	* @access private
	* @var string
	*/
	private $_appNameInMemory;
	/**
	* @access private
	* @var string
	*/
	private $_xmlnukepath = "";
	/**
	 * Debug erros in XMLNuke Error Module
	 *
	 * @var bool
	 */
	private $_debug = false;
//	/**
//	*It is necessary, because the Random value was returned the same value (because uses the same seed).
//	* @access private
//	* @var int
//	*/
//	private $_rnd;

	private $_contentType = array();

	protected $_PHP_SELF = "PHP_SELF";


	/**
	* Context construtor. Read data from HttpContext class and assign default values to main arguments (XML, XSL, SITE and LANG) if doesn't exists.
	* Process Config.php and put into NameValueCollection the make easy access it.
	* @access public
	* @return void
	*/
	protected function __construct()
	{
		// Empty to avoid recursion
	}


	/**
	 * Semaphore status
	 * @var int
	 */
	private $_status = 0;

	/**
	 *
	 * @return Context
	 */
	public static function getInstance()
	{
		$context = self::traitGetInstance();

		//while ($context->_status == 1)   # Semaphore --> 1 Setup not finished yet. Wait for it.
		//	sleep(1);

		if ($context->_status == 0)      # Not setup yet. Enter and 'lock' to avoid recursive loop
		{
			$context->_status = 1;       # Lock semaphore / Running

			$context->setupVariables();
			ModuleFactory::registerUserLibDir($context);

			$context->setLocale();

			// Adjusts to Run with XMLNukeDB
			$context->_appNameInMemory = "db_".strtolower($context->Language()->getName());

			$context->_xmlnukedb = new XmlnukeDB($context->XmlHashedDir(), $context->XmlPath(), strtolower($context->Language()->getName()));
			//$this->_xmlnukedb->loadIndex();

			if ($context->get("logout") != "")
			{
				   UserContext::getInstance()->registerLogout();
			}

			$context->_status = 2;       # Release Semaphore / All done.
		}

		return $context;
	}

	private function setupVariables()
	{
		$valuesConfig = \config::getValuesConfig();

		if (isset($_SERVER["SERVER_SOFTWARE"]) && strpos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false)
			$this->_PHP_SELF = 'DOCUMENT_URI';

		if (!is_array($valuesConfig))
			throw new InvalidArgumentException ("getValuesConfig() method expects an array. ");

		$this->AddCollectionToConfig($valuesConfig);

		$this->setXsl();
		$this->setXml();

		$this->_xmlnukepath = $this->get("xmlnuke.ROOTDIR");
		if (!file_exists($this->_xmlnukepath))
			throw new \Exception('the config xmlnuke.ROOTDIR not found');

		$this->_reset = ($this->getParameter("reset") != "");
		$this->_nocache = ($this->getParameter("nocache"));

		$this->AddCollectionToConfig($_REQUEST);
		$this->AddCollectionToConfig($_SERVER);
		$this->AddSessionToConfig($_SESSION);
		$this->AddCookieToConfig($_COOKIE);

		$queryString = (isset($_SERVER["QUERY_STRING"]) && !empty($_SERVER["QUERY_STRING"])) ? $_SERVER["QUERY_STRING"] . '&' : '';
		$this->addPairToConfig("SELFURLREAL", $_SERVER["SCRIPT_NAME"] . "?" . $queryString);
		$this->addPairToConfig("SELFURL", isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '/');
		$this->addPairToConfig("ROOTDIR", $this->_xmlnukepath."/".$this-> _site);
		$this->addPairToConfig("XMLNUKE", $this->_XmlNukeVersion);
		//$this->addPairToConfig("USERNAME", $this->authenticatedUser());
		//$this->addPairToConfig("USERID", $this->authenticatedUserId());
		$this->addPairToConfig("ENGINEEXTENSION", "php");

		$this->readCustomConfig();
		$this->_debug = $this->get("xmlnuke.DEBUG");
		if (gettype($this->_debug) != "boolean")
		{
			$this->_debug = ($this->_debug == "true");
		}

		$this->_module = $this->get("module");
		if ($this->_module != "")
		{
			$this->_module = htmlentities($this->_module);
			$this->set("module", $this->_module);
		}
	}



	protected $_xslCacheEngine = null;

	/**
	 *
	 * @return ICacheEngine
	 */
	public function getXSLCacheEngine()
	{
		if ($this->_xslCacheEngine == null)
		{
			$cache = $this->get("xmlnuke.XSLCACHE");
			if ($cache == "")
            {
                $this->_xslCacheEngine = new NoCacheEngine();
            }
            else
            {
                $this->_xslCacheEngine = new $cache();
            }
            $this->_xslCacheEngine->configKey = 'xmlnuke';
        }

		return $this->_xslCacheEngine;
	}

	/**
	 * The Debug mode is enabled by switching two options:
	 *  1 - Setting TRUE in Debug
	 *  2 - Setting the Query string "debug=true"
	 *
	 * @return bool
	 */
	public function getDebugStatus()
	{
		$configDebug = $this->_debug;
		if ($this->get("debug") == "")
		{
			$requestDebug = ($this->getSession("XMLNUKE_DEBUG_MODE") == "true");
		}
		elseif ($this->get("debug") == "true")
		{
			$this->setSession("XMLNUKE_DEBUG_MODE", "true");
			$requestDebug = true;
		}
		else
		{
			$this->removeSession("XMLNUKE_DEBUG_MODE");
			$requestDebug = false;
		}
		return ($configDebug && $requestDebug);
	}

	/**
	 * Set debug in module with true or false
	 *
	 * @param bool $debug
	 */
	public function setDebugStatus($debug)
	{
		$this->_debug = $debug;
	}

	public function getDevelopmentStatus()
	{
		return isset($this->_config['XMLNUKE.DEVELOPMENT']) ? $this->_config['XMLNUKE.DEVELOPMENT'] : false;
	}

	/**
	* Return a randon number
	* @access public
	* @param int $maxValue Number maximo to range values
	* @return int - Any number
	*/
	public function getRandomNumber($maxValue)
	{
		return rand(0, $maxValue);
	}


	/**
	* Look for a param name into the HttpContext Request already processed.
	* @access public
	* @param string $paramName Param to be looked for
	* @return string Return the param value if exists or an empty string if doesnt exists
	*/
	private function getParameter($paramName)
	{
		if (array_key_exists($paramName, $_REQUEST))
		{
			return str_replace(FileUtil::Slash().FileUtil::Slash(), FileUtil::Slash(), $_REQUEST[$paramName]);
		}
		else
		{
			return "";
		}
	}

	/**
	* @access public
	* @return string Return the current XML page argument
	*/
	public function getXml()
	{
		return $this->_xml;
	}

	/**
	* @access public
	* @param string $value Value to XML page argument
	* @return void
	*/
	public function setXml($value = null)
	{
		if (is_null($value))
		{
			$this->_xml = $this->getParameter("xml");
			if ($this->_xml == "")
			{
				$this->_xml = "home";
			}
			else
			{
				$this->_xml = htmlentities($this->_xml);
			}
		}
		else
			$this->_xml = $value;
	}
	/**
	* @access public
	* @return string Return the current XSL page argument
	*/
	public function getXsl()
	{
		return $this->_xsl;
	}
	/**
	* @access public
	* @param string $value Value to XSL page argument
	* @return void
	*/
	public function setXsl($value = null)
	{
		if (is_null($value))
		{
			$this->_xsl = $this->getParameter("xsl");
			if ($this->_xsl == "")
			{
				$this->_xsl = $this->get("xmlnuke.DEFAULTPAGE");
			}
			else
			{
				$this->_xsl = htmlentities($this->_xsl);
			}
		}
		else
			$this->_xsl = $value;
	}

	public function getModule()
	{
		return $this->_module;
	}

	/**
	* @access public
	* @desc Return the current Language page argument
	* @return CultureInfo
	*/
	public function Language()
	{
		return $this->_lang;
	}

	/**
	 *
	 * @param type $str
	 */
	public function setLocale($str = "")
	{
		$lang = strtolower($str == "" ? $this->getParameter("lang") : $str);

		$langAvail = array_keys($this->LanguagesAvailable());

		// Get the languages to check
		if (!empty($str))
		{
			$checkLang = array($str);
		}
		else if (!empty($lang))
		{
			$checkLang = array($lang);
		}
		else
		{
			$checkLang = $langAvail;
		}

		// Language Negotiator
		$langNeg = new LanguageNegotiator();
		$langAccepted = $langNeg->getBest(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : "en-us", $checkLang);
		if (is_null($langAccepted))
		{
			$currentLanguage = !empty($str) ? $str : (!empty($lang) ? $lang : $langAvail[0]);
		}
		else
		{
			$currentLanguage = strtolower($langAccepted->getValue());
		}

		// Get the correct language
		$selected = array_merge(
			preg_grep("/^" . $currentLanguage . "*/", $langAvail),
			preg_grep("/^" . substr($currentLanguage, 0, 2) . "*/", $langAvail)
		);
		if (count($selected) == 0)
		{
			$currentLanguage = $langAvail[0];
		}
		else
		{
			$currentLanguage = array_shift($selected);
		}

		$this->_lang = LocaleFactory::GetLocale($currentLanguage);
		$this->addPairToConfig("LANGUAGE", $this->_lang->getName());
		$this->addPairToConfig("LANGUAGENAME", $this->_lang->getLanguage());
	}

	/**
	* @access public
	* @return string Return the current Reset page argument
	*/
	public function getReset()
	{
		return $this->_reset;
	}
	/**
	* @access public
	* @param string $value Value to Reset page argument
	* @return void
	*/
	public function setReset($value)
	{
		$this->_reset = $value;
	}

	/**
	* @access public
	* @return string Return the current NoCache page argument
	*/
	public function getNoCache()
	{
		return $this->_nocache;
	}
	/**
	* @access public
	* @param string $value Value to NoCache page argument
	* @return void
	*/
	public function setNoCache($value)
	{
		$this->_nocache = $value;
	}

	/**
	* @access public
	* @return string Return the phisical directory from xmlnuke.ROOTDIR param from Config.php file.
	*/
	private function XmlNukePath()
	{
		if ($this->get("xmlnuke.USEABSOLUTEPATHSROOTDIR"))
		{
			return FileUtil::AdjustSlashes($this->_xmlnukepath).FileUtil::Slash();
		}
		else
		{
			return realpath($this->_xmlnukepath).FileUtil::Slash();
		}
	}

	/**
	* @desc Return the phisical directory from xmlnuke.ROOTDIR param from Web.Config file.
	* @return string
	*/
	public function SharedRootPath()
	{
		return $this->XmlNukePath() . "shared" . FileUtil::Slash();

	}

	/**
	* @access public
	* @return string Return the root directory where all sites are located.
	*/
	public function SiteRootPath()
	{
		return $this->XmlNukePath()."sites".FileUtil::Slash();
	}

	/**
	* @access public
	* @return string Return the root directory where the current site pages are located.
	*/
	public function CurrentSitePath($default = true)
	{
		$xmlnukeData = array_values($this->getXmlnukeData());

		if (!is_array($xmlnukeData))
			throw new \InvalidArgumentException('You need pass an array to xmlnuke.XMLNUKEDATA parameter');

		if ($default)
			return array_shift($xmlnukeData) . FileUtil::Slash();
		else
			return FileUtil::Slash($xmlnukeData);
	}

	/**
	* @access public
	* @return string Return the root directory where the current site XML pages are located.
	*/
	public function XmlPath($default = true)
	{
		return FileUtil::Slash($this->CurrentSitePath($default), 'xml');
	}
	/**
	* @access public
	* @return string Return the root directory where the current site XSL pages are located.
	*/
	public function XslPath($default = true)
	{
		return FileUtil::Slash($this->CurrentSitePath($default), 'xsl');
	}

	/**
	* @access public
	* @return string Return the root directory where the current site CACHE pages are located..
	*/
	public function CachePath()
	{
		return $this->CurrentSitePath()."cache".FileUtil::Slash();
	}

	/**
	* @access public
	* @return string Return the root directory where the current site OFFLINE pages are located.
	*/
	public function OfflinePath()
	{
		return $this->CurrentSitePath()."offline".FileUtil::Slash();
	}

	/**
	* @access public
	* @return string Return the virtual path from xmlnuke.URLXMLNUKEENGINE param from Config.php file.
	*/
	public function UrlXmlnukeEngine()
	{
		return $this->joinUrlBase($this->get("xmlnuke.URLXMLNUKEENGINE"));
	}

	/**
	* @access public
	* @return string Return the virtual path from xmlnuke.URLMODULE param from Config.php file.
	*/
	public function UrlModule()
	{
		return $this->joinUrlBase($this->get("xmlnuke.URLMODULE"));
	}

	/**
	* @access public
	* @return string Return the virtual path from xmlnuke.URLXMLNUKEADMIN param from Config.php file.
	*/
	public function UrlXmlNukeAdmin()
	{
		return $this->joinUrlBase($this->get("xmlnuke.URLXMLNUKEADMIN"));
	}

	public function UrlBase()
	{
		return $this->get("xmlnuke.URLBASE");
	}

	public function joinUrlBase($url)
	{
		$urlBase = $this->UrlBase();
		if ($urlBase != "")
		{
			if ($urlBase[strlen($urlBase)-1] != "/")
			{
				$urlBase .= "/";
			}

			if ($url[0] == "/")
			{
				$url = substr($url, 1);
			}
		}

		return $urlBase . $url;
	}


	/**
	* @access public
	* @param string $relativePath
	* @return string Return the absolute virtual path from relatives virtual paths.
	*/
	public function VirtualPathAbsolute($relativePath)
	{
		if (($relativePath[0] == "/") || (preg_match("/^https?:\/\//", $relativePath)))
		{
			return $relativePath;
		}

		$result = $_SERVER["SCRIPT_NAME"];
		$iPath = strrpos($result,"/");
		if ($iPath !== false)
		{
			$result = substr($result,0,$iPath);
		}
		if ($relativePath{0} == "~")
		{
			return $result.substr($relativePath,1);
		}
		else
		{
			return $result."/".$relativePath;
		}
	}

	/**
	* Access the Context collection and returns the value from a key.
	* @access public
	* @return string
	*/
	public function get($key, $persistent = false)
	{
		if ($persistent) {
			$origKey = $key;
			$key = "PVALUE.$key";
		}

		$key = strtoupper($key);

		if (isset($this->_config[$key]))
		{
			$value = $this->_config[$key];
			if ($value instanceof IProcessParameter)
			{
				return $value->getParameter();
			}
			else
			{
				return $value;
			}
		}
		elseif ($persistent)
		{
			if ($this->get($origKey) != "")
				$value = $this->get($origKey);
			else if ($this->getSession($key))
				$value = $this->getSession($key);
			else
				$value = "";

			$this->setSession($key, $value);
			$this->set($key, $value);

			return $value;
		}
		else
		{
			return "";
		}
	}

	public function Keys()
	{
		return array_keys($this->_config);
	}

	/**
	 *
	 * @param type $key
	 * @param type $value
	 */
	public function set($key, $value)
	{
		$this->addPairToConfig($key, $value);
	}

	public function rmset($key)
	{
		unset($this->_config[strtoupper($key)]);
	}

	protected $_langAvail = null;
	/**
	* @access public
	* @return array Return the languages available from xmlnuke.LANGUAGESAVAILABLE from Config.php file.
	*/
	public function LanguagesAvailable()
	{
		if (!is_null($this->_langAvail))
			return $this->_langAvail;

		$temp = $this->get("xmlnuke.LANGUAGESAVAILABLE");
		if (!is_array($temp))
			throw new \InvalidArgumentException('Config "xmlnuke.LANGUAGESAVAILABLE" requires an associative array');

		$this->_langAvail = array();

		foreach ($temp as $key=>$value)
			$this->_langAvail[strtolower($key)] = $value;

		return $this->_langAvail;
	}

	/**
	* @access public
	* @return string Return XmlNuke version.
	*/
	public function XmlNukeVersion()
	{
		return $this->_XmlNukeVersion;
	}

	protected $_xmlnukeData = null;

	/**
	 * @return array
	 */
	protected function getXmlnukeData()
	{
		if ($this->_xmlnukeData == null)
		{
			if ($this->get('xmlnuke.EXTERNALSITEDIR') != '')
				throw new UnexpectedValueException('The parameter xmlnuke.EXTERNALSITEDIR was deprecated. Use xmlnuke.XMLNUKEDATA instead.');

			$this->_xmlnukeData = $this->get("xmlnuke.XMLNUKEDATA");

			if (!is_array($this->_xmlnukeData))
				throw new \InvalidArgumentException('Config "xmlnuke.XMLNUKEDATA" requires an array');

			foreach($this->_xmlnukeData as $data)
			{
				if (!file_exists($data))
				{
					throw new \Exception("Path '$data' in xmlnuke.XMLNUKEDATA was not found");
				}
			}
		}
		return $this->_xmlnukeData;
	}

	/**
	* Get information about current context is authenticated.
	* @access public
	* @return bool Return true if authenticated; false otherwise.
	*/
	public function IsAuthenticated()
	{
		return UserContext::getInstance()->isAuthenticated();
	}

	/**
	* Get the authenticated user name
	* @access public
	* @return string The authenticated username if exists.
	*/
	public function authenticatedUser()
	{
		if ($this->IsAuthenticated())
		{
			$user = UserContext::getInstance()->userInfo();
            return $user[$this->getUsersDatabase()->getUserTable()->username];
		}
		else
		{
			return "";
		}
	}

	public function authenticatedUserId()
	{
		if ($this->IsAuthenticated())
		{
			$user = UserContext::getInstance()->userInfo();
            return $user[$this->getUsersDatabase()->getUserTable()->id];
		}
		else
		{
			return "";
		}
	}

	/**
	* Make login in XMLNuke Engine
	* @access public
	* @param string $user
	* @return void
	*/
	public function MakeLogin($user, $id)
	{
        $userObj = $this->getUsersDatabase()->getUserId($id);
        UserContext::getInstance()->registerLogin($userObj->toArray(), $this->getUsersDatabase());
	}

	/**
	* Make logout from XMLNuke Engine
	* @access public
	* @return void
	*/
	public function MakeLogout()
	{
		session_unset();
	}

	/**
	* Collection to be added
	* @access public
	* @param array $collection Collection to be added
	* @return void
	*/
	private function AddCollectionToConfig($collection)
	{
		foreach($collection as $key=>$value)
		{
			if ($key[0] == '/')
				$this->_virtualCommand = str_replace('_', '.', substr($key, 1));
			$this->addPairToConfig($key, $value);
		}
	}

	/**
	* Collection Session to be added
	* @access public
	* @param array $collection Session Collection to be added
	* @return void
	*/
	private function AddSessionToConfig($collection)
	{
		if (is_array($collection))
		{
			foreach($collection as $key => $value)
			{
				$this->addPairToConfig('session.' . $key, $value);
			}
		}
	}

	/**
	* Cookie Collection to be added
	* @access public
	* @param array $collection Cookie Collection to be added
	* @return void
	*/
	private function AddCookieToConfig($collection)
	{
		foreach($collection as $key => $value)
		{
			$this->addPairToConfig('cookie.' . $key, $value);
		}
	}

	/**
	* @access public
	* Add a single element to _config collection
	* @param string $key
	* @param array $value
	* @return void
	*/
	private function addPairToConfig($key, $value)
	{
		$this->_config[strtoupper($key)] = $value;
	}
	/**
	* @access public
	* @return XmlnukeDB
	*/
	public function getXMLDataBase()
	{
		return $this->_xmlnukedb;
	}
	/**
	* Nothing today.
	* @access public
	* @return void
	*/
	public function persistXMLDataBaseInMemory()
	{
		/*
		_context.Application.Lock();
		try
		{
		_context.Application.Set(_appNameInMemory, _xmlnukedb);
		}
		finally
		{
		_context.Application.UnLock();
		}
		*/
	}
	/**
	* Redirect to Url.
	* @access public
	* @param string $url
	* @return void
	*/
	public function redirectUrl($url)
	{
		$processor = new ParamProcessor();
		$url = $processor->GetFullLink($url);
		$url = str_replace("&amp;", "&", $url);

		// IIS running CGI mode has a bug related to POST and header(LOCATION) to the SAME script.
		// In this environment the behavior expected causes a loop to the same page
		// To reproduce this behavior comment the this and try use any processpage state class
		$isBugVersion = stristr(PHP_OS, "win") && stristr($this->get("GATEWAY_INTERFACE"), "cgi") && stristr($this->get("SERVER_SOFTWARE"), "iis");

		ob_clean();
		if (!$isBugVersion)
		{
			header("Location: " . $url);
		}

		echo "<html>";
		echo "<head>";
		echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"1;URL=$url\">";
		echo "<style type='text/css'> ";
		echo "	#logo{";
		echo "		width:32px;";
		echo "		height:32px;";
		echo "		top: 50%;";
		echo "		left: 50%;";
		echo "		margin-top: -16px;";
		echo "		margin-left: -16px;";
		echo "		position:absolute;";
		echo "} </style>";
		echo "</head>";
		echo "<h1></h1>";
		echo "<div id='logo'><a href='$url'><img src='common/imgs/ajax-loader.gif' border='0' title='If this page does not refresh, Click here' alt='If this page does not refresh, Click here' /></a></div>";
		echo "</html>";
		exit;
	}

	/**
	* @access public
	* @param string $name
	* @param string $value
	* @param int $expire (seconds from now)
	* @param int $path (directory into domain in which the cookie will be available on )
	* @return void
	* @desc Add a value in cookie
	*/
	public function addCookie($name, $value, $expire = null, $path = null, $domain = null)
	{
		if (!is_null($expire))
		{
			$expire = time() + $expire;
		}
		setcookie($name, $value, $expire, $path, $domain);
		$this->addPairToConfig("cookie." . $name, $value);
	}

	/**
	* @access public
	* @param string $name
	* @return void
	* @desc Remove a cookie
	*/
	public function removeCookie($name)
	{
		setcookie ($name, "", time() - 3600);
		unset($_COOKIE[$name]);
		unset($this->_config["cookie." . $name]);
	}

	/**
	* @access public
	* @param string $name
	* @return string
	* @desc Return the value of a cookie
	*/
	public function getCookie($name)
	{
		return $this->get("cookie." . $name);
	}

	/**
	* @access public
	* @param string $name
	* @param string $value
	* @return string
	* @desc Add a value in session
	*/
	public function setSession($name, $value)
	{
		$_SESSION[strtoupper($name)] = $value;
		$this->addPairToConfig("session." . strtoupper($name), $value);
	}

	/**
	* @access public
	* @param string $name
	* @return void
	* @desc Remove a value in this session
	*/
	public function removeSession($name)
	{
		unset($_SESSION[strtoupper($name)]);
		unset($this->_config["session." . strtoupper($name)]);
	}

	/**
	* @access public
	* @param string $name
	* @return string
	* @desc Return the a value in this session
	*/
	public function getSession($name)
	{
		return $this->get("session." . strtoupper($name));
	}


	/**
	* This method was created in intention to substitute the outhers three similars methods.
	* @access public
	* @param string $modulename
	* @param string $xsl
	* @param string $site
	* @param string $lang
	* @return string Return the bind Url
	*/
	public function bindModuleUrl($modulename, $xsl="", $lang="")
	{
		$queryStart = strpos($modulename, "?");
		$queryString = "";

		if ($queryStart!==false)
		{
			$queryString = "&" . substr($modulename, $queryStart+1);
			$modulename = substr($modulename, 0, $queryStart);
		}

		if (strpos($modulename, "module:") !== false)
		{
			$modulename = substr($modulename, 7);
		}

		if(empty($xsl))
		{
			if ($this->getXsl()=="index")
			{
				$xsl = 	$this->get("xmlnuke.DEFAULTPAGE");

			}
			else
			{
				$xsl = $this->getXsl();
			}
		}

		if(empty($lang))
			$lang = strtolower($this->Language()->getName());


		$fullLink = $this->get("xmlnuke.USEFULLPARAMETER");
		if (!$fullLink)
		{
			if ($xsl == $this->get("xmlnuke.DEFAULTPAGE"))
			{
				$xsl = "";
			}
			$array = array_keys($this->LanguagesAvailable());
			if ($lang == $array[0])
			{
				$lang = "";
			}
		}

		$url = $this->UrlModule()."?module=".$modulename;
		$url .= $queryString;
		if ($xsl != "")
		{
			$url .= "&xsl=".$xsl;
		}
		if ($lang != "")
		{
			$url .= "&lang=".$lang;
		}

		return $url;
	}


	/**
	* This method was created in intention to substitute the outhers three similars methods.
	* @access public
	* @param string $xml
	* @param string $xsl
	* @param string $site
	* @param string $lang
	* @return string Return the bind Url
	*/
	public function bindXmlnukeUrl($xml, $xsl="", $lang="")
	{
		if(empty($xsl))
			$xsl = $this->getXsl();

		if(empty($lang))
			$lang = strtolower($this->Language()->getName());

		return $this->UrlXmlnukeEngine()."?xml=".$xml."&xsl=".$xsl."&lang=".$lang;
	}

	/**
	* @access public
	* @param array $options
	* @return void
	*/
	public function updateCustomConfig($options)
	{
		//processor.AnydatasetFilenameProcessor
		$configFile = new AnydatasetFilenameProcessor("customconfig");
		$phyFile = $this->CurrentSitePath().$configFile->FullQualifiedName();
		//anydataset.AnyDataset
		$config = new AnyDataset($phyFile->FullQualifiedNameAndPath());
		//anydataset.AnyIterator
		$it = $config->getIterator();
		if ($it->hasNext())
		{
			$config->removeRow(0);
		}

		$config->appendRow();
		foreach( array_keys($options) as $key )
		{
			if (trim($options[$key]) != "")
			{
				$this->addPairToConfig($key, $options[$key]);
				$config->addField($key, $options[$key]);
			}
		}
		$config->Save($phyFile);
	}
	/**
	* @access public
	* @return void
	*/
	private function readCustomConfig()
	{
		$configFile = new AnydatasetFilenameProcessor("customconfig", $this);

		$config = new AnyDataset($configFile->FullQualifiedNameAndPath());
		$it = $config->getIterator(null);
		if ($it->hasNext())
		{
			//SingleRow
			$sr = $it->moveNext();
			$fieldNames = $sr->getFieldNames();
			foreach( $fieldNames as $field )
			{
				if ($sr->getField($field) != "")
				{
					$this->addPairToConfig($field, $sr->getField($field));
				}
			}
		}

	}

	/**
	* @desc Return path to XMLNuke root directory
	* @return string
	*/
	public function SystemRootPath()
	{
		return realpath(".") . FileUtil::Slash();
	}

	public function getXmlnukeURL()
	{
		$protocol = ($this->get("SERVER_PORT") == 443) ? "https://" : "http://";
		$url = $protocol . $this->get("HTTP_HOST") . dirname($this->get($this->_PHP_SELF));
		if ($url[strlen($url)-1] != '/')
		{
			$url .= "/";
		}
		return $url;
	}

	protected $_virtualCommand = null;

	/**
	 * Enable XMLNuke get parameters like:
	 *
	 * module.php/VIRTUALCOMMAND?a=1
	 *
	 * Virtual Command follow the SLASH and QUERY STRING follow the "?" char
	 */
	public function getVirtualCommand()
	{
		if ($this->_virtualCommand == null)
		{
			$script = $this->get($this->_PHP_SELF);
			$name = $this->get("SCRIPT_NAME");

			$command = substr($script, strlen($name) + 1);
			$this->_virtualCommand = $command;
		}

		return $this->_virtualCommand;
	}

	/**
	 * Return the Field name and the FILENAME for Saving files
	 *
	 * @param bool $systemArray
	 * @return array
	 */
	public function getUploadFileNames($systemArray=false)
	{
		if ($systemArray)
		{
			return $_FILES;
		}
		else
		{
			$ret = array();
			foreach($_FILES as $file => $property)
			{
				$ret[$file] = $property["name"];
			}
		}
		return $ret;
	}

	/**
	 * Process a document Upload
	 *
	 * @param UploadFilenameProcessor $filenameProcessor
	 * @param bool $useProcessorForName
	 * @param string|array $field Contain the filename properties (if Array, or $filename if string)
	 * @param array Valid Extensions
	 * @return Array Filename saved.
	 */
    public function processUpload($filenameProcessor, $useProcessorForName, $field = null)
    {
    	if (!($filenameProcessor instanceof UploadFilenameProcessor))
    	{
    		throw new UploadUtilException("processUpload must receive a UploadFilenameProcessor class");
    	}
    	else if (is_null($field))
    	{
    		$ret = array();
			foreach($_FILES as $file => $property)
			{
				$ret[] = $this->processUpload($filenameProcessor, $useProcessorForName, $property);
			}
			return $ret;
		}
		else if (is_string($field))
		{
			$ret = array();
			$ret[] = $this->processUpload($filenameProcessor, $useProcessorForName, $_FILES[$field]);
			return $ret;
		}
		else if (is_array($field))
		{
			if ($useProcessorForName)
			{
				$uploadfile = $filenameProcessor->FullQualifiedNameAndPath();
			}
			else
			{
				$uploadfile = $filenameProcessor->PathSuggested() . FileUtil::Slash() . $field["name"];
			}
			if (move_uploaded_file($field['tmp_name'], $uploadfile))
			{
				return $uploadfile;
			}
			else
			{
				$message = "Unknow error: " . $field['error'];
				switch ($field['error'])
				{
					case UPLOAD_ERR_CANT_WRITE:
						$message = "Can't write";
						break;
					case UPLOAD_ERR_EXTENSION:
						$message = "Extension is not permitted";
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$message = "Max post size reached";
						break;
					case UPLOAD_ERR_INI_SIZE:
						$message = "Max system size reached";
						break;
					case UPLOAD_ERR_NO_FILE:
						$message = "No file was uploaded";
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$message = "No temp dir";
						break;
					case UPLOAD_ERR_PARTIAL:
						$message = "The uploaded file was only partially uploaded";
						break;
				}
			    throw new UploadUtilException($message);
			}
		}
		else
		{
			throw new UploadUtilException("Something is wrong with Upload file.");
		}
    }

	/**
	 * Gets and array with the best content-type for the page.
	 * It checks the file "contenttype.anydata.xsl" if the property "xmlnuke.CHECKCONTENTTYPE" is true
	 *
	 * The returned array is:
	 * array(
	 *   "xsl" => "",
	 *   "content-type" => "",
	 *   "content-disposition" => "",
	 *   "extension" => ""
	 * )
	 *
	 * @return array
	 */
	public function getSuggestedContentType()
	{
		if (count($this->_contentType) == 0)
		{
			$this->_contentType["xsl"] = $this->getXsl();
			$this->_contentType["content-type"] = "text/html";
			$this->_contentType["content-disposition"] = "";
			$this->_contentType["extension"] = "";
			if ($this->get("xmlnuke.CHECKCONTENTTYPE"))
			{
				$filename = new AnydatasetFilenameProcessor("contenttype");
				$anydataset = new AnyDataset($filename->FullQualifiedNameAndPath());
				$itf = new IteratorFilter();
				$itf->addRelation("xsl",  Relation::EQUAL, $this->getXsl());
				$it = $anydataset->getIterator($itf);
				if ($it->hasNext())
				{
					$sr = $it->moveNext();
					$this->_contentType = $sr->getOriginalRawFormat();
				}
				else
				{
					$filename = new AnydatasetSetupFilenameProcessor("contenttype");
					$anydataset = new AnyDataset($filename->FullQualifiedNameAndPath());
					$itf = new IteratorFilter();
					$itf->addRelation("xsl",  Relation::EQUAL, $this->getXsl());
					$it = $anydataset->getIterator($itf);
					if ($it->hasNext())
					{
						$sr = $it->moveNext();
						$this->_contentType = $sr->getOriginalRawFormat();
					}
				}
			}
		}
		return ($this->_contentType);
	}

	public function Debug()
	{
		Debug::PrintValue($this->_config);
	}

    public function CacheHashedDir()
    {
        return (strtoupper($this->get("xmlnuke.CACHESTORAGEMETHOD")) == "HASHED");
    }

    public function XmlHashedDir()
    {
        return (strtoupper($this->get("xmlnuke.XMLSTORAGEMETHOD")) == "HASHED");
    }

    public function getPostVariables()
    {
    	return $_POST;
    }


    protected $__userdb;

    /**
     *
     * @return UsersBase
     */
    public function getUsersDatabase()
    {
		if ($this->__userdb == null)
		{
			$class = $this->get("xmlnuke.USERSCLASS");
			$conn = $this->get("xmlnuke.USERSDATABASE");
			if ($class != "")
			{
				$this->__userdb = new $class($conn);
				if (!($this->__userdb instanceof IUsersBase))
				{
					throw new InvalidArgumentException("Authentication class '$class' must implement IUsersBase interface");
				}
			}
			elseif ($conn == "")
			{
				$this->__userdb = new UsersAnyDataset();
			}
			else
			{
				$this->__userdb = new UsersDBDataset($conn, new \ByJG\Authenticate\UserTable('xmlnuke_users'), new \ByJG\Authenticate\CustomTable('xmlnuke_custom'));
			}
		}

		return $this->__userdb;
    }

	/**
	 *
	 * @return OutputData
	 */
	public function getOutputFormat()
	{
		if ($this->get("xmlnuke.OUTPUT_FORMAT") == "")
		{
			if ($this->get("raw")=="xml")
			{
				$output = OutputData::Xml;
			}
			elseif (($this->get("raw")=="json") || ($this->get("CONTENT_TYPE") == "application/json"))
			{
				$output = OutputData::Json;
			}
			else
			{
				$output = OutputData::Xslt;
			}

			$this->setOutputFormat($output);
		}

		return $this->get("xmlnuke.OUTPUT_FORMAT");
	}

	/**
	 * Warning! Try to not use this method. Instead set it on your method module getOutputFormat()
	 * @param OutputData $value
	 */
	public function setOutputFormat($value)
	{
		$this->set("xmlnuke.OUTPUT_FORMAT", $value);
	}

	public function WriteWarningMessage($message)
	{
		if ($this->getOutputFormat() == OutputData::Xslt)
			echo "<br/>\n<b>Warning: </b>" . $message . "\n<br/>";
	}

	/**
	 * Use this method to get the CLIENT REQUEST IP.
	 * Note that if you behing a Proxy, the variable REMOTE_ADDR will always have the same IP
	 * @return string
	 */
	public function getClientIp()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';

		return $ipaddress;
	}

	/**
	 * Use this method to get the SERVER NAME.
	 * @return string
	 */
	public function getServerName($port = false, $protocol = false)
	{
		$servername = '';
		if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME']))
			$servername = $_SERVER['SERVER_NAME'];
		else if(isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']))
			$servername = $_SERVER['HTTP_HOST'];
		else
			$servername = $_SERVER['SERVER_ADDR'];

		if ($port && isset($_SERVER['SERVER_PORT']))
			$servername .= ':' . $_SERVER['SERVER_PORT'];

		if ($protocol)
			$servername = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://") . $servername;

		return $servername;
	}

	public function toArray()
	{
		return $this->_config;
	}
}
