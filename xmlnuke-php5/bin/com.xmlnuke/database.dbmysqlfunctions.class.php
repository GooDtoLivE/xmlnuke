<?php
/*
 *=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=
 *  Copyright:
 *
 *  XMLNuke: A Web Development Framework based on XML.
 *
 *  Main Specification and Implementation: Joao Gilberto Magalhaes, joao at byjg dot com
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

class DbMySQLFunctions extends DbBaseFunctions
{

	function Concat($s1, $s2 = null)
	{
		$sql = "concat(";
	 	for ($i = 0, $numArgs = func_num_args(); $i < $numArgs ; $i++)
	 	{
	 		$var = func_get_arg($i);
	 		$sql .= ($i==0 ? "" : ",") . $var;
	 	}
	 	$sql .= ")";
	 	
	 	return $sql;
	} 

	/**
	 * Given a SQL returns it with the proper LIMIT or equivalent method included
	 * @param string $sql
	 * @param int $start
	 * @param int $qty 
	 * @return string
	 */
	function Limit($sql, $start, $qty)
	{
		/*** 
		 * 
		 * Por favor! Verifique se o SQL já tem LIMIT e faça os ajustes!
		 *  
		 * */
		return $sql .= " LIMIT $start, $qty ";
	}

	/**
	 * Given a SQL returns it with the proper TOP or equivalent method included
	 * @param string $sql
	 * @param int $qty 
	 * @return string
	 */
	function Top($sql, $qty)
	{
		return $this->Limit($sql, 0, $qty);
	}

	/**
	 * Return if the database provider have a top or similar function 
	 * @return unknown_type
	 */
	function hasTop()
	{
		return true;
	}

	/**
	 * Return if the database provider have a limit function 
	 * @return bool
	 */
	function hasLimit()
	{
		return true;
	}
	
}

?>