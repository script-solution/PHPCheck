<?php
/**
 * Contains the start-action
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
		PC_DAO::get_errors()->delete_by_type(
			PC_Obj_Error::get_types_of(PC_Obj_Error::R_ANALYZER),$project->get_id()
		);
		
		if(PC_PARALLEL_JOB_COUNT == 0)
			$this->set_redirect(true,PC_URL::get_submod_url(0,'scan'));
		else
			$this->set_redirect(true,PC_URL::get_submod_url(0,'cliscan'));
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
}	
