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
namespace Xmlnuke\Core\AnyDataset;

use IteratorIterator;
use PDO;
use Xmlnuke\Core\Database\ConnectionManagement;
use Xmlnuke\Core\Database\DBOci8Driver;
use Xmlnuke\Core\Database\DBPDODriver;
use Xmlnuke\Core\Database\DBSQLRelayDriver;
use Xmlnuke\Core\Database\IDBDriver;
use Xmlnuke\Core\Database\IDbFunctions;
use Xmlnuke\Core\Engine\Context;
use Xmlnuke\Core\Engine\PluginFactory;

class DBDataSet 
{
	
	protected $_context = null;
	
	/**
	 * Enter description here...
	 *
	 * @var ConnectionManagement
	 */
	protected $_connectionManagement;

	/**
	 *
	 * @var IDBDriver
	 */
	protected $_dbDriver = null;
	
	/**
	 *@param string $dbname - Name of file without '_db' and extention '.xml'. 
	 *@param Context $context
	 *@desc Constructor
	 */
	public function __construct($dbname, $context = null)
	{
		if (is_null($context))
			$this->_context = Context::getInstance();
		else
			$this->_context = $context;
		
		$this->_connectionManagement = new ConnectionManagement ( $dbname );
		
		if ($this->_connectionManagement->getDriver () == "sqlrelay") 
			$this->_dbDriver = new DBSQLRelayDriver ($this->_connectionManagement);
		elseif ($this->_connectionManagement->getDriver () == "oci8") 
			$this->_dbDriver = new DBOci8Driver ($this->_connectionManagement);
		else
			$this->_dbDriver = new DBPDODriver($this->_connectionManagement);
	}
	
	public function getDbType() 
	{
		return $this->_connectionManagement->getDbType ();
	}
	
	public function getDbConnectionString() 
	{
		return $this->_connectionManagement->getDbConnectionString ();
	}
	
	public function testConnection() 
	{
		return true;
	}
		
	/**
	 * @access public
	 * @param string $sql
	 * @param array $array
	 * @return IIterator
	 */
	public function getIterator($sql, $array = null) 
	{
		return $this->_dbDriver->getIterator($sql, $array);
	}

	public function getScalar($sql, $array = null)
	{
		return $this->_dbDriver->getScalar($sql, $array);
	}

	/**
	 *@access public
	 *@param string $tablename
	 *@return array
	 */
	public function getAllFields($tablename) 
	{
		return $this->_dbDriver->getAllFields($tablename);
	}
	
	/**
	 *@access public
	 *@param string $sql
	 *@param array $array
	 *@return Resource
	 */
	public function execSQL($sql, $array = null) 
	{
		$this->_dbDriver->executeSql($sql, $array);
	}
	
	public function beginTransaction()
	{ 
		$this->_dbDriver->beginTransaction();
	}
	
	public function commitTransaction()
	{ 
		$this->_dbDriver->commitTransaction();
	}
	
	public function rollbackTransaction()
	{ 
		$this->_dbDriver->rollbackTransaction();
	}
		
	/**
	 *@access public
	 *@param Iterator $it
	 *@param string $fieldPK
	 *@param string $fieldName
	 *@return Resource
	 */
	public function getArrayField($it, $fieldPK, $fieldName) 
	{
		$result = array ();
		//$it = $this->getIterator($sql);
		while ( $it->hasNext () ) 
		{
			$registro = $it->MoveNext ();
			$result [$registro->getField ( $fieldPK )] = $registro->getField ( $fieldName );
		}
		return $result;
	}
	
	/**
	 *@access public 
	 *@return PDO
	 */
	public function getDBConnection() 
	{
		return $this->_dbDriver->getDbConnection();
	}
	
	/**
	 * 
	 * @var IDbFunctions
	 */
	protected $_dbFunction = null;
	
	/**
	 * Get a IDbFunctions class to execute Database specific operations.
	 * @return IDbFunctions
	 */
	public function getDbFunctions()
	{
		if ($this->_dbFunction == null)
		{
			$dbFunc = "\\Xmlnuke\\Core\\Database\\DB" . $this->_connectionManagement->getDriver() . "Functions";
			$this->_dbFunction = new $dbFunc();
		}

		return $this->_dbFunction;
	}
}

?>