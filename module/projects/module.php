<?php
/**
 * Contains the projects-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		$renderer->add_action(PC_ACTION_SAVE_PROJECTS,'save');
		$renderer->add_action(PC_ACTION_ADD_PROJECT,'add');
		$renderer->add_action(PC_ACTION_DELETE_PROJECTS,'delete');
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
			$projects[] = array(
				'name' => $project->get_name(),
				'id' => $project->get_id(),
				'created' => FWS_Date::get_date($project->get_created())
			);
		}
		
		$this->request_formular();
		$url = PC_URL::get_mod_url();
		$url->set('aid',PC_ACTION_ADD_PROJECT);
		$tpl->add_variables(array(
			'action_id' => PC_ACTION_SAVE_PROJECTS,
			'add_project_url' => $url->to_url(),
			'projects' => $projects
		));
	}
}
?>