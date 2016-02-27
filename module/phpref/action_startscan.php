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
final class PC_Action_PHPRef_startscan extends FWS_Action_Base
{
	public function perform_action()
	{
		$user = FWS_Props::get()->user();
		
		$files = array();
		foreach(FWS_FileUtils::get_list('phpman',false,false) as $file)
		{
			if(preg_match('/\.html$/',$file))
				$files[] = 'phpman/'.$file;
		}
		// store in session
		$user->set_session_data('phpref_files',$files);
		$user->set_session_data('phpref_aliases',array());
		$user->set_session_data('phpref_versions',array());
		
		// clear position, just to be sure
		$storage = new FWS_Progress_Storage_Session('pphprefscan_');
		$storage->clear();
		
		// clear previous data in the db
		PC_DAO::get_functions()->delete_by_project(PC_Project::PHPREF_ID);
		PC_DAO::get_classes()->delete_by_project(PC_Project::PHPREF_ID);
		
		$this->set_redirect(true,PC_URL::get_submod_url(0,'cliscan'));
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
}
