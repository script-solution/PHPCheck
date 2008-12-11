<?php
/**
 * Contains the projects-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the projects-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Projects extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Projects the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * @return array an array of all projects
	 */
	public function get_all()
	{
		$db = FWS_Props::get()->db();
		$res = array();
		$rows = $db->sql_rows('SELECT * FROM '.PC_TB_PROJECTS);
		foreach($rows as $row)
			$res[] = new PC_Project($row['id'],$row['name']);
		return $res;
	}
	
	/**
	 * @return PC_Project the current project
	 */
	public function get_current()
	{
		$db = FWS_Props::get()->db();
		$row = $db->sql_fetch('SELECT * FROM '.PC_TB_PROJECTS.' WHERE current = 1');
		return new PC_Project($row['id'],$row['name']);
	}
	
	/**
	 * Creates a new project
	 *
	 * @param PC_Project $project the project
	 * @return int the id
	 */
	public function create($project)
	{
		$db = FWS_Props::get()->db();
		
		if(!($project instanceof PC_Project))
			FWS_Helper::def_error('instance','project','PC_Project',$project);
		
		$db->sql_insert(PC_TB_PROJECTS,array(
			'name' => addslashes($project->get_name()),
			'created' => time()
		));
		return $db->get_last_insert_id();
	}
}
?>