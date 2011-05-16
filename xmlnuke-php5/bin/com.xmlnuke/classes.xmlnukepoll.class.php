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
class XmlnukePoll extends XmlnukeDocumentObject
{
	/**
	*@var Context
	*/
	private $_context;
	/**
	*@var string
	*/
	private $_url;
	/**
	*@var string
	*/
	private $_poll;
	/**
	*@var string
	*/
	private $_lang;
	/**
	*@var bool
	*/
	private $_processed;
	/**
	*@var LanguageCollection
	*/
	private $_myWords;
	/**
	 * @var AnyDataSet
	 */
	private $_anyPoll;
	/**
	 * @var AnyDataSet
	 */
	private $_anyAnswer;

	private $_width;
	private $_height;

	/* POLL CONFIG */
	protected $_tblpoll = "";
	protected $_tblanswer = "";
	protected $_tbllastip = "";
	protected $_isdb = false;
	protected $_connection = "";
	protected $_error = false;

	/**
	 * Initialize a poll context
	 * Use the method processData to process data.
	 *
	 * @param Context $context
	 * @param string $urlProcess
	 * @param string $poll
	 * @param string $lang
	 * @return XmlnukePoll
	 */
	public function __construct($context, $urlProcess, $poll, $lang = "")
	{
		$this->_context = $context;
		$this->_url = $urlProcess;
		$this->_poll = $poll;
		if ($lang != "")
		{
			$this->_lang = $lang;
		}
		else
		{
			$this->_lang = $this->_context->Language()->getName();
		}
		$this->_processed = false;
		$this->getPollConfig();
		$this->_myWords = LanguageFactory::GetLanguageCollection($this->_context, LanguageFileTypes::OBJECT, __FILE__);
	}

	/**
	 * Get informations about WHERE I need to store poll data
	 *
	 */
	protected function getPollConfig()
	{
		$pollfile = new AnydatasetFilenameProcessor("_poll");
		$anyconfig = new AnyDataSet($pollfile);

		$it = $anyconfig->getIterator();
		if ($it->hasNext())
		{
			$sr = $it->moveNext();
			$this->_isdb = $sr->getField("dbname") != "-anydata-";
			$this->_connection = $sr->getField("dbname");
			$this->_tblanswer = $sr->getField("tbl_answer");
			$this->_tblpoll = $sr->getField("tbl_poll");
			$this->_tbllastip = $sr->getField("tbl_lastip");
		}
		else
		{
			$this->_error = true;
		}
	}

	/**
	 * Get AnydataSet Poll information
	 *
	 */
	protected function getAnyData()
	{
		$filepoll = new AnydatasetFilenameProcessor("poll_list");
		$this->_anyPoll = new AnyDataSet($filepoll);
		$fileanswer = new AnydatasetFilenameProcessor("poll_" . $this->_poll . "_" . $this->_lang);
		$this->_anyAnswer = new AnyDataSet($fileanswer);
	}

	/**
	 * Process Vote. Note that the system ONLY process the vote if there is no another equal IP.
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function processVote($width=450, $height=400)
	{
		if ($this->_context->ContextValue("xcrt") == "") // TRICK CODE HERE. THIS VALUE IS GENERATED BY CHART.
														 // WE HAVE TO USE THIS TO AVOID TWO processVote() calling.
		{
			// Is The Post values needed to process vote exists?
			if (($this->_context->ContextValue("xmlnuke_poll") != "") && ($this->_context->ContextValue("xmlnuke_polllang") != "") && ($this->_context->ContextValue("xmlnuke_pollanswer") != ""))
			{
				$this->_poll = $this->_context->ContextValue("xmlnuke_poll");
				$this->_lang = $this->_context->ContextValue("xmlnuke_polllang");

				// Try to get the Last IP who vote here.
				$ok = false;
				$filelastip = new AnydatasetFilenameProcessor("poll_lastip_" . $this->_poll);
				$anylastip = new AnyDataSet($filelastip);
				$itlastip = $anylastip->getIterator();
				if ($itlastip->hasNext())
				{
					$sr = $itlastip->moveNext();
					$arr = $sr->getFieldArray("ip");

					// Is The maximum amount of unique IP reached?
					// If true, I need to remove the excess.
					if (sizeof($arr) > 20)
					{
						array_shift($arr);

						$anylastip->removeRow(0);
						$anylastip->appendRow();
						foreach ($arr as $value)
						{
							$anylastip->addField("ip", $value);
						}
						$anylastip->Save();
					}

					// Is This a New IP?
					if (array_search($this->_context->ContextValue("REMOTE_ADDR"), $arr) === false)
					{
						$ok = true;
						$sr->addField("ip", $this->_context->ContextValue("REMOTE_ADDR"));
						$anylastip->Save();
					}
				}
				// OK. First time here. I need to add the IP.
				else
				{
					$ok = true;
					$anylastip->appendRow();
					$anylastip->addField("ip", $this->_context->ContextValue("REMOTE_ADDR"));
					$anylastip->Save();
				}

				// Is My IP Unique? If true I can process the vote.
				// Note if the poll name, lang and code are wrong the system does not do anything.
				if ($ok)
				{
					// Get Data
					$itf = new IteratorFilter();
					$itf->addRelation("name", Relation::Equal, $this->_poll);
					$itf->addRelation("lang", Relation::Equal, $this->_lang);
					$itf->addRelation("code", Relation::Equal, $this->_context->ContextValue("xmlnuke_pollanswer"));
					if ($this->_isdb)
					{
						$dbdata = new DBDataSet($this->_connection, $this->_context);
						$param = array();
						$sql = $itf->getSql($this->_tblanswer, $param); // Use only to get Where clause
						$i = strpos($sql, $this->_tblanswer);
						$sql = "update " . $this->_tblanswer . " set " .
							" votes = votes + 1 " .
							substr($sql, $i + strlen($this->_tblanswer) + 1);
						$dbdata->execSQL($sql, $param);
					}
					else
					{
						$this->getAnyData();
						$itAnswer = $this->_anyAnswer->getIterator($itf);
						if ($itAnswer->hasNext())
						{
							$sr = $itAnswer->moveNext();
							$sr->setField("votes", intval($sr->getField("votes"))+1);
							$this->_anyAnswer->Save();
						}
					}
				}

				$this->_processed = true;
			}
		}
		else
		{
			$this->_processed = true;
		}
		$this->_width = $width;
		$this->_height = $height;
	}

	public function generateObject($current)
	{
		// Is there some error?
		if ($this->_error)
		{
			$nodeWorking = XmlUtil::CreateChild($current, "poll");
			XmlUtil::CreateChild($nodeWorking, "error", $this->_myWords->Value("ERROR_POLLNOTSETUP"));
		}
		else
		{
			// Get Data to SHOW the answers OR chart.
			$itf = new IteratorFilter();
			$itf->addRelation("name", Relation::Equal, $this->_poll);
			$itf->addRelation("lang", Relation::Equal, $this->_lang);
			if ($this->_isdb)
			{
				$dbdata = new DBDataSet($this->_connection, $this->_context);
				$param = array();
				$sql = $itf->getSql($this->_tblpoll, $param);
				$itPoll = $dbdata->getIterator($sql, $param);
				$param = array();
				$sql = $itf->getSql($this->_tblanswer, $param);
				$itAnswer = $dbdata->getIterator($sql, $param);
			}
			else
			{
				$this->getAnyData();
				$itPoll = $this->_anyPoll->getIterator($itf);
				$itAnswer = $this->_anyAnswer->getIterator($itf);
			}

			// Show the answers if not was called the method processVote()
			if (!$this->_processed)
			{
				$nodeWorking = XmlUtil::CreateChild($current, "poll");
				XmlUtil::AddAttribute($nodeWorking, "url", $this->_url);
				XmlUtil::AddAttribute($nodeWorking, "name", $this->_poll);
				XmlUtil::AddAttribute($nodeWorking, "lang", $this->_lang);

				// Show Data Only if Poll is active
				if ($itPoll->hasNext())
				{
					$sr = $itPoll->moveNext();

					if ($sr->getField("active") == "Y")
					{
						XmlUtil::AddAttribute($nodeWorking, "active", "true");
						XmlUtil::AddAttribute($nodeWorking, "sendbtn", $this->_myWords->Value("SENDBTN"));
						XmlUtil::CreateChild($nodeWorking, "question", $sr->getField("question"));

						while ($itAnswer->hasNext())
						{
							$sr = $itAnswer->moveNext();
							$nodeanswer = XmlUtil::CreateChild($nodeWorking, "answer", $sr->getField("answer"));
							XmlUtil::AddAttribute($nodeanswer, "code", $sr->getField("code"));
						}
					}
					else
					{
						XmlUtil::AddAttribute($nodeWorking, "sendbtn", $this->_myWords->Value("VIEWRESULTSBTN"));
						XmlUtil::CreateChild($nodeWorking, "question", $sr->getField("question") . " - " . $this->_myWords->Value("POLLENDED"));
					}
				}
				else
				{
					XmlUtil::CreateChild($nodeWorking, "error", $this->_myWords->Value("ERROR_POLLEMPTY"));
				}
			}
			// Show the chart if poll is processed.
			else
			{
				$srPoll = $itPoll->moveNext();

				if ($srPoll->getField("showresults")=="Y")
				{
					$fldGrp = array();
					$anyGraph = new AnyDataSet();
					$anyGraph->appendRow();
					$anyGraph->addField("data", $title);
					while ($itAnswer->hasNext())
					{
						$sr = $itAnswer->moveNext();
						$anyGraph->addField("qty_" . $sr->getField("code"), $sr->getField("votes"));
						$fldGrp["qty_" . $sr->getField("code")] = $sr->getField("short");
					}
					$itGraphInv = $anyGraph->getIterator();

					$chart = new XmlChart($this->_context, "Title", $itGraphInv, ChartOutput::Flash, ChartSeriesFormat::Column);
					$chart->setFrame($this->_width, $this->_height);
					$chart->setLegend("data", "#444444", "#FFFFFF");
					$chart->setAreaColor("#000000", "#ddddee");
					foreach ($fldGrp as $key=>$value)
					{
						$chart->addSeries($key, $value, '#000000');
					}

					$chart->generateObject($current);
				}
				else
				{
					if ($srPoll->getField("active")=="Y")
					{
						$txt = new XmlnukeText($this->_myWords->Value("VOTECOMPUTED"), true);
					}
					else
					{
						$txt = new XmlnukeText($this->_myWords->Value("CANNOTSHOWRESULTS"), true);
					}
					$txt->generateObject($current);
				}
			}
		}
	}

}
?>
