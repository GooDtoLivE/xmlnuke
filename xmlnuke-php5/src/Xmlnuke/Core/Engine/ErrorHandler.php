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
 * @package xmlnuke
 */
namespace Xmlnuke\Core\Engine;

use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Xmlnuke\Core\Classes\BaseSingleton;
use Xmlnuke\Core\Enum\OutputData;

class ErrorHandler 
{
	use \ByJG\DesignPattern\Singleton;
	
	/**
	 *
	 * @var Run
	 */
	protected $_whoops = null;

	/**
	 *
	 * @var Handler
	 */
	protected $_handler = null;

	protected function __construct()
	{
		$this->_whoops = new Run();

		$output = Context::getInstance()->getOutputFormat();

		$this->setHandler($output);
	}

	/**
	 * Set the proper Error Handler based on the Output of the page
	 *
	 * @param OutputData $output
	 */
	public function setHandler($output)
	{
		$this->_whoops->popHandler();

		if ($output == OutputData::Json)
		{
			$this->_handler = new JsonResponseHandler();
		}
		else if ($output == OutputData::Xml)
		{
			$this->_handler = new XmlResponseHandler();
		}
		else
		{
			$this->_handler = new PrettyPageHandler();
			if (!Context::getInstance()->getDevelopmentStatus())
			{
				$this->_handler->addResourcePath(\WhoopsResources\Resource::getPath());
			}
		}

		$this->_whoops->pushHandler($this->_handler);
	}

	/**
	 * Set Whoops as the default error and exception handler used by PHP:
	 */
	public function register()
	{
		$this->_whoops->register();
	}

	/**
	 * Disable Whoops as the default error and exception handler used by PHP:
	 */
	public function unregister()
	{
		$this->_whoops->unregister();
	}

	/**
	 * Added extra information for debug purposes on the error handler screen
	 * 
	 * @param string $name
	 * @param value $value
	 */
	public function addExtraInfo($name, $value)
	{
		if (method_exists($this->_handler, 'addDataTable'))
			$this->_handler->addDataTable('Xmlnuke Debug', array($name => $value));
	}

}

