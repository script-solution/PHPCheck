<?php
/**
 * Contains the cli-statement-scan-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The cli-statement-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_StmtScan implements PC_CLIJob
{
	public function run($args)
	{
		$errors = array();
		$types = new PC_Compile_TypeContainer();
		$ascanner = new PC_Compile_StmtScannerFrontend();
		
		foreach($args as $file)
		{
			try
			{
				$ascanner->scan_file($file,$types);
			}
			catch(PC_Compile_Exception $e)
			{
				$errors[] = $e->__toString();
			}
		}
		if(count($ascanner->get_calls()))
			PC_DAO::get_calls()->create_bulk($ascanner->get_calls());
		
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