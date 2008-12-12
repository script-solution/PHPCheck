<?php
/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

final class PC_Module_Index extends FWS_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		$doc->use_default_renderer();
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		
		list($types,$vars,$calls) = PC_Utils::get_data();
		
		$tplcalls = array();
		foreach($calls as $call)
		{
			$tplcalls[] = array(
				'call' => $call->get_call(),
				'file' => $call->get_file(),
				'line' => $call->get_line()
			);
		}
		$tpl->add_variable_ref('calls',$tplcalls);
		
		$tplvars = array();
		foreach($vars as $scope => $svars)
		{
			foreach($svars as $var)
			{
				$tplvars[] = array(
					'scope' => $var->get_scope(),
					'name' => $var->get_name(),
					'type' => $var->get_type()
				);
			}
		}
		$tpl->add_variable_ref('vars',$tplvars);
		
		// analyze everything
		$analyzer = new PC_Analyzer();
		$analyzer->analyze_calls($types,$vars,$calls);
		$tplerrors = array();
		foreach($analyzer->get_errors() as $error)
		{
			/* @var $error PC_Error */
			$tplerrors[] = array(
				'file' => $error->get_loc()->get_file(),
				'line' => $error->get_loc()->get_line(),
				'msg' => $error->get_msg(),
				'type' => $error->get_type()
			);
		}
		$tpl->add_variable_ref('errors',$tplerrors);
	}
}
?>