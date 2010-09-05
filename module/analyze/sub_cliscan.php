<?php
/**
 * Contains the analyze-scan-submodule that uses a CLI script to work parallel
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The scancli submodule for module analyze
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_analyze_cliscan extends PC_SubModule
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
		$renderer->add_breadcrumb('Analyzing...');
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$user = FWS_Props::get()->user();
		$tpl = FWS_Props::get()->tpl();
		
		$jobs = array(
			'php cli.php analyzecalls',
			'php cli.php analyzetypes'
		);
		$user->set_session_data('job_commands',$jobs);
		
		// init shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$mutex->write(serialize(new PC_JobData()));
		$mutex->close();
		
		$tpl->add_variables(array(
			'file_count' => 2,
			'files_per_job' => 1,
			'check_interval' => PC_JOB_CTRL_POLL_INTERVAL / 1000
		));
	}
}
?>