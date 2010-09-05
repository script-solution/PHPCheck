<?php
/**
 * Contains the start-action
 *
 * @version			$Id: action_startscan.php 27 2008-12-13 17:11:45Z nasmussen $
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The start-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_analyze_start extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$report_mixed = $input->isset_var('report_mixed','post');
		$report_unknown = $input->isset_var('report_unknown','post');
		
		// store in project
		$project = FWS_Props::get()->project();
		$project->set_report_mixed($report_mixed);
		$project->set_report_unknown($report_unknown);
		PC_DAO::get_projects()->update($project);
		
		// clear position, just to be sure
		$storage = new FWS_Progress_Storage_Session('panalyze_');
		$storage->clear();
		
		// clear previous data in the db
		$project = FWS_Props::get()->project();
		PC_DAO::get_errors()->delete_by_project($project->get_id());
		
		if(PC_PARALLEL_JOB_COUNT == 0)
			$this->set_redirect(true,PC_URL::get_submod_url(0,'scan'));
		else
			$this->set_redirect(true,PC_URL::get_submod_url(0,'cliscan'));
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
}	
?>