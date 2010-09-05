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
	 * The number of calls we insert into the db at once.
	 * 20 seems to be a good value. A lower and higher value leads (for me) to worse performance
	 * 
	 * @var int
	 */
	const CALLS_AT_ONCE		= 20;
	
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
		$msgs = FWS_Props::get()->msgs();
		
		// scan for statements
		$types = new PC_Compile_TypeContainer();
		$ascanner = new PC_Compile_StmtScannerFrontend();
		$files = $user->get_session_data('stmtscan_files',array());
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
		{
			try
			{
				$ascanner->scan_file($files[$i],$types);
			}
			catch(PC_Compile_Exception $e)
			{
				$msgs->add_error($e->__toString());
			}
		}
		
		// insert vars and calls into db
		$calls = $ascanner->get_calls();
		for($i = 0, $len = count($calls); $i < $len; $i += self::CALLS_AT_ONCE)
			PC_DAO::get_calls()->create_bulk(array_slice($calls,$i,self::CALLS_AT_ONCE));
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