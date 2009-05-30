<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *  PHP Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
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
*@package com.xmlnuke
*@subpackage xmlnukeobject
* This class will be define a single XML InputImageValidate.
* This XML need be transformed to generate a PNG Image with a random number and chars/
* The method Validate will test if a sequence is typed corretly or not.
*/
class XmlInputImageValidate extends XmlnukeDocumentObject 
{
	protected $_caption;
	protected $_chars = "5";
	protected $_challengeQuestion = "1";
	
	/**
	*@desc XmlBlockCollection construction
	*@param string $title
	*@param BlockPosition $position
	*/
	public function XmlInputImageValidate($caption)
	{
		$this->_caption = $caption;
	}
	
	public function UseChallengeQuestion($b)
	{
		$this->_challengeQuestion = ($b ? "1" : "0");
	}
	
	public function NumberOfChars($c)
	{
		$this->_chars = $c;
	}

	/**
	*@desc Generate page, processing yours childs.
	*@param DOMNode $current
	*@return void
	*/
	public function generateObject($current)
	{
		$nodeWorking = XmlUtil::CreateChild($current, "imagevalidate", "");
		XmlUtil::AddAttribute($nodeWorking, "caption", $this->_caption);
		XmlUtil::AddAttribute($nodeWorking, "challengequestion", $this->_challengeQuestion);
		XmlUtil::AddAttribute($nodeWorking, "chars", $this->_chars);
	}
	
	/**
	 * Validate if the text type by the user matchs with the text generated by the 
	 * XmlInputImageValidate is correct or not
	 *
	 * @param Context $context
	 * @param string $text
	 */
	public static function validateText($context)
	{
		require_once(PHPXMLNUKEDIR . "bin/modules/captcha/captcha.class.php");
		return Captcha::TextIsValid($context->ContextValue("imagevalidate"));
	}

}

?>