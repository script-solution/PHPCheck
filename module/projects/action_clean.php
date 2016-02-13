<?php
/**
 * Contains the clean-project-action
 * 
 * @package			PHPCheck
 * @subpackage	module
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
