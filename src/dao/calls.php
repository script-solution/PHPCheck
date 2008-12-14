<?php
/**
 * Contains the calls-dao-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the calls-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Calls extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Calls the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of calls for the given project
	 *
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($pid = 0)
	{
		return $this->get_count_for('','','',$pid);
	}
	
	/**
	 * Returns the number of items for the given file
	 *
	 * @param string $file the file
	 * @param string $class the class-name
	 * @param string $function the function-name
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count_for($file = '',$class = '',$function = '',$pid = 0)
	{
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CALLS.' WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND class LIKE :class' : '')
				.($function ? ' AND function LIKE :func' : '')
		);
		$stmt->bind(':pid',$pid);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		if($function)
			$stmt->bind(':func','%'.$function.'%');
		$set = $db->execute($stmt->get_statement());
		$row = $set->next();
		return $row['num'];
	}
	
	/**
	 * Returns all calls
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param int $pid the project-id (0 = current)
	 * @param string $file the file
	 * @param string $class the class-name
	 * @param string $function the function-name
	 * @return array all found calls
	 */
	public function get_list($start = 0,$count = 0,$file = '',$class = '',$function = '',$pid = 0)
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
		$calls = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CALLS.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND class LIKE :class' : '')
				.($function ? ' AND function LIKE :func' : '')
			 .' ORDER BY id ASC
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		$stmt->bind(':pid',$pid);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		if($function)
			$stmt->bind(':func','%'.$function.'%');
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$calls[] = $this->_build_call($row);
		return $calls;
	}
	
	/**
	 * Creates a new entry for given call
	 *
	 * @param PC_Call $call the call
	 * @return int the used id
	 */
	public function create($call)
	{
		$db = FWS_Props::get()->db();

		if(!($call instanceof PC_Call))
			FWS_Helper::def_error('instance','call','PC_Call',$call);
		
		$project = FWS_Props::get()->project();
		return $db->insert(PC_TB_CALLS,array(
			'project_id' => $project->get_id(),
			'file' => $call->get_file(),
			'line' => $call->get_line(),
			'function' => $call->get_function(),
			'class' => $call->get_class() === null ? null : $call->get_class(),
			'static' => $call->is_static() ? 1 : 0,
			'objcreation' => $call->is_object_creation() ? 1 : 0,
			'arguments' => serialize($call->get_arguments())
		));
	}
	
	/**
	 * Deletes all calls from the project with given id
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
			'DELETE FROM '.PC_TB_CALLS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Call from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Call the call
	 */
	private function _build_call($row)
	{
		$c = new PC_Call($row['file'],$row['line']);
		$c->set_class($row['class']);
		$c->set_function($row['function']);
		$c->set_static($row['static']);
		$c->set_object_creation($row['objcreation']);
		$args = unserialize($row['arguments']);
		if($args === false)
			echo FWS_Printer::to_string($row);
		foreach($args as $arg)
			$c->add_argument($arg);
		return $c;
	}
}
?>