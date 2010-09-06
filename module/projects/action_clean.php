<?php
/**
 * Contains the clean-project-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The clean-project-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_projects_clean extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		
		$id = $input->get_var('id','get',FWS_Input::STRING);
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			return 'The id is invalid';
		
		PC_DAO::get_calls()->delete_by_project($id);
		PC_DAO::get_classes()->delete_by_project($id);
		PC_DAO::get_classfields()->delete_by_project($id);
		PC_DAO::get_constants()->delete_by_project($id);
		PC_DAO::get_errors()->delete_by_project($id);
		PC_DAO::get_functions()->delete_by_project($id);
		PC_DAO::get_vars()->delete_by_project($id);
		
		$this->set_redirect(false);
		$this->set_success_msg('The project has been cleaned successfully');
		$this->set_show_status_page(true);
		$this->set_action_performed(true);

		return '';
	}
}	
?>