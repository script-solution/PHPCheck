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
final class PC_Module_StmtScan_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('stmtscan_files',array()));
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		$user = FWS_Props::get()->user();
		
		// scan for statements
		$types = new PC_TypeContainer();
		$ascanner = new PC_StatementScanner();
		$files = $user->get_session_data('stmtscan_files',array());
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
			$ascanner->scan_file($files[$i],$types);
		
		// insert vars and calls into db
		foreach($ascanner->get_vars() as $svars)
		{
			foreach($svars as $var)
				PC_DAO::get_vars()->create($var);
		}
		foreach($ascanner->get_calls() as $call)
			PC_DAO::get_calls()->create($call);
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