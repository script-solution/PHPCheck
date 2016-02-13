<?php
/**
 * Contains the dao-factory
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
 * The factory for all DAO-classes. This allows us for example to support other DBMS in future
 * by exchanging the DAO-classes here.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_DAO extends FWS_UtilBase
{
	/**
	 * @return PC_DAO_Calls the DAO for the calls-table
	 */
	public static function get_calls()
	{
		return PC_DAO_Calls::get_instance();
	}
	
	/**
	 * @return PC_DAO_Classes the DAO for the classes-table
	 */
	public static function get_classes()
	{
		return PC_DAO_Classes::get_instance();
	}
	
	/**
	 * @return PC_DAO_ClassFields the DAO for the class-fields-table
	 */
	public static function get_classfields()
	{
		return PC_DAO_ClassFields::get_instance();
	}
	
	/**
	 * @return PC_DAO_Errors the DAO for the errors-table
	 */
	public static function get_errors()
	{
		return PC_DAO_Errors::get_instance();
	}
	
	/**
	 * @return PC_DAO_Functions the DAO for the functions-table
	 */
	public static function get_functions()
	{
		return PC_DAO_Functions::get_instance();
	}
	
	/**
	 * @return PC_DAO_Constants the DAO for the constants-table
	 */
	public static function get_constants()
	{
		return PC_DAO_Constants::get_instance();
	}
	
	/**
	 * @return PC_DAO_Projects the DAO for the projects-table
	 */
	public static function get_projects()
	{
		return PC_DAO_Projects::get_instance();
	}
	
	/**
	 * @return PC_DAO_Vars the DAO for the vars-table
	 */
	public static function get_vars()
	{
		return PC_DAO_Vars::get_instance();
	}
}
