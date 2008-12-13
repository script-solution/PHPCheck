<?php
/**
 * Contains the change-project-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The change-project-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_chgproject extends FWS_Actions_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		
		$pid = $input->get_var('project','post',FWS_Input::INTEGER);
		if($pid <= 0)
			return 'Invalid project-id';
		
		PC_DAO::get_projects()->set_current($pid);
		
		$this->set_action_performed(true);
		$this->set_show_status_page(false);
		$this->set_redirect(false);

		return '';
	}
}	
?>