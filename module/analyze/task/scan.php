<?php
/**
 * Contains the stmtscan-task
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The task to scan for statements
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_Analyze_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * The number of calls
	 *
	 * @var int
	 */
	private $_callnum;
	
	/**
	 * The number of classes
	 *
	 * @var int
	 */
	private $_classnum;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_callnum = PC_DAO::get_calls()->get_count();
		$this->_classnum = PC_DAO::get_classes()->get_count();
	}
	
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		return $this->_callnum + $this->_classnum;
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		if($pos == 0)
		{
			// delete all errors
			$project = FWS_Props::get()->project();
			PC_DAO::get_errors()->delete_by_project($project->get_id());
		}
		
		$an = new PC_Analyzer();
		$types = new PC_TypeContainer();
		
		// analyze calls
		if($pos < $this->_callnum)
		{
			$calls = PC_DAO::get_calls()->get_list($pos,$ops);
			$an->analyze_calls($types,$calls);
		}
		
		// analyze types
		if($pos >= $this->_callnum)
		{
			$classes = PC_DAO::get_classes()->get_list($pos - $this->_callnum,$ops);
			$an->analyze_classes($types,$classes);
		}
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>