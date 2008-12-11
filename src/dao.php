<?php
/**
 * Contains the dao-factory
 *
 * @version			$Id: dao.php 49 2008-07-30 12:35:41Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
}
?>