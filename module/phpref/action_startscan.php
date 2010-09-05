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
final class PC_Action_PHPRef_startscan extends FWS_Action_Base
{
	public function perform_action()
	{
		$input = FWS_Props::get()->input();
		$user = FWS_Props::get()->user();
		
		$files = array();
		foreach(FWS_FileUtils::get_list('phpman',false,false) as $file)
		{
			if(preg_match('/^(function|class)\./',$file))
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