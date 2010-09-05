<?php
/**
 * Contains the cli-call-analyzer-module
 * 
 * @version			$Id: module.php 57 2010-09-03 23:13:08Z nasmussen $
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The cli-call-analyzer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_AnalyzeCalls implements PC_CLIJob
{
	public function run($args)
	{
		$project = FWS_Props::get()->project();
		$an = new PC_Compile_Analyzer(
			$project !== null ? $project->get_report_mixed() : false,
			$project !== null ? $project->get_report_unknown() : false
		);
		$types = new PC_Compile_TypeContainer();
		
		// analyze calls
		$calls = PC_DAO::get_calls()->get_list();
		$an->analyze_calls($types,$calls);
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
	}
}
?>