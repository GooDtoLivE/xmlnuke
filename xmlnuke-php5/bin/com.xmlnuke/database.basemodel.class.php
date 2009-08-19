<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification and Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
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

abstract class BaseModel
{

	protected $_propertyPattern = array("/(\w*)/", "$1");


	/**
	 * This setter enable changes in how XMLNuke will match a property (protected) into your Getter or Setter
	 * @param $pattern
	 * @param $replace
	 * @return void
	 */
	public function setPropertyPattern($pattern, $replace)
	{
		$this->_propertyPattern = array(($pattern[0]!="/" ? "/" : "") . $pattern . ($pattern[strlen($pattern)-1]!="/" ? "/" : ""), $replace);
	}
	
	/**
	 * Bind public string class parameters based on Request Get e Form
	 *
	 * @param SingleRow $sr
	 */
	public function bindSingleRow($sr)
	{
		if ($sr == null)
		{
			return;
		}
		
		$class = new ReflectionClass(get_class($this));
		
		$properties = $class->getProperties( ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC );
		
		if (!is_null($properties))
		{
			foreach ($properties as $prop)
			{
				$propName = $prop->getName();
				if ($propName == "_propertyPattern")
					continue;
				
				// Remove Prefix "_" from Property Name to find a value
				if ($propName[0] == "_")
				{
					$propName = substr($propName, 1);
				}
				
				// If exists value, set it;
				if ($sr->getField($propName) != "")
				{
					$method = new ReflectionMethod(get_class($this), "set" . ucfirst(preg_replace($this->_propertyPattern[0], $this->_propertyPattern[1], $propName)));
					$method->invokeArgs($this, array($sr->getField($propName)));
				}
			}
		}
	}
	

	/**
	 * Enter description here...
	 *
	 * @param IIterator $it
	 */
	public function bindIterator($it)
	{
		if ($it->hasNext()) 
		{
			$sr = $it->moveNext();
			$this->bindSingleRow($sr);
		}
	}

}
?>