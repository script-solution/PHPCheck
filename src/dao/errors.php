<?php
/**
 * Contains the errors-dao-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the errors-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Errors extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Errors the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of errors for the given project
	 *
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($pid = 0)
	{
		return $this->get_count_with('','',array(),$pid);
	}
	
	/**
	 * Returns the number of errors that contain the given file and message
	 *
	 * @param string $file the file
	 * @param string $msg the message
	 * @param array $types an array of types (numeric!)
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count_with($file = '',$msg = '',$types = array(),$pid = 0)
	{
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? ($project !== null ? $project->get_id() : 0) : $pid;
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_ERRORS.'
			 WHERE project_id = '.$pid
				.($file ? ' AND file LIKE :file' : '')
				.($msg ? ' AND message LIKE :msg' : '')
				.(count($types) ? ' AND type IN ('.implode(',',$types).')' : '')
		);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($msg)
			$stmt->bind(':msg','%'.$msg.'%');
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns all errors. Optionally you can filter the search by file and message
	 *
	 * @param int $pid the project-id (0 = current)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $file the file
	 * @param string $msg the message
	 * @param array $types an array of types (numeric!)
	 * @return array all found errors
	 */
	public function get_list($pid = 0,$start = 0,$count = 0,$file = '',$msg = '',$types = array())
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Array_Utils::is_numeric($types))
			FWS_Helper::def_error('numarray','types',$types);
		
		$project = FWS_Props::get()->project();
		$errs = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_ERRORS.'
			 WHERE project_id = '.($project !== null ? $project->get_id() : 0)
				.($file ? ' AND file LIKE :file' : '')
				.($msg ? ' AND message LIKE :msg' : '')
				.(count($types) ? ' AND type IN ('.implode(',',$types).')' : '')
				.' ORDER BY id ASC'
				.($count > 0 ? ' LIMIT '.$start.','.$count : '')
		);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($msg)
			$stmt->bind(':msg','%'.$msg.'%');
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$errs[] = $this->_build_error($row);
		return $errs;
	}
	
	/**
	 * Creates a new entry for given error
	 *
	 * @param PC_Obj_Error $error the error to create
	 * @return int the used id
	 */
	public function create($error)
	{
		$db = FWS_Props::get()->db();

		if(!($error instanceof PC_Obj_Error))
			FWS_Helper::def_error('instance','error','PC_Obj_Error',$error);
		
		$project = FWS_Props::get()->project();
		return $db->insert(PC_TB_ERRORS,array(
			'project_id' => $project !== null ? $project->get_id() : 0,
			'file' => $error->get_loc()->get_file(),
			'line' => $error->get_loc()->get_line(),
			'message' => $error->get_msg(),
			'type' => $error->get_type()
		));
	}
	
	/**
	 * Deletes all errors from the project with given id
	 *
	 * @param int $id the project-id
	 * @return int the number of affected rows
	 */
	public function delete_by_project($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id < 0)
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_ERRORS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds a PC_Obj_Error from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Error the error
	 */
	private function _build_error($row)
	{
		return new PC_Obj_Error(new PC_Obj_Location($row['file'],$row['line']),$row['message'],$row['type']);
	}
}
?>