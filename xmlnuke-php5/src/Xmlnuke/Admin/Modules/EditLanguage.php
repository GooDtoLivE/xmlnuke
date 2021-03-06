<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *  CSharp Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
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

use Xmlnuke\Core\Admin\NewBaseAdminModule;
use ByJG\AnyDataset\Repository\AnyDataset;
use ByJG\AnyDataset\Repository\ArrayDataset;
use ByJG\AnyDataset\Repository\IteratorInterface;
use Xmlnuke\Core\Classes\CrudField;
use Xmlnuke\Core\Classes\CrudFieldCollection;
use Xmlnuke\Core\Classes\EditListField;
use Xmlnuke\Core\Classes\XmlBlockCollection;
use Xmlnuke\Core\Classes\XmlEditList;
use Xmlnuke\Core\Classes\XmlFormCollection;
use Xmlnuke\Core\Classes\XmlInputButtons;
use Xmlnuke\Core\Classes\XmlInputHidden;
use Xmlnuke\Core\Classes\XmlInputMemo;
use Xmlnuke\Core\Classes\XmlInputTextBox;
use Xmlnuke\Core\Classes\XmlnukeCrudAnydata;
use Xmlnuke\Core\Enum\AccessLevel;
use Xmlnuke\Core\Enum\BlockPosition;
use Xmlnuke\Core\Enum\ModuleAction;
use Xmlnuke\Core\Locale\LanguageCollection;
use Xmlnuke\Core\Processor\AnydatasetLangFilenameProcessor;
use Xmlnuke\Core\Processor\FilenameProcessor;
use Xmlnuke\Core\Processor\ForceFilenameLocation;
use Xmlnuke\Util\FileUtil;

class EditLanguage extends NewBaseAdminModule
{
	/**
	 * @var LanguageCollection
	 */
	protected $myWords;

	/// <summary>
	/// Default constructor
	/// </summary>
	public function EditLanguage()
	{}

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
		return array("MANAGER", "EDITOR");
	}

	public function CreatePage()
	{
		parent::CreatePage(); // Doesnt necessary get PX, because PX is protected!

		$this->myWords = $this->WordCollection();
		$this->setTitlePage($this->myWords->Value("TITLE"));
		$this->setHelp($this->myWords->Value("DESCRIPTION"));
		$this->addMenuOption($this->myWords->Value("NEWLANGUAGEFILE"),"module:Xmlnuke.Admin.EditLanguage?action=new");
		$this->addMenuOption($this->myWords->Value("VIEWSHAREDFILES"),"module:Xmlnuke.Admin.EditLanguage?op=1");
		$this->addMenuOption($this->myWords->Value("VIEWPRIVATEFILES"),"module:Xmlnuke.Admin.EditLanguage");

		$block = new XmlBlockCollection($this->myWords->Value("WORKINGAREA"), BlockPosition::Center);
		$this->defaultXmlnukeDocument->addXmlnukeObject($block);

		$op = $this->_context->get("op");
		$ed = $this->_context->get("ed");
		$langDir = new AnydatasetLangFilenameProcessor("");

		if ($op == "")
		{
			$filelist = FileUtil::RetrieveFilesFromFolder($langDir->PrivatePath(), $langDir->Extension());
		}
		else
		{
			$filelist = FileUtil::RetrieveFilesFromFolder($langDir->SharedPath(), $langDir->Extension());
		}
		$it = $this->getIteratorFromList($filelist, $langDir);

		if ($this->_action == "")
		{
			$editlist = new XmlEditList($this->_context, $this->myWords->Value("FILELIST$op"), "module:Xmlnuke.Admin.EditLanguage", true, false, true, false);

			$field = new EditListField();
			$field->editlistName = "#";
			$field->fieldData = "key";
			$editlist->addEditListField($field);

			$field = new EditListField();
			$field->editlistName = "Language Filename";
			$field->fieldData = "singlename";
			$editlist->addEditListField($field);

			$editlist->setDataSource($it);
			$editlist->addParameter("op", $op);
			$editlist->setEnablePage(true);
			$editlist->setPageSize(20, 0);
			$block->addXmlnukeObject($editlist);
		}
		elseif (($this->_action == ModuleAction::Edit) || ($ed == 1))
		{
			if ($ed == 1)
			{
				$file = $this->_context->get("file");
			}
			else
			{
				$file = $this->_context->get("valueid");
			}

			$langDir = new AnydatasetLangFilenameProcessor($file);
			$langDir->setFilenameLocation(($op == "" ? ForceFilenameLocation::PrivatePath : ForceFilenameLocation::SharedPath));
			$anydata = new AnyDataset($langDir->FullQualifiedNameAndPath());

			$it = $anydata->getIterator();
			$sr = $it->moveNext();

			$arFields = $sr->getFieldNames();

			$i = 0;
			$CrudFieldCollection = new CrudFieldCollection();
			foreach ($arFields as $value)
			{
				$process = CrudField::FactoryMinimal($value, $value, 40, ($i<4), true);
				$process->key = ($i == 0);
				if ($value == "LANGUAGE")
				{
					$process->beforeInsertFormatter = $this;
				}
				$CrudFieldCollection->addCrudField($process);
				$i++;
			}

			$crud =
				new XmlnukeCrudAnydata(
					$this->_context,
					$CrudFieldCollection,
					$this->myWords->Value("EDITLANGUAGE", $file),
					"module:Xmlnuke.Admin.EditLanguage",
					null,
					$langDir
				);
			$crud->addParameter("op", $op);
			$crud->addParameter("ed", 1);
			$crud->addParameter("file", $file);

			$block->addXmlnukeObject($crud);

		}
		elseif ($this->_action == ModuleAction::Create)
		{
			$form = new XmlFormCollection($this->_context, "module:Xmlnuke.Admin.EditLanguage", $this->myWords->Value("NEWLANGUAGEFILE"));
			$form->addXmlnukeObject(new XmlInputHidden("action", ModuleAction::CreateConfirm));
			$form->addXmlnukeObject(new XmlInputHidden("op", $op));
			$form->addXmlnukeObject(new XmlInputTextBox($this->myWords->Value("NEWFILE"), "newfile", "", 30));
			$form->addXmlnukeObject(new XmlInputMemo($this->myWords->Value("FIELDS"), "fields", "TITLE\r\nABSTRACT"));
			$form->addXmlnukeObject(XmlInputButtons::CreateSubmitButton($this->myWords->Value("TXT_SUBMIT")));
			$block->addXmlnukeObject($form);
		}
		elseif ($this->_action == ModuleAction::CreateConfirm)
		{
			$file = $this->_context->get("newfile");
			$langDir = new AnydatasetLangFilenameProcessor($file);
			$langDir->setFilenameLocation(($op == "" ? ForceFilenameLocation::PrivatePath : ForceFilenameLocation::SharedPath));
			$anydata = new AnyDataset($langDir->FullQualifiedNameAndPath());

			$fields = explode("\r\n", $this->_context->get("fields"));

			$langs = $this->_context->LanguagesAvailable();
			foreach ($langs as $lang=>$dummy)
			{
				$anydata->appendRow();
				$anydata->addField("lang", $lang);
				foreach ($fields as $field)
				{
					$anydata->addField($field, "");
				}
			}
			$anydata->Save($langDir);
			$this->_context->redirectUrl("module:Xmlnuke.Admin.EditLanguage?ed=1&file=$file");
		}


		$langfile = $this->_context->get("langfile");
		$contents = $this->_context->get("contents");
		$contents = stripslashes($contents);

		return $this->defaultXmlnukeDocument;
	}

	/**
	 *
	 * @param array $filelist
	 * @param FilenameProcessor $proc
	 * @return IteratorInterface
	 */
	private function getIteratorFromList($filelist, $proc)
	{
		$arResult = array();
		asort($filelist);
		foreach($filelist as $key => $file)
		{
			$name = FileUtil::ExtractFileName($file);
			$name = $proc->removeLanguage($name);

			$arResult[$name] = $name;
		}

		$ds = new ArrayDataset($arResult, "singlename");
		return $ds->getIterator();

	}

	public function Format($row, $field, $value)
	{
		return $_POST[$field];
	}

}

?>