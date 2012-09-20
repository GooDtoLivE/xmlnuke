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
 * @package Xmlnuke
 */
class FixedTextFileDataSet
{
	protected $_context = null;

	protected $_source;

	protected $_fieldDefinition;

	protected $_sourceType;


	/**
	 * Text File Data Set
	 *
	 * @param Context $context
	 * @param string $source
	 * @param array $fieldDefinition
	 * @return TextFileDataSet
	 */
	public function __construct($context, $source, $fieldDefinition)
	{
		if (!is_array($fieldDefinition))
		{
			throw new InvalidArgumentException("You must define an array of field definition.");
		}
		if (strpos($source, "http://")===false)
		{
			if ($source instanceof FilenameProcessor)
			{
				$this->_source = $source->FullQualifiedNameAndPath;
			}
			else
			{
				$this->_source = $source;
			}
			if (!FileUtil::Exists($this->_source))
			{
				throw new NotFoundException("The specified file " . $this->_source . " does not exists")	;
			}

			$this->_sourceType = "FILE";
		}
		else
		{
			$this->_source = $source;
			$this->_sourceType = "HTTP";
		}


		$this->_context = $context;
		$this->_fieldDefinition = $fieldDefinition;
	}

	/**
	*@access public
	*@param string $sql
	*@param array $array
	*@return DBIterator
	*/
	public function getIterator()
	{
		//'/(http|ftp|https):\\/\\/((\\w|\\.)+)/i';

		$errno = null;
		$errstr = null;
		if ($this->_sourceType == "HTTP")
		{
			// Expression Regular:
			// [1]: http or ftp
			// [2]: Server name
			// [3]: Full Path
			$pat = "/(http|ftp|https):\/\/([\w+|\.]+)/i";
			$urlParts = preg_split($pat, $this->_source, -1,PREG_SPLIT_DELIM_CAPTURE);

			$handle = fsockopen($urlParts[2], 80, $errno, $errstr, 30);
			if (!$handle)
			{
				throw new DatasetException("TextFileDataSet Socket error: $errstr ($errno)");
			}
			else
			{
				$out = "GET " . $urlParts[4] . " HTTP/1.1\r\n";
				$out .= "Host: " . $urlParts[2] . "\r\n";
				$out .= "Connection: Close\r\n\r\n";

				fwrite($handle, $out);

				try
				{
					$it = new FixedTextFileIterator($this->_context, $handle, $this->_fieldDefinition);
					return $it;
				}
				catch (Exception $ex)
				{
					fclose($handle);
				}
			}
		}
		else
		{
			$handle = fopen($this->_source, "r");
			if (!$handle)
			{
				throw new DatasetException("TextFileDataSet File open error");
			}
			else
			{
				try
				{
					$it = new FixedTextFileIterator($this->_context, $handle, $this->_fieldDefinition);
					return $it;
				}
				catch (Exception $ex)
				{
					fclose($handle);
				}
			}
		}
	}

}
?>
