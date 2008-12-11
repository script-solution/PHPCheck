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