<?php
/**
 * Contains the startscan-action
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The startscan-action
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_typescan_startscan extends FWS_Actions_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$user = FWS_Props::get()->user();
		$folderstr = $input->get_var('add_folders','post',FWS_Input::STRING);
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
				foreach(FWS_FileUtils::get_dir_content($folder,true,true) as $item)
				{
					if(!$this->_is_excluded($item,$excl))
						$files[] = $item;
				}
			}
		}
		
		// store in session
		$user->set_session_data('typescan_files',$files);
		
		// clear previous data in the db
		$project = FWS_Props::get()->project();
		PC_DAO::get_classes()->delete_by_project($project->get_id());
		PC_DAO::get_functions()->delete_by_project($project->get_id());
		PC_DAO::get_constants()->delete_by_project($project->get_id());
		PC_DAO::get_classfields()->delete_by_project($project->get_id());
		
		$this->set_redirect(true,PC_URL::get_submod_url(0,'scan'));
		$this->set_show_status_page(false);
		$this->set_action_performed(true);

		return '';
	}
	
	/**
	 * Checks wether the given file is excluded
	 *
	 * @param string $file the file
	 * @param array $excl the excluded files
	 * @return boolean true if so
	 */
	private function _is_excluded($file,$excl)
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
?>