<?php
/**
 * Contains the delete-projects-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The delete-projects-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_projects_delete extends FWS_Actions_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		
		$idstr = $input->get_var('ids','get',FWS_Input::STRING);
		$ids = FWS_Array_Utils::advanced_explode(',',$idstr);
		if(!FWS_Array_Utils::is_numeric($ids))
			return 'Got an invalid id-string';
		
		PC_DAO::get_projects()->delete($ids);
		foreach($ids as $id)
		{
			PC_DAO::get_calls()->delete_by_project($id);
			PC_DAO::get_classes()->delete_by_project($id);
			PC_DAO::get_classfields()->delete_by_project($id);
			PC_DAO::get_constants()->delete_by_project($id);
			PC_DAO::get_errors()->delete_by_project($id);
			PC_DAO::get_functions()->delete_by_project($id);
			PC_DAO::get_vars()->delete_by_project($id);
		}
		
		$this->set_redirect(false);
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
}	
?>