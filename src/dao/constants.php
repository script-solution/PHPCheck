<?php
/**
 * Contains the constants-dao-class
 *
 * @version			$Id$
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
	 * @param int $class the class-id (0 = freestanding)
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($class = 0,$pid = 0)
	{
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		return $db->get_row_count(PC_TB_CONSTANTS,'*',' WHERE class = '.$class.' AND project_id = '.$pid);
	}
	
	/**
	 * Returns the (free) constant with given name in the given project
	 *
	 * @param string $name the constant-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Obj_Constant the constant or null
	 */
	public function get_by_name($name,$pid = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CONSTANTS.'
			 WHERE project_id = '.$pid.' AND class = 0 AND name = ?'
		);
		$stmt->bind(0,$name);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->_build_const($row);
		return null;
	}
	
	/**
	 * Returns all constants
	 *
	 * @param int $class the class-id (0 = freestanding)
	 * @param int $pid the project-id (0 = current)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @return array all found constants
	 */
	public function get_list($class = 0,$pid = 0,$start = 0,$count = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$project = FWS_Props::get()->project();
		$consts = array();
		$rows = $db->get_rows(
			'SELECT * FROM '.PC_TB_CONSTANTS.'
			 WHERE class = '.$class.' AND project_id = '.$project->get_id().'
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
			$consts[] = $this->_build_const($row);
		return $consts;
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Obj_Constant $constant the constant to create
	 * @param int $class the class-id (0 = freestanding)
	 * @return int the used id
	 */
	public function create($constant,$class = 0)
	{
		$db = FWS_Props::get()->db();

		if(!($constant instanceof PC_Obj_Constant))
			FWS_Helper::def_error('instance','constant','PC_Obj_Constant',$constant);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$project = FWS_Props::get()->project();
		return $db->insert(PC_TB_CONSTANTS,array(
			'project_id' => $project->get_id(),
			'class' => $class,
			'file' => $constant->get_file(),
			'line' => $constant->get_line(),
			'name' => $constant->get_name(),
			'type' => $constant->get_type()->get_type(),
			'value' => $constant->get_type()->get_value()
		));
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
		
		$db->execute(
			'DELETE FROM '.PC_TB_CONSTANTS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds a PC_Obj_Constant from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Constant the constant
	 */
	private function _build_const($row)
	{
		return new PC_Obj_Constant(
			$row['file'],$row['line'],$row['name'],new PC_Obj_Type($row['type'],$row['value'])
		);
	}
}
?>