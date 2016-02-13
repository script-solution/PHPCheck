<?php
/**
 * Contains the prop-loader-class
 * 
 * @package			PHPCheck
 * @subpackage	src
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * The property-loader for phpcheck
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PropLoader extends FWS_PropLoader
{
	/**
	 * @see FWS_PropLoader::sessions()
	 *
	 * @return FWS_Session_Manager
	 */
	protected function sessions()
	{
		return new FWS_Session_Manager(new PC_Session_Storage_File(),true);
	}

	/**
	 * @see FWS_PropLoader::input()
	 *
	 * @return FWS_Input
	 */
	protected function input()
	{
		$c = FWS_Input::get_instance();
		$c->set_escape_values(false);
		return $c;
	}

	/**
	 * @see FWS_PropLoader::cookies()
	 *
	 * @return FWS_Cookies
	 */
	protected function cookies()
	{
		return new FWS_Cookies('pc_');
	}
	
	/**
	 * @see FWS_PropLoader::doc()
	 *
	 * @return PC_Document
	 */
	protected function doc()
	{
		return new PC_Document();
	}
	
	/**
	 * @return FWS_DB_MySQL_Connection the property
	 */
	protected function db()
	{
		include_once(FWS_Path::server_app().'config/mysql.php');
		$c = new FWS_DB_MySQLi_Connection();
		$c->connect(PC_MYSQL_HOST,PC_MYSQL_LOGIN,PC_MYSQL_PASSWORD);
		$c->select_database(PC_MYSQL_DATABASE);
		$c->set_save_queries(false);
		$c->set_escape_values(true);
		
		$version = $c->get_server_version();
		if($version >= '4.1')
		{
			$c->execute('SET CHARACTER SET utf8;');
			// we don't want to have any sql-modes
			$c->execute('SET SESSION sql_mode="";');
		}
		return $c;
	}
	
	/**
	 * @return PC_Project the current project
	 */
	protected function project()
	{
		return PC_DAO::get_projects()->get_current();
	}
}
