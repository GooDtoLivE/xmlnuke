<?php
/*
*=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
*  Copyright:
*
*  XMLNuke: A Web Development Framework based on XML.
*
*  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
*  PHP Implementation: Joao Gilberto Magalhaes
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
namespace Xmlnuke\Core\AnyDataset;

class ArrayDataSet
{
	/**
	 * @var Array
	 */
	protected $_array;

	/**
	 * Constructor Method
	 *
	 * @param Array $array
	 * @return ArrayDataSet
	 */
	public function __construct($array, $fieldName="value")
	{

		$this->_array = array();

		if (!$array)
			return;
		
		if (is_array($array))
		{
			foreach ($array as $key => $value) 
			{
				if (is_array($value))
				{
					$this->_array[$key] = $value;
				}
				elseif (!is_object($value))
				{
					$this->_array[$key] = array($fieldName => $value);
				}
				else 
				{
					$result = array();
					$methods = get_class_methods($value);
					foreach ($methods as $method)
					{
						if (strpos($method, "get") == 0)
						{
							$met = new ReflectionMethod($value, $method);
							$result[substr($method,3)] = $met->invoke($value);
						}
					}
				}
			}
		}
		else
		{
			throw new UnexpectedValueException("You need to pass an array");
		}
	}

	/**
	 * Return a IIterator
	 *
	 * @return IIterator
	 */
	public function getIterator()
	{
		return new ArrayIIterator($this->_array);
	}
	
}
?>
