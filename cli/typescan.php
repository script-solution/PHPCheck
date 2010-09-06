<?php
/**
 * Contains the cli-type-scan-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The cli-type-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_TypeScan implements PC_CLIJob
{
	public function run($args)
	{
		$errors = array();
		$tscanner = new PC_Compile_TypeScannerFrontend();
		foreach($args as $file)
		{
			try
			{
				$tscanner->scan_file($file);
			}
			catch(PC_Compile_Exception $e)
			{
				$errors[] = $e->__toString();
			}
		}
		
		$typecon = $tscanner->get_types();
		foreach($typecon->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($typecon->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($typecon->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
		foreach($typecon->get_errors() as $err)
			PC_DAO::get_errors()->create($err);
		
		// write errors to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_errors($errors);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
?>