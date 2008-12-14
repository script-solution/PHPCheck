<?php
/**
 * Contains the prop-loader-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		return new FWS_Session_Manager(new FWS_Session_Storage_PHP(),true);
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
		$c = new FWS_DB_MySQL_Connection();
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
?>