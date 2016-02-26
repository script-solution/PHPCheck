<?php
/**
 * Contains the add-version-action
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
 * The add-version-action
 *
 * @package			PHPCheck
 * @subpackage	modules
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Action_edit_project_save extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$db = FWS_Props::get()->db();
		
		$pid = $input->get_var('id','get',FWS_Input::INTEGER);
		if($pid == null)
			return '';
		
		if($input->isset_var('add','post'))
		{
			$type = $input->correct_var('add_type','post',FWS_Input::STRING,array('min','max'),'min');
			$name = $input->get_var('add_name','post',FWS_Input::STRING);
			$version = $input->get_var('add_version','post',FWS_Input::STRING);
			
			if($name == '')
				return 'Please specify the name of the component';
			if($version == '')
				return 'Please specify the version';
			
			PC_DAO::get_projects()->add_req($pid,$type,$name,$version);
		}
		else
		{
			$name = $input->get_var('name','post',FWS_Input::STRING);
			$start_day = $input->get_var('start_day','post',FWS_Input::INTEGER);
			$start_month = $input->get_var('start_month','post',FWS_Input::INTEGER);
			$start_year = $input->get_var('start_year','post',FWS_Input::INTEGER);
			$start = mktime(0,0,0,$start_month,$start_day,$start_year);
			
			$edit_type = $input->get_var('edit_type','post');
			$edit_name = $input->get_var('edit_name','post');
			$edit_version = $input->get_var('edit_version','post');
			
			$proj = PC_DAO::get_projects()->get_by_id($pid);
			if(!$proj)
				return '';
			
			$proj->set_name($name);
			$proj->set_created($start);
			$req = $proj->get_req();
			foreach($req as &$r)
			{
				$r['type'] = $edit_type[$r['id']];
				$r['name'] = $edit_name[$r['id']];
				$r['version'] = $edit_version[$r['id']];
			}
			$proj->set_req($req);

			PC_DAO::get_projects()->update($proj);
		}
		
		$this->set_success_msg('The version has been added');
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