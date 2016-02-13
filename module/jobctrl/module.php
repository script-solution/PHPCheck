<?php
/**
 * Contains the jobctrl-module
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
 * The jobctrl-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_jobctrl extends PC_Module implements FWS_Job_Listener
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$doc->use_raw_renderer();
	}
	
	public function start_failed($job)
	{
		$this->add_error('Executing job "'.$job->get_command().'" failed');
	}
	
	public function before_sleep()
	{
	}
	
	public function after_sleep()
	{
	}
	
	public function finished($job)
	{
		// increase the number of finished jobs
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->increase_done();
		$mutex->write(serialize($data));
		$mutex->close();
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$user = FWS_Props::get()->user();
		
		$jobm = new FWS_Job_Manager();
		$jobm->set_parallel_count(PC_PARALLEL_JOB_COUNT);
		$jobm->set_poll_interval(PC_JOB_CTRL_POLL_INTERVAL);
		$jobm->add_listener($this);
		foreach($user->get_session_data('job_commands',array()) as $cmd)
			$jobm->add_job(new FWS_Job_Data($cmd));
		if(($fin = $user->get_session_data('job_finalizer','')) != '')
			$jobm->set_finalizer(new FWS_Job_Data($fin));
		
		$jobm->start();
		
		$user->delete_session_data('job_commands');
		$user->delete_session_data('job_finalizer');
	}
	
	/**
	 * Adds the given error to file
	 * 
	 * @param string $err the error
	 */
	private function add_error($err)
	{
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_error($err);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
