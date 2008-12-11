<?php
/**
 * Contains the constants-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the constants-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Constants extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Constants the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of constants for the given project
	 *
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($pid = 0)
	{
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		return $db->sql_num(PC_TB_CONSTANTS,'*',' WHERE project_id = '.$pid);
	}
	
	/**
	 * Returns all constants
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @return array all found constants
	 */
	public function get_list($start = 0,$count = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$project = FWS_Props::get()->project();
		$consts = array();
		$rows = $db->sql_rows(
			'SELECT * FROM '.PC_TB_CONSTANTS.'
			 WHERE project_id = '.$project->get_id().'
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
		{
			$consts[] = new PC_Constant(
				$row['file'],$row['line'],$row['name'],new PC_Type($row['type'],$row['value'])
			);
		}
		return $consts;
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Constant $constant the constant to create
	 * @return int the used id
	 */
	public function create($constant)
	{
		$db = FWS_Props::get()->db();

		if(!($constant instanceof PC_Constant))
			FWS_Helper::def_error('instance','constant','PC_Constant',$constant);
		
		$project = FWS_Props::get()->project();
		$db->sql_insert(PC_TB_CONSTANTS,array(
			'project_id' => $project->get_id(),
			'file' => addslashes($constant->get_file()),
			'line' => $constant->get_line(),
			'name' => addslashes($constant->get_name()),
			'type' => $constant->get_type()->get_type(),
			'value' => addslashes($constant->get_type()->get_value())
		));
		return $db->get_last_insert_id();
	}
	
	/**
	 * Deletes all contants from the project with given id
	 *
	 * @param int $id the project-id
	 * @return int the number of affected rows
	 */
	public function delete_by_project($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$db->sql_qry(
			'DELETE FROM '.PC_TB_CONSTANTS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
}
?>