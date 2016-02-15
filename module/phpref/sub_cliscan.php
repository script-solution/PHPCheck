<?php
/**
 * Contains the phpref-submodule that uses a CLI script to work parallel
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
 * The scancli submodule for module phpref
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_phpref_cliscan extends PC_SubModule
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
		$renderer->add_breadcrumb('Scanning...');
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$user = FWS_Props::get()->user();
		$tpl = FWS_Props::get()->tpl();
		
		$jobs = array();
		$files = $user->get_session_data('phpref_files');
		for($i = 0; $i < count($files); $i += PC_PHPREF_PAGES_PER_CYCLE)
		{
			$args = array();
			for($j = 0, $count = min(count($files) - $i,PC_PHPREF_PAGES_PER_CYCLE); $j < $count; $j++)
				$args[] = escapeshellarg($files[$i + $j]);
			$jobs[] = PC_PHP_EXEC.' cli.php phpref '.implode(' ',$args);
		}
		
		$user->delete_session_data('phpref_files');
		$user->set_session_data('job_commands',$jobs);
		$user->set_session_data('job_finalizer',PC_PHP_EXEC.' cli.php phpreffin');
		
		// init shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = new PC_JobData();
		$data->set_misc(array('versions' => array(),'aliases' => array()));
		$mutex->write(serialize($data));
		$mutex->close();
		
		$tpl->add_variables(array(
			'file_count' => count($files),
			'files_per_job' => PC_PHPREF_PAGES_PER_CYCLE,
			'check_interval' => PC_JOB_CTRL_POLL_INTERVAL / 1000
		));
	}
}
