<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *  PHP5 Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
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
namespace Xmlnuke\Admin\Modules;

use Exception;
use Xmlnuke\Core\Admin\NewBaseAdminModule;
use ByJG\AnyDataset\Repository\DBDataSet;
use Xmlnuke\Core\Classes\CrudField;
use Xmlnuke\Core\Classes\CrudFieldCollection;
use Xmlnuke\Core\Classes\XmlAnchorCollection;
use Xmlnuke\Core\Classes\XmlBlockCollection;
use Xmlnuke\Core\Classes\XmlnukeCrudAnydata;
use Xmlnuke\Core\Classes\XmlnukeText;
use Xmlnuke\Core\Classes\XmlParagraphCollection;
use Xmlnuke\Core\Enum\AccessLevel;
use Xmlnuke\Core\Enum\BlockPosition;
use Xmlnuke\Core\Enum\CustomButtons;
use Xmlnuke\Core\Enum\INPUTTYPE;
use Xmlnuke\Core\Enum\MultipleSelectType;
use Xmlnuke\Core\Enum\XmlInputObjectType;
use Xmlnuke\Core\Processor\AnydatasetFilenameProcessor;

class ManageDBConn extends NewBaseAdminModule
{
	public function ManageDBConn()
	{
	}

	public function useCache()
	{
		return false;
	}

	public function getAccessLevel()
	{
		return AccessLevel::OnlyRole;
	}

	public function getRole()
	{
		return array("MANAGER");
	}

	//Returns: classes.PageXml
	public function CreatePage()
	{
		parent::CreatePage();

		$this->myWords = $this->WordCollection();
		$this->setTitlePage($this->myWords->Value("TITLE"));
		$this->setHelp($this->myWords->Value("DESCRIPTION"));

		$block = new XmlBlockCollection($this->myWords->Value("BLOCK_TITLE"), BlockPosition::Center);

		$anydatafile = new AnydatasetFilenameProcessor("_db");

		if ($this->_action != "test")
		{

			$processfields = new CrudFieldCollection();

			$field = new CrudField();
			$field->fieldName = "dbname";
			$field->editable = true;
			$field->key = true;
			$field->visibleInList = true;
			$field->dataType = INPUTTYPE::TEXT;
			$field->fieldXmlInput = XmlInputObjectType::TEXTBOX;
			$field->fieldCaption = $this->myWords->Value("DBNAME");
			$field->size = 20;
			$field->maxLength = 20;
			$field->required = true;
			$field->newColumn = true;
			$processfields->addCrudField($field);

			$field = new CrudField();
			$field->fieldName = "dbtype";
			$field->editable = true;
			$field->key = false;
			$field->visibleInList = true;
			$field->dataType = INPUTTYPE::TEXT;
			$field->fieldXmlInput = XmlInputObjectType::SELECTLIST;
			$field->fieldCaption = $this->myWords->Value("DBTYPE");
			$field->size = 15;
			$field->maxLength = 15;
			$field->required = true;
			$field->arraySelectList = array("dsn"=>"dsn (use it)",
				"literal"=>"PDO Literal connection string",
				"dblib"=>"FreeTDS / Microsoft SQL Server / Sybase",
				"firebird"=>"Firebird/Interbase 6",
				"informix"=>"IBM Informix Dynamic Server",
				"mysql"=>"MySQL 3.x/4.x/5.x",
				"oci"=>"Oracle Call Interface",
				"odbc"=>"ODBC v3 (IBM DB2, unixODBC and win32 ODBC)",
				"pgsql"=>"PostgreSQL",
				"sqlite"=>"SQL Lite");
			$field->defaultValue = "dsn";
			$field->newColumn = true;
			$processfields->addCrudField($field);

			$field = new CrudField();
			$field->fieldName = "dbconnectionstring";
			$field->editable = true;
			$field->key = false;
			$field->visibleInList = true;
			$field->dataType = INPUTTYPE::TEXT;
			$field->fieldXmlInput = XmlInputObjectType::TEXTBOX;
			$field->fieldCaption = $this->myWords->Value("DBCONNECTIONSTRING");
			$field->size = 50;
			$field->maxLength = 200;
			$field->required = true;
			$field->defaultValue = "adodriver://username:password@server/datasource?persist";
			$field->newColumn = true;
			$processfields->addCrudField($field);

			$buttons = new CustomButtons();
			$buttons->action = "test";
			$buttons->alternateText = $this->myWords->Value("TESTALTERNATETEXT");
			$buttons->enabled = true;
			$buttons->icon = "common/editlist/ic_selecionar.gif";
			$buttons->message = $this->myWords->Value("TESTMESSAGETEXT");
			$buttons->multiple = MultipleSelectType::ONLYONE;

			$crud =
				new XmlnukeCrudAnydata(
					$this->_context,
					$processfields,
					$this->myWords->Value("AVAILABLELANGUAGES"),
					"module:Xmlnuke.Admin.ManageDBConn",
					array($buttons),
					$anydatafile
				);

			$block->addXmlnukeObject($crud);

			$p = new XmlParagraphCollection();
			$p->addXmlnukeObject(new XmlnukeText($this->myWords->Value("NOTE"), true));
			$p->addXmlnukeObject(new XmlnukeText($this->myWords->Value("PHPNOTE")));
			$block->addXmlnukeObject($p);
		}
		else
		{
			$p = new XmlParagraphCollection();
			$db = $this->_context->get("valueid");
			if ($db == "")
			{
				$p->addXmlnukeObject(new XmlnukeText($this->myWords->Value("ERRORDBEMPTY")));
			}
			else
			{
				try
				{
					$dbdataset = new DBDataSet($db, $this->_context);
					$dbdataset->TestConnection();
					$p->addXmlnukeObject(new XmlnukeText($this->myWords->Value("SEEMSOK")));
				}
				catch (Exception $ex)
				{
					$p->addXmlnukeObject(new XmlnukeText($this->myWords->Value("GOTERROR", $ex->getMessage())));
				}
			}
			$block->addXmlnukeObject($p);
			$p = new XmlParagraphCollection();
			$href = new XmlAnchorCollection("module:Xmlnuke.Admin.ManageDBConn");
			$href->addXmlnukeObject(new XmlnukeText($this->myWords->Value("GOBACK")));
			$p->addXmlnukeObject($href);
			$block->addXmlnukeObject($p);
		}

		$this->defaultXmlnukeDocument->addXmlnukeObject($block);
		return $this->defaultXmlnukeDocument->generatePage();
	}

}
?>