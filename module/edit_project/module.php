<?php
/**
 * Contains the edit-project-module
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
 * The edit-project-module
 * 
 * @package			PHPCheck
 * @subpackage	modules
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_edit_project extends PC_Module
{
	/**
	 * @see FWS_Module::init($doc)
	 * 
	 * @param TDL_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$input = FWS_Props::get()->input();
		$renderer = $doc->use_default_renderer();
		
		$renderer->add_action(PC_ACTION_EDIT_PROJECT,'save');
		$renderer->add_action(PC_ACTION_DELETE_REQ,'delete_req');

		$id = $input->get_var('id','get',FWS_Input::INTEGER);
		$editurl = PC_URL::get_mod_url('edit_project');
		$editurl->set('id',$id);
		
		$renderer->add_breadcrumb('Projects',PC_URL::build_mod_url('projects'));
		$renderer->add_breadcrumb('Edit',$editurl->to_url());
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$input = FWS_Props::get()->input();
		$tpl = FWS_Props::get()->tpl();

		$id = $input->get_var('id','get',FWS_Input::INTEGER);
		if($id === null)
		{
			$this->report_error();
			return;
		}
		
		$add_type = $input->correct_var('add_type','post',FWS_Input::STRING,array('min','max'),'min');
		$add_name = $input->get_var('add_name','post',FWS_Input::STRING);
		$add_version = $input->get_var('add_version','post',FWS_Input::STRING);
		
		$proj = PC_DAO::get_projects()->get_by_id($id);
		if($proj === null)
		{
			$this->report_error();
			return;
		}
		
		$target_url = PC_URL::get_mod_url();
		$target_url->set('id',$id);
		
		$this->request_formular();
		
		$req = $proj->get_req();
		if(!is_array($req))
			$req = array();
		foreach($req as &$r)
		{
			$del_url = clone $target_url;
			$del_url->set('vid',$r['id']);
			$del_url->set('aid',PC_ACTION_DELETE_REQ);
			$r['del_url'] = $del_url->to_url();
		}
		
		$tpl->add_variables(array(
			'target_url' => $target_url->to_url(),
			'action_type' => PC_ACTION_EDIT_PROJECT,
			'def_name' => $proj->get_name(),
			'def_start' => $proj->get_created(),
			'req' => $req,
			'add_type' => $add_type,
			'add_name' => $add_name,
			'add_version' => $add_version,
			'types' => array('min' => '&gt;=','max' => '&lt;'),
			'add_req_url' => $target_url->set('aid',PC_ACTION_ADD_REQ)->to_url()
		));
	}
}
?>