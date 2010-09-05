<?php
/**
 * Contains the cli-phpref-finalizer-module
 * 
 * @version			$Id: module.php 57 2010-09-03 23:13:08Z nasmussen $
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The cli-phpref-finalizer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_PHPRefFin implements PC_CLIJob
{
	public function run($args)
	{
		// get shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		$misc = $data->get_misc();
		$mutex->close();
		
		$fin = new PC_PHPRef_Finalizer($misc['aliases'],$misc['versions']);
		$fin->finalize();
	}
}
?>