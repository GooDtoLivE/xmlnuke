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


namespace Xmlnuke\Core\Classes;

use Xmlnuke\Core\Engine\Context;
use Xmlnuke\Core\Enum\UIAlert;
use ByJG\Util\XmlUtil;

/**
 * @package xmlnuke
 */
class  XmlnukeProgressBar extends XmlnukeDocumentObject
{
	/**
	 * @var Context
	 */
	protected $_context;

	protected $_name = "";
	protected $_value = 0;

	/**
	 *
	 * @param string $context
	 * @param UIAlert $uialert
	 * @param string $title
	 */
	public function  __construct($name, $initialValue = 0)
	{
		//$this->_context = $context;
		$this->_name = $name;
		$this->_value = $initialValue;
	}


	public function generateObject($current)
	{
		$node = XmlUtil::CreateChild($current, "progressbar", "");
		XmlUtil::AddAttribute($node, "name", $this->_name);
		XmlUtil::AddAttribute($node, "value", $this->_value);
	}
}

?>