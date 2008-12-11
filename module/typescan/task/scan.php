<?php
/**
 * Contains the typescan-task
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module.typescan
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The task to scan for types
 *
 * @package			PHPCheck
 * @subpackage	module.typescan
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_TypeScan_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('typescan_files',array()));
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
		
		$tscanner = new PC_TypeScanner();
		$files = $user->get_session_data('typescan_files',array());
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
			$tscanner->scan_file($files[$i]);
		$tscanner->finish();
		
		foreach($tscanner->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($tscanner->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($tscanner->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
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