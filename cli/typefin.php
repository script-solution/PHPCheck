<?php
/**
 * Contains the cli-type-finalizer-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The cli-type-finalizer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_TypeFin implements PC_CLIJob
{
	public function run($args)
	{
		$typecon = new PC_Compile_TypeContainer(PC_Project::CURRENT_ID,true);
		$typecon->add_classes(PC_DAO::get_classes()->get_list());
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_DB());
		$fin->finalize();
	}
}
?>