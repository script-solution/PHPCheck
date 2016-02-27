<?php
/**
 * Contains the startscan-action
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
 * The startscan-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_typescan_startscan extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$user = FWS_Props::get()->user();
		$folderstr = $input->get_var('folders','post',FWS_Input::STRING);
		$exclstr = $input->get_var('exclude','post',FWS_Input::STRING);
		
		$files = array();
		$folders = FWS_Array_Utils::advanced_explode("\n",$folderstr);
		$excl = FWS_Array_Utils::advanced_explode("\n",$exclstr);
		FWS_Array_Utils::trim($excl);
		
		// determine files to scan
		foreach($folders as $folder)
		{
			$folder = trim($folder);
			if(is_file($folder))
				$files[] = $folder;
			else
			{
				foreach(FWS_FileUtils::get_list($folder,true,true) as $item)
				{
					if(!$this->is_excluded($item,$excl))
						$files[] = $item;
				}
			}
		}
		
		// clear position, just to be sure
		$storage = new FWS_Progress_Storage_Session('ptypescan_');
		$storage->clear();
		
		// store in session
		$user->set_session_data('typescan_files',$files);
		
		// store in project
		$project = FWS_Props::get()->project();
		$project->set_type_folders($folderstr);
		$project->set_type_exclude($exclstr);
		PC_DAO::get_projects()->update($project);
		
		// clear previous data in the db
		PC_DAO::get_classes()->delete_by_project($project->get_id());
		PC_DAO::get_functions()->delete_by_project($project->get_id());
		PC_DAO::get_constants()->delete_by_project($project->get_id());
		PC_DAO::get_classfields()->delete_by_project($project->get_id());
		PC_DAO::get_errors()->delete_by_type(
			PC_Obj_Error::get_types_of(PC_Obj_Error::R_TYPESCANNER),$project->get_id()
		);
		
		$this->set_redirect(true,PC_URL::get_submod_url(0,'cliscan'));
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
	
	/**
	 * Checks whether the given file is excluded
	 *
	 * @param string $file the file
	 * @param array $excl the excluded files
	 * @return boolean true if so
	 */
	private function is_excluded($file,$excl)
	{
		if(!FWS_String::ends_with($file,'.php'))
			return true;
		
		foreach($excl as $ex)
		{
			if(strpos($file,$ex) !== false)
				return true;
		}
		return false;
	}
}	
