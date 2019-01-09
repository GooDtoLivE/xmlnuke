<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *  PHP Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
 *  Acknowledgments to: Roan Brasil Monteiro
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

use ByJG\Util\XmlUtil;


/**
 * @package xmlnuke
 */
class XmlnukeCalendar extends XmlnukeDocumentObject 
{
	/**
	* @var int
	*/
	protected $_month;
	/**
	* @var int
	*/
	protected $_year;
	/**
	* @var string
	*/
	protected $_title;
	
	/**
	* @var string[]
	*/
	protected $_events;
	
	/**
	* @param int month;
	* @param int year;
	*/
	public function	__construct($month = "", $year = "")
	{
		$today = getdate();
		$this->_month = $month != "" ? $month : $today['mon'];
		$this->_year = $year != "" ? $year : $today['year'];
		$this->_title = date('F/Y', mktime(0, 0, 0, $this->_month, 1, $this->_year));
		
		$this->_events = array();
	}
	
	public function getTitle()
	{
		return $this->_title;
	}
	
	public function setTitle($title)
	{
		$this->_title = $title;
	}
	
	public function getMonth()
	{
		return $this->_month;
	}
	
	public function getYear()
	{
		return $this->_year;
	}
	
	public function addCalendarEvent($calendarEvent)
	{
		$this->_events[] = $calendarEvent;
	}
	
	/// <summary>
	/// Contains specific instructions to generate all XML informations. This method is processed only one time. Usually is the last method processed.
	/// </summary>
	/// <param name="px">PageXml class</param>
	/// <param name="current">XmlNode where the XML will be created.</param>
	public function generateObject($current)
	{
		$nodeCalendar = XmlUtil::createChild($current, "calendar", "");
				
		XmlUtil::addAttribute($nodeCalendar, "name", "cal" . (rand(1000, 9999)) );
		XmlUtil::addAttribute($nodeCalendar, "month", $this->_month);
		XmlUtil::addAttribute($nodeCalendar, "year", $this->_year);
		XmlUtil::addAttribute($nodeCalendar, "title", $this->_title);
		
		foreach($this->_events as $key=>$calendarEvent)
		{
			$calendarEvent->generateObject($nodeCalendar);
		}
	}
	
}



class XmlnukeCalendarEvent extends XmlnukeCollection implements IXmlnukeDocumentObject
{
	protected  $_day;
	protected  $_type;
	protected  $_text;
	
	public function __construct($day, $type=-1, $text="")
	{
		$this->_day = $day;
		$this->_type = $type;
		$this->_text = $text;
	}
	
	/// <summary>
	/// Contains specific instructions to generate all XML informations. This method is processed only one time. Usually is the last method processed.
	/// </summary>
	/// <param name="px">PageXml class</param>
	/// <param name="current">XmlNode where the XML will be created.</param>
	public function generateObject($current)
	{
		$nodeCalendarEvent = XmlUtil::createChild($current, "event", $this->_text);
		
		XmlUtil::addAttribute($nodeCalendarEvent, "day", $this->_day);
		if ($this->_type > 0)
		{
			XmlUtil::addAttribute($nodeCalendarEvent, "type", ($this->_type-1) % 24 + 1);
		}
		
		$this->generatePage($nodeCalendarEvent);
	}
}

?>