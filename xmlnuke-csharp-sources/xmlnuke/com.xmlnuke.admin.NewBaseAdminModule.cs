/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification: Joao Gilberto Magalhaes, joao at byjg dot com
 *  CSharp Specification: Joao Gilberto Magalhaes, joao at byjg dot com
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

using System;
using System.Xml;
using System.Collections.Specialized;

using com.xmlnuke.classes;
using com.xmlnuke.module;
using com.xmlnuke.anydataset;
using com.xmlnuke.processor;
using com.xmlnuke.international;
using com.xmlnuke.util;

namespace com.xmlnuke.admin
{
	abstract public class NewBaseAdminModule : BaseModule, IAdmin
	{
		protected XmlBlockCollection _mainBlock;

		protected XmlParagraphCollection _help;

		protected XmlParagraphCollection _menu;

		public NewBaseAdminModule()
		{ }

		override public IXmlnukeDocument CreatePage()
		{
			this.defaultXmlnukeDocument.PageTitle = "Control Panel";
			this.defaultXmlnukeDocument.Abstract = "Painel de Controle do XMLNuke";
			this._mainBlock = new XmlBlockCollection("Menu", BlockPosition.Center);
			this._help = new XmlParagraphCollection();
			this._menu = new XmlParagraphCollection();
			this._mainBlock.addXmlnukeObject(this._help);
			this._mainBlock.addXmlnukeObject(this._menu);
			this.defaultXmlnukeDocument.addXmlnukeObject(this._mainBlock);
			XmlnukeManageUrl url = new XmlnukeManageUrl(URLTYPE.ADMIN, "");
			url.addParam("site", this._context.Site);
			XmlAnchorCollection link = new XmlAnchorCollection(url.getUrl(), "");
			link.addXmlnukeObject(new XmlnukeText("Menu"));
			this._menu.addXmlnukeObject(link);
			this.defaultXmlnukeDocument.setMenuTitle("Menu");
			this.CreateMenuAdmin();

			return null;
		}

		override public sealed bool requiresAuthentication()
		{
			return true;
		}

        override public international.LanguageCollection WordCollection()
        {
            if (this._words == null)
            {
                this._words = LanguageFactory.GetLanguageCollection(this._context, LanguageFileTypes.ADMINMODULE, this._moduleName);
            }
            return this._words;
        }
        
        public void admin()
		{ }

		protected void addMenuOption(string strMenu, string strLink)
		{
			this.defaultXmlnukeDocument.addMenuItem(strLink, strMenu, "");
		}

		protected void addMenuOption(string strMenu, string strLink, string target)
		{
			this.defaultXmlnukeDocument.addMenuItem(strLink, strMenu, "");
		}

		protected void setHelp(string strHelp)
		{
			this._help.addXmlnukeObject(new XmlnukeText(strHelp));
		}

		protected void setTitlePage(string strTitle)
		{
			this._mainBlock.setTitle(strTitle);
		}


		protected bool isUserAdmin()
		{
			IUsersBase user = this.getUsersDatabase();
			com.xmlnuke.anydataset.SingleRow sr = user.getUserId(this._context.authenticatedUserId());
			return (sr.getField(user.getUserTable().Admin) == "yes");
		}


		protected IIterator GetAdminGroups()
		{
			return this.GetAdminGroups("");
		}
		protected IIterator GetAdminGroups(string group)
		{
			string rowNode;
			if (String.IsNullOrEmpty(group))
			{
				rowNode = "group";
			}
			else
			{
				rowNode = "group[@name='" + group + "']";
			}
			NameValueCollection colNode = new NameValueCollection();
			colNode["name"] = "@name";
			XmlDataSet dataset = new XmlDataSet(this._context, this.GetAdminModulesList(), rowNode, colNode);
			return dataset.getIterator();
		}

		protected IIterator GetAdminModules(string group)
		{
			string rowNode = "group[@name='" + group + "']/module";
			NameValueCollection colNode = new NameValueCollection();
			colNode["name"] = "@name";
			colNode["icon"] = "icon";
			colNode["url"] = "url";

			XmlDataSet dataset = new XmlDataSet(this._context, this.GetAdminModulesList(), rowNode, colNode);
			return dataset.getIterator();
		}

		protected string _adminModulesList = "";
		protected bool _adminModulesListLocal = false;
		protected string GetAdminModulesList()
		{

			if (this._adminModulesList == "")
			{
				// Load Module List
				AnydatasetFilenameProcessor localXmlProcessor = new AnydatasetFilenameProcessor("adminmodules.config", this._context);
				string configFile = localXmlProcessor.PathSuggested() + localXmlProcessor.ToString() + ".xml";
				if (FileUtil.Exists(configFile))
				{
					this._adminModulesListLocal = true;
				}
				else
				{
					XMLFilenameProcessor xmlProcessor = new XMLFilenameProcessor("admin" + FileUtil.Slash() + "adminmodules" + FileUtil.Slash(), this._context);
					xmlProcessor.FilenameLocation = ForceFilenameLocation.PathFromRoot;
					configFile = xmlProcessor.PathSuggested() + "admin" + FileUtil.Slash() + "adminmodules.config.xml";
				}
				this._adminModulesList = FileUtil.QuickFileRead(configFile);
			}
			return this._adminModulesList;
		}


		protected void CreateMenuAdmin()
		{
			// Load Language file for Module Object
			LanguageCollection lang = LanguageFactory.GetLanguageCollection(this._context, LanguageFileTypes.ADMININTERNAL, "adminmodules");

			// Create a Menu Item for GROUPS and MODULES. 
			// This menu have CP_ before GROUP NAME
			IIterator itGroup = this.GetAdminGroups();

			while (itGroup.hasNext())
			{
				SingleRow srGroup = itGroup.moveNext();
				this.defaultXmlnukeDocument.addMenuGroup(lang.Value("GROUP_" + srGroup.getField("name").ToUpper()), "CP_" + srGroup.getField("name"));

				IIterator itModule = this.GetAdminModules(srGroup.getField("name"));

				while (itModule.hasNext())
				{
					SingleRow srModule = itModule.moveNext();
					this.defaultXmlnukeDocument.addMenuItem(
						srModule.getField("url"),
						lang.Value("MODULE_TITLE_" + srModule.getField("name").ToUpper()),
						lang.Value("MODULE_ABSTRACT_" + srModule.getField("name").ToUpper()),
						"CP_" + srGroup.getField("name"),
						srModule.getField("icon"));
				}
			}
		}

	}
}