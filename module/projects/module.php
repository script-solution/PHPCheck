<?php
/**
 * Contains the projects-module
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
 * The projects-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_projects extends PC_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$renderer = $doc->use_default_renderer();
		$renderer->add_action(PC_ACTION_ADD_PROJECT,'add');
		$renderer->add_action(PC_ACTION_DELETE_PROJECTS,'delete');
		$renderer->add_action(PC_ACTION_CLEAN_PROJECT,'clean');
		$renderer->add_breadcrumb('Projects',PC_URL::build_mod_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$input = FWS_Props::get()->input();
		$tpl = FWS_Props::get()->tpl();
		
		$delete = $input->get_var('delete','post');
		if($delete != null)
		{
			$ids = $input->get_var('delete','post');
			$names = array();
			foreach(PC_DAO::get_projects()->get_by_ids($ids) as $data)
				$names[] = $data->get_name();
			$namelist = FWS_StringHelper::get_enum($names,'and');
			
			$url = PC_URL::get_mod_url();
			$url->set('ids',implode(',',$ids));
			$url->set('aid',PC_ACTION_DELETE_PROJECTS);
			
			$tpl->set_template('inc_delete_message.htm');
			$tpl->add_variables(array(
				'delete_message' => sprintf('Are you sure to completely delete the projects %s?',$namelist),
				'yes_url' => $url->to_url(),
				'no_url' => PC_URL::build_mod_url()
			));
			$tpl->restore_template();
		}
		
		$projects = array();
		foreach(PC_DAO::get_projects()->get_all() as $project)
		{
			$cleanurl = PC_URL::get_mod_url();
			$cleanurl->set('id',$project->get_id());
			$cleanurl->set('aid',PC_ACTION_CLEAN_PROJECT);
			
			$editurl = PC_URL::get_mod_url('edit_project');
			$editurl->set('id',$project->get_id());
			
			$projects[] = array(
				'name' => $project->get_name(),
				'id' => $project->get_id(),
				'classes' => PC_DAO::get_classes()->get_count($project->get_id()),
				'functions' => PC_DAO::get_functions()->get_count(0,$project->get_id()),
				'errors' => PC_DAO::get_errors()->get_count($project->get_id()),
				'created' => FWS_Date::get_date($project->get_created()),
				'clean_url' => $cleanurl->to_url(),
				'edit_url' => $editurl->to_url()
			);
		}
		
		$this->request_formular();
		$url = PC_URL::get_mod_url();
		$url->set('aid',PC_ACTION_ADD_PROJECT);
		$tpl->add_variables(array(
			'add_project_url' => $url->to_url(),
			'projects' => $projects
		));
	}
}
