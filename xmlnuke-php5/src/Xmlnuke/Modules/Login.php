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
namespace Xmlnuke\Modules;

class ModuleActionLogin extends \Xmlnuke\Core\Enum\ModuleAction 
{
	const LOGIN = 'action.LOGIN';
	const NEWUSER = 'action.NEWUSER';
	const NEWUSERCONFIRM = 'action.NEWUSERCONFIRM';
	const FORGOTPASSWORD = 'action.FORGOTPASSWORD';
	const FORGOTPASSWORDCONFIRM = 'action.FORGOTPASSWORDCONFIRM';
	const RESETPASSWORD = 'action.RESETPASSWORD';
	const RESETPASSWORDCONFIRM = 'action.RESETPASSWORDCONFIRM';
}

/**
 * Login is a default module descendant from BaseModule class.
 * This class shows/edit the profile from the current user.
 * 
 * @package xmlnuke
 */
namespace Xmlnuke\Modules;

use Xmlnuke\Core\Admin\IUsersBase;
use Xmlnuke\Core\Classes\MailEnvelope;
use Xmlnuke\Core\Classes\PageXml;
use Xmlnuke\Core\Classes\XmlBlockCollection;
use Xmlnuke\Core\Classes\XmlInputImageValidate;
use Xmlnuke\Core\Classes\XmlnukeManageUrl;
use Xmlnuke\Core\Classes\XmlnukeText;
use Xmlnuke\Core\Classes\XmlnukeUIAlert;
use Xmlnuke\Core\Enum\BlockPosition;
use Xmlnuke\Core\Enum\DATEFORMAT;
use Xmlnuke\Core\Enum\UIAlert;
use Xmlnuke\Core\Locale\LanguageCollection;
use Xmlnuke\Core\Module\BaseModule;
use Xmlnuke\Core\Processor\XSLFilenameProcessor;
use Xmlnuke\Model\Login as LoginModel;
use Xmlnuke\Util\DateUtil;
use Xmlnuke\Util\MailUtil;

class Login extends BaseModule
{
	/**
	 * Users
	 *
	 * @var IUsersBase
	 */
	protected $_users;
		
	/**
	 * Module
	 *
	 * @var String
	 */
	private  $_module = "Xmlnuke.Login";

	/**
	 * @var XmlBlockCollection
	 */
	protected $_blockcenter;

	/**
	 * Login model
	 *
	 * @var LoginModel
	 */
	protected $_login;
	
	/**
	 * Default constructor
	 *
	 * @return Login
	 */
	public function Login()
	{}

	public function Setup($xmlModuleName, $customArgs)
	{
		parent::Setup($xmlModuleName, $customArgs);

		$myWords = $this->WordCollection();
		$this->defaultXmlnukeDocument->addXmlnukeObject($myWords);

		$this->_users = $this->getUsersDatabase();

		$this->_blockCenter = new XmlBlockCollection( "", BlockPosition::Center );
		$this->defaultXmlnukeDocument->addXmlnukeObject($this->_blockCenter);

		$this->_login = new LoginModel($this->_context);

		$this->_urlReturn = $this->_context->get("ReturnUrl");

		$this->_login->setCanRegister(true);
		$this->_login->setCanRetrievePassword(true);

	}

	/**
	 * Create Page
	 *
	 * @return PageXml
	 */
	public function CreatePage()
	{
		switch ($this->_action) 
		{
			case ModuleActionLogin::LOGIN :
				$this->MakeLogin();
				break;
			case ModuleActionLogin::FORGOTPASSWORD :
				$this->ForgotPassword();
				break;
			case ModuleActionLogin::FORGOTPASSWORDCONFIRM :
				$this->ForgotPasswordConfirm();
				break;
			case ModuleActionLogin::NEWUSER :
				$this->CreateNewUser();
				break;
			case ModuleActionLogin::NEWUSERCONFIRM :
				$this->CreateNewUserConfirm();
				break;
			case ModuleActionLogin::RESETPASSWORD :
				$this->ResetPassword();
				break;
			case ModuleActionLogin::RESETPASSWORDCONFIRM :
				$this->ResetPasswordConfirm();
				break;
			default:
				$this->FormLogin();
				break;
		}

		$this->_blockCenter->addXmlnukeObject($this->_login);
		
		return $this->defaultXmlnukeDocument;
	}
	
	/**
	 * Form Login
	 *
	 */
	protected function FormLogin()
	{
		$myWords = $this->WordCollection();
		$this->defaultXmlnukeDocument->setPageTitle($myWords->Value("TITLELOGIN"));

		$this->_login->setAction("");
		$this->_login->setNextAction(ModuleActionLogin::LOGIN);
		$this->_login->setPassword("");
	}

	/**
	 * Make Login
	 *
	 */
	protected function MakeLogin()
	{
		$myWords = $this->WordCollection();
		$user = $this->_users->isValidUser($this->_login->getUsername(), $this->_login->getPassword());
		if ($user == null)
		{
			$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
			$container->setAutoHide(5000);
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("LOGINFAIL"), true));
			$this->_blockCenter->addXmlnukeObject($container);
			$this->FormLogin();
		}
		else
		{
			$this->updateInfo($user->getField($this->_users->getUserTable()->username), $user->getField($this->_users->getUserTable()->id));
		}
	}

	/**
	 * Forgot Password
	 *
	 */
	protected function ForgotPassword()
	{
		if (!$this->_login->getCanRetrievePassword())
		{
			$this->FormLogin();
			return;
		}

		$myWords = $this->WordCollection();
		$this->defaultXmlnukeDocument->setPageTitle($myWords->Value("FORGOTPASSTITLE"));

		$this->_login->setAction(ModuleActionLogin::FORGOTPASSWORD);
		$this->_login->setNextAction(ModuleActionLogin::FORGOTPASSWORDCONFIRM);
		$this->_login->setPassword("");
		$this->_login->setCanRegister(false); // Hide buttons
		$this->_login->setCanRetrievePassword(false); // Hide buttons

		return;
	}
	
	/**
	 * Forgot Password Confirm
	 *
	 */
	protected function ForgotPasswordConfirm()
	{
		if (!$this->_login->getCanRetrievePassword())
		{
			$this->FormLogin();
			return;
		}
		$myWords = $this->WordCollection();
		
		$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxInfo);
		$container->setAutoHide(5000);
		$this->_blockCenter->addXmlnukeObject($container);
		
		$user = $this->_users->getUserEMail( $this->_context->get("email") );
		
		if (is_null($user))
		{
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("FORGOTUSERFAIL"), true));			
			$this->ForgotPassword();
		}
		else
		{
			$newpassword = sha1($this->getRandomPassword());
			$user->setField('TOKEN_PWD_RESET', $newpassword);
			$user->setField('TOKEN_PWD_RESET_VALID', DateUtil::DateAdd(DateUtil::Today(DATEFORMAT::YMD), 2, DATEFORMAT::YMD));
			$this->_users->Save();
			$this->sendResetPasswordMessage($myWords, $user->getField($this->_users->getUserTable()->name), $user->getField($this->_users->getUserTable()->username), $user->getField($this->_users->getUserTable()->email), $newpassword );
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("FORGOTUSEROK"), true));
			$this->FormLogin();
		}
	}

	/**
	 * Forgot Password
	 *
	 */
	protected function ResetPassword()
	{
		if (!$this->_login->getCanRetrievePassword())
		{
			$this->FormLogin();
			return;
		}

		$myWords = $this->WordCollection();

		$user = $this->_users->getUserName( $this->_login->getUsername() );
		$tokenValid = $user->getField("TOKEN_PWD_RESET") == $this->_login->getResetToken()
				&& $user->getField("TOKEN_PWD_RESET_VALID") >= \Xmlnuke\Util\DateUtil::Today(\Xmlnuke\Core\Enum\DATEFORMAT::YMD);

		if (is_null($user) || !$tokenValid)
		{
			$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
			$container->setAutoHide(5000);
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("RESETINVALIDTOKEN"), true));
			$this->_blockCenter->addXmlnukeObject($container);

			$this->FormLogin();
			return;
		}

		$this->defaultXmlnukeDocument->setPageTitle($myWords->Value("RESETPASSTITLE"));

		$this->_login->setAction(ModuleActionLogin::RESETPASSWORD);
		$this->_login->setNextAction(ModuleActionLogin::RESETPASSWORDCONFIRM);
		$this->_login->setCanRegister(false); // Hide buttons
		$this->_login->setCanRetrievePassword(false); // Hide buttons

		return;
	}

	/**
	 * Forgot Password Confirm
	 *
	 */
	protected function ResetPasswordConfirm()
	{
		$myWords = $this->WordCollection();

		if (!$this->_login->getCanRetrievePassword())
		{
			$this->FormLogin();
			return;
		}
		elseif ($this->_login->getPassword() == "" || $this->_context->get('password2') == "")
		{
			$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
			$container->setAutoHide(5000);
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("PASSWORDISREQUIRED"), true));
			$this->_blockCenter->addXmlnukeObject($container);

			$this->ResetPassword();
			return;
		}
		elseif ($this->_login->getPassword() != $this->_context->get('password2'))
		{
			$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
			$container->setAutoHide(5000);
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("PASSWORDNOTMATCH"), true));
			$this->_blockCenter->addXmlnukeObject($container);

			$this->ResetPassword();
			return;
		}

		$user = $this->_users->getUserName( $this->_login->getUsername() );

		$tokenValid = $user->getField("TOKEN_PWD_RESET") == $this->_login->getResetToken()
				&& $user->getField("TOKEN_PWD_RESET_VALID") >= \Xmlnuke\Util\DateUtil::Today(\Xmlnuke\Core\Enum\DATEFORMAT::YMD);

		if (is_null($user) || !$tokenValid)
		{
			$this->FormLogin();
		}
		elseif ($user->getField("TOKEN_PWD_RESET") != $this->_login->getResetToken())
		{
			$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
			$container->setAutoHide(5000);
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("RESETINVALIDTOKEN"), true));
			$this->_blockCenter->addXmlnukeObject($container);

			$this->FormLogin();
			return;
		}
		else
		{
			$newpassword = $this->_users->getSHAPassword($this->_login->getPassword());
			$user->setField('TOKEN_PWD_RESET', '');
			$user->setField('TOKEN_PWD_RESET_VALID', '');
			$user->setField($this->_users->getUserTable()->password, $newpassword);
			$this->_users->Save();
			$container = new XmlnukeUIAlert($this->_context, UIAlert::ModalDialog, "");
			$container->addRedirectButton($myWords->Value("TXT_BACK"), $this->_login->getReturnUrl());
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("RESETPASSWORDOK"), true));
			$this->_blockCenter->addXmlnukeObject($container);
			$this->FormLogin();
		}
	}

	/**
	 * Create New User
	 *
	 */
	protected function CreateNewUser()
	{
		if (!$this->_login->getCanRegister())
		{
			$this->FormLogin();
			return;
		}
		$myWords = $this->WordCollection();
		$this->defaultXmlnukeDocument->setPageTitle($myWords->Value("CREATEUSERTITLE"));

		$this->_login->setAction(ModuleActionLogin::NEWUSER);
		$this->_login->setNextAction(ModuleActionLogin::NEWUSERCONFIRM);
		$this->_login->setPassword("");
		$this->_login->setCanRegister(false); // Hide buttons
		$this->_login->setCanRetrievePassword(false); // Hide buttons

		return;
	}

	/**
	 * Confirm New user
	 *
	 */
	protected function CreateNewUserConfirm()
	{
		if (!$this->_login->getCanRegister())
		{
			$this->FormLogin();
			return;
		}
		$myWords = $this->WordCollection();
		$container = new XmlnukeUIAlert($this->_context, UIAlert::BoxAlert);
		$container->setAutoHide(5000);
		$this->_blockCenter->addXmlnukeObject($container);

		if (($this->_login->getName() == "") || ($this->_login->getEmail() == "") || ($this->_login->getUsername() == "") ||
			!MailUtil::isValidEmail($this->_login->getEmail()))
		{
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("INCOMPLETEDATA"), true));
			$this->CreateNewUser();
		}
		elseif (!XmlInputImageValidate::validateText($this->_context))
		{
			$container->addXmlnukeObject(new XmlnukeText($myWords->Value("OBJECTIMAGEINVALID"), true));
			$this->CreateNewUser();
		}
		else 
		{
			$newpassword = $this->getRandomPassword();
			if (!$this->_users->addUser( $this->_login->getName(), $this->_login->getUsername(), $this->_login->getEmail(), $newpassword ) )
			{
				$container->addXmlnukeObject(new XmlnukeText($myWords->Value("CREATEUSERFAIL"), true));
				$this->CreateNewUser();
			}
			else
			{
				$this->sendWelcomeMessage($myWords, $this->_context->get("name"), $this->_context->get("newloguser"), $this->_context->get("email"), $newpassword );
				$this->_users->Save();
				$container->addXmlnukeObject(new XmlnukeText($myWords->Value("CREATEUSEROK"), true));
				$container->setUIAlertType(UIAlert::BoxInfo);
				$this->FormLogin($block);
			}
		}
	}

	/**
	 * Overrides getXsl() to force to use the LOGIN xsl
	 * @return XSLFilenameProcessor
	 */
	public function getXsl()
	{
		$xslFile = new XSLFilenameProcessor("login");
		return $xslFile;
	}

	/**
	 * Update Info
	 *
	 * @param String $usernamevalid
	 * @param String $id
	 */
	protected function updateInfo($usernamevalid, $id)
	{
		$this->_context->MakeLogin($usernamevalid, $id);
		$url = XmlnukeManageUrl::decodeParam($this->_urlReturn);
		$this->_context->redirectUrl($url);
	}

	/**
	 * Make a random password
	 *
	 * @return string
	 */
	public function getRandomPassword()
	{
		//Random rand = new Random();
		//int type, number;
		$password = "";
		for($i=0; $i<7; $i++)
		{
			$type = rand(0,21) % 3;
			$number = rand(0,25);
			if ($type == 1)
			{
				$password = $password . chr(48 + ($number%10));
			}
			else
			{
				if ($type == 2)
				{
					$password = $password  . chr(65 + $number);

				}
				else
				{
					$password  = $password . chr(97 + $number);

				}
			}
		}
		return $password;
	}

	/**
	 * Send a email with user data profile
	 *
	 * @param LanguageCollection $myWords
	 * @param String $name
	 * @param String $user
	 * @param String $email
	 * @param String $password
	 */
	protected function sendWelcomeMessage($myWords, $name, $user, $email, $password)
	{
		$path = $this->_context->get("SCRIPT_NAME");
		$path = substr($path,0,strrpos($path,"/")+1);
		$url = "http://" . $this->_context->getServerName() . $path;
		$body = $myWords->ValueArgs("WELCOMEMESSAGE", array($name, $this->_context->get("SERVER_NAME"), $user, $password, $url.$this->_context->bindModuleUrl("Xmlnuke.UserProfile")));

		$envelope = new MailEnvelope(
			MailUtil::getFullEmailName($name, $email),
			$myWords->Value("SUBJECTMESSAGE", "[" . $this->_context->get("SERVER_NAME") . "]"),
			$body
		);
		$envelope->Send();
	}

	/**
	 * Send a email with user data profile
	 *
	 * @param LanguageCollection $myWords
	 * @param String $name
	 * @param String $user
	 * @param String $email
	 * @param String $password
	 */
	protected function sendResetPasswordMessage($myWords, $name, $user, $email, $token)
	{
		$path = $this->_context->get("SCRIPT_NAME");
		$path = substr($path,0,strrpos($path,"/")+1);
		$url = "http://" . $this->_context->getServerName() . $path;
		$body = $myWords->ValueArgs("RESETPASSWORDMESSAGE", array($name, $this->_context->get("SERVER_NAME"), $user, $url.$this->_context->bindModuleUrl(str_replace('\\', '.', get_class()) . '?action=' . ModuleActionLogin::RESETPASSWORD . '&username=' . $user . '&resettoken=' . $token . '&returnurl=' . $this->_login->getReturnUrl())));

		$envelope = new MailEnvelope(
			MailUtil::getFullEmailName($name, $email),
			$myWords->Value("RESETSUBJECTMESSAGE", "[" . $this->_context->getServerName() . "]"),
			$body
		);
		$envelope->Send();
	}



}
?>
