<?php

/*
 * =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
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
 * =-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 */

/**
 * @package xmlnuke
 */
namespace Xmlnuke\Core\Processor;

use Xmlnuke\Util\FileUtil;

class XSLFilenameProcessor extends FilenameProcessor
{
	/**
	 *@param string $singlename
	 *@return string
	 *@desc
	 */
	public function __construct($singlename)
	{
		parent::__construct($singlename);
	}

	/**
	 *@param
	 *@return string
	 *@desc Implementing
	 */
	public function Extension()
	{
		return ".xsl";
	}

	/**
	 *@param
	 *@return string
	 *@desc Implementing
	 */
	public function SharedPath()
	{
		return FileUtil::Slash($this->_context->SharedRootPath(), "xsl");
	}

	/**
	 *@param
	 *@return string
	 *@desc Implementing
	 */
	public function PrivatePath()
	{
		return $this->_context->XslPath(false);
	}

	/**
	 *@param
	 *@return string
	 *@desc Implementing
	 */
	public function FullName($xml, $xsl, $languageId)
	{
		return parent::addLanguage($xsl,$languageId);
	}

	/**
	 *@param
	 *@return string
	 *@desc
	 */
	public function virtualFullName()
	{
		return $this->FullName("", $this->_singlename, $this->_languageid );
	}
}


?>