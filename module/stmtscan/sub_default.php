<?php
/**
 * Contains the default-stmtscan-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The default submodule for module stmtscan
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_stmtscan_default extends PC_SubModule
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
		$renderer->add_action(PC_ACTION_START_STMNTSCAN,'startscan');
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
			'action_id' => PC_ACTION_START_STMNTSCAN,
			'folders' => $project !== null ? $project->get_stmt_folders() : '',
			'exclude' => $project !== null ? $project->get_stmt_exclude() : ''
		));
	}
}
?>