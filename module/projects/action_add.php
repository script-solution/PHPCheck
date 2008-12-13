<?php
/**
 * Contains the add-projects-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The add-projects-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_projects_add extends FWS_Action_Base
{
	public function perform_action()
	{
		PC_DAO::get_projects()->create(new PC_Project(1,'',time(),'','','',''));
		
		$this->set_redirect(false);
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
}	
?>