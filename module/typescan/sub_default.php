<?php
/**
 * Contains the default-typescan-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The default submodule for module typescan
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_typescan_default extends PC_SubModule
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
		$renderer->add_action(PC_ACTION_START_TYPESCAN,'startscan');
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
			'action_id' => PC_ACTION_START_TYPESCAN,
			'folders' => $project !== null ? $project->get_type_folders() : '',
			'exclude' => $project !== null ? $project->get_type_exclude() : ''
		));
	}
}
?>