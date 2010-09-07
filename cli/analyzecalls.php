<?php
/**
 * Contains the cli-call-analyzer-module
 * 
 * @version			$Id$
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
		$an = new PC_Engine_Analyzer(
			$project !== null ? $project->get_report_mixed() : false,
			$project !== null ? $project->get_report_unknown() : false
		);
		$types = new PC_Engine_TypeContainer();
		// we need all classes in the type-container to be able to search for sub-classes and interface-
		// implementations
		$types->add_classes(PC_DAO::get_classes()->get_list());
		
		// analyze calls
		$calls = PC_DAO::get_calls()->get_list();
		$an->analyze_calls($types,$calls);
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
	}
}
?>