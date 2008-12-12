<?php
/**
 * Contains the vars-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the vars-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Vars extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Vars the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of vars for the given project
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
		return $db->sql_num(PC_TB_VARS,'*',' WHERE project_id = '.$pid);
	}
	
	/**
	 * Returns all vars
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param int $pid the project-id (0 = current)
	 * @return array all found vars
	 */
	public function get_list($start = 0,$count = 0,$pid = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$vars = array();
		$rows = $db->sql_rows(
			'SELECT * FROM '.PC_TB_VARS.'
			 WHERE project_id = '.$pid.'
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
			$vars[] = $this->_build_var($row);
		return $vars;
	}
	
	/**
	 * Creates a new entry for given var
	 *
	 * @param PC_Variable $var the variable
	 * @return int the used id
	 */
	public function create($var)
	{
		$db = FWS_Props::get()->db();

		if(!($var instanceof PC_Variable))
			FWS_Helper::def_error('instance','var','PC_Variable',$var);
		
		$project = FWS_Props::get()->project();
		$db->sql_insert(PC_TB_VARS,array(
			'project_id' => $project->get_id(),
			'name' => addslashes($var->get_name()),
			'function' => addslashes($var->get_function()),
			'class' => addslashes($var->get_class()),
			'type' => addslashes(serialize($var->get_type()))
		));
		return $db->get_last_insert_id();
	}
	
	/**
	 * Deletes all vars from the project with given id
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
			'DELETE FROM '.PC_TB_VARS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Variable from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Variable the var
	 */
	private function _build_var($row)
	{
		return new PC_Variable($row['name'],unserialize($row['type']),$row['function'],$row['class']);
	}
}
?>