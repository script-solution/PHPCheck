<?php
/**
 * Contains the vars-dao-class
 *
 * @version			$Id$
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
	const MAX_VALUE_LEN			= 2048;
	
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
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$pid = PC_Utils::get_project_id($pid);
		return $db->get_row_count(PC_TB_VARS,'*',' WHERE project_id = '.$pid);
	}
	
	/**
	 * Returns all vars
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param int $pid the project-id (default = current)
	 * @return array all found vars
	 */
	public function get_list($start = 0,$count = 0,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$vars = array();
		$rows = $db->get_rows(
			'SELECT * FROM '.PC_TB_VARS.'
			 WHERE project_id = '.PC_Utils::get_project_id($pid).'
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
			$vars[] = $this->_build_var($row);
		return $vars;
	}
	
	/**
	 * Creates a new entry for given var
	 *
	 * @param PC_Obj_Variable $var the variable
	 * @return int the used id
	 */
	public function create($var)
	{
		$db = FWS_Props::get()->db();

		if(!($var instanceof PC_Obj_Variable))
			FWS_Helper::def_error('instance','var','PC_Obj_Variable',$var);
		
		$project = FWS_Props::get()->project();
		$type = serialize($var->get_type());
		if(strlen($type) > self::MAX_VALUE_LEN)
		{
			$clone = clone $var->get_type();
			$clone->clear_values();
			$type = serialize($clone);
		}
		return $db->insert(PC_TB_VARS,array(
			'project_id' => PC_Utils::get_project_id(PC_Project::CURRENT_ID),
			'name' => $var->get_name(),
			'function' => $var->get_function(),
			'class' => $var->get_class(),
			'type' => $type
		));
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
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_VARS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Obj_Variable from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Obj_Variable the var
	 */
	private function _build_var($row)
	{
		return new PC_Obj_Variable($row['name'],unserialize($row['type']),$row['function'],$row['class']);
	}
}
?>