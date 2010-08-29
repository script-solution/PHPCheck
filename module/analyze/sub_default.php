<?php
/**
 * Contains the default-analyze-submodule
 * 
 * @version			$Id: sub_default.php 38 2010-08-29 22:07:05Z nasmussen $
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The default submodule for module analyze
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_analyze_default extends PC_SubModule
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$renderer = $doc->use_default_renderer();
		$renderer->add_action(PC_ACTION_START_ANALYZE,'start');
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$project = FWS_Props::get()->project();
		$this->request_formular();
		$tpl->add_variables(array(
			'action_id' => PC_ACTION_START_ANALYZE,
			'report_mixed' => $project !== null ? $project->get_report_mixed() : false,
			'report_unknown' => $project !== null ? $project->get_report_unknown() : false
		));
	}
}
?>