<?php
/**
 * Contains the delete-requirements action
 * 
 * @package			PHPCheck
 * @subpackage	modules
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * The delete-requirements action
 *
 * @package			PHPCheck
 * @subpackage	modules
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Action_edit_project_delete_req extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$db = FWS_Props::get()->db();
		
		$pid = $input->get_var('id','get',FWS_Input::INTEGER);
		$vid = $input->get_var('vid','get',FWS_Input::INTEGER);
		if($pid == null || $vid == null)
			return TDL_GENERAL_ERROR;
		
		PC_DAO::get_projects()->del_req($vid);
		
		$this->set_success_msg('The requirement has been deleted');
		$this->set_redirect(
			true,
			PC_URL::get_mod_url('edit_project')->set('id',$pid)
		);
		$this->set_show_status_page(false);
		$this->set_action_performed(true);
	
		return '';
	}
}
?>