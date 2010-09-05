<?php
/**
 * Contains the cli-job-interface
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The interface that all cli-jobs will implemented
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
interface PC_CLIJob
{
	/**
	 * Runs the job with given arguments
	 * 
	 * @param array $args the arguments
	 */
	public function run($args);
}
?>