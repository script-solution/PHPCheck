<?php
/**
 * Contains the change-project-action
 * 
 * @package			PHPCheck
 * @subpackage	src.action
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
 * The change-project-action
 *
 * @package			PHPCheck
 * @subpackage	src.action
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_chgproject extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		
		$pid = $input->get_var('project','post',FWS_Input::INTEGER);
		if($pid < 0)
			return 'Invalid project-id';
		
		PC_DAO::get_projects()->set_current($pid);
		
		$this->set_action_performed(true);
		$this->set_show_status_page(false);
		$this->set_redirect(false);

		return '';
	}
}	
