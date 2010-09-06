<?php
/**
 * Contains the logger-class for the CLI-stuff
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Implements a logger that reports the errors through the shared data so that the users
 * can see them.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLILogger implements FWS_Error_Logger
{
	/**
	 * @see FWS_Error_Logger::log()
	 *
	 * @param int $no
	 * @param string $msg
	 * @param string $file
	 * @param int $line
	 * @param array $backtrace
	 */
	public function log($no,$msg,$file,$line,$backtrace)
	{
		// build error-message
		$msg .= ' in file "'.$file.'", line '.$line.'<br />';
		$btpr = new FWS_Error_BTPrinter_HTML();
		$msg .= $btpr->print_backtrace($backtrace);

		// write stuff to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_error($msg);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
?>