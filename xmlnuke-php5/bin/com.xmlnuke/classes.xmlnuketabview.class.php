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
class XmlnukeTabView extends XmlnukeDocumentObject 
{
	protected $_tabs = array();
	
	protected $_tabDefault = "";
	
	/**
	*@desc Generate page, processing yours childs.
	*@param DOMNode $current
	*@return void
	*/
	public function __construct()
	{	
	
	}

	/**
	 * Add a Tab with content.
	 * @param string $title
	 * @param IXmlnukeDocumentObject $docobj
	 * @param bool $tabdefault
	 */
	public function addTabItem($title, $docobj, $tabdefault = false)
	{
		if (is_null($docobj) || !($docobj instanceof IXmlnukeDocumentObject)) 
		{
			throw new XmlNukeObjectException(853, "Object is null or not is IXmlnukeDocumentObject. Found object type: " . get_class($docobj));
		}
		$this->_tabs[] = array($title, "OBJ", $docobj);
		if ($tabdefault)
		{
			$this->_tabDefault = count($this->_tabs)-1;
		}
	}

	/**
	 *
	 * @param string $title
	 * @param string $url
	 * @param bool $tabdefault
	 */
	public function addTabAjax($title, $url, $tabdefault = false)
	{
		if (is_null($url) || !is_string($url))
		{
			throw new XmlNukeObjectException(853, "Object is null or not is URL.");
		}
		$this->_tabs[] = array($title, "URL", $url);
		if ($tabdefault)
		{
			$this->_tabDefault = count($this->_tabs)-1;
		}
	}

	public function generateObject($current)
	{
		$node = XmlUtil::CreateChild($current, "tabview", "");
		foreach ($this->_tabs as $key=>$value) 
		{
			$title = $value[0];
			$type = $value[1];
			$content = $value[2];

			$nodetab = XmlUtil::CreateChild($node, "tabitem", "");
			XmlUtil::AddAttribute($nodetab, "title", $title);
			if ($this->_tabDefault == $key)
			{
				XmlUtil::AddAttribute($nodetab, "default", "true");
			}
			if ($type == "OBJ")
			{
				$content->generateObject($nodetab);
			}
			else
			{
				XmlUtil::AddAttribute($nodetab, "url", $content);
			}
		}
	}
}

?>