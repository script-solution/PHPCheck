<?php
/**
 * Contains the save-projects-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The save-projects-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_projects_save extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		if(!$input->isset_var('submit','post'))
			return '';
		
		$names = $input->get_var('name','post');
		if(!FWS_Array_Utils::is_numeric(array_keys($names)))
			return 'Invalid name-array';
		
		foreach(PC_DAO::get_projects()->get_by_ids(array_keys($names)) as $project)
		{
			$project->set_name($names[$project->get_id()]);
			PC_DAO::get_projects()->update($project);
		}
		
		$this->set_show_status_page(false);
		$this->set_redirect(false);
		$this->set_action_performed(true);

		return '';
	}
}
?>