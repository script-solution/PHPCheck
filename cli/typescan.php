<?php
/**
 * Contains the cli-type-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
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
 * The cli-type-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_TypeScan implements PC_CLIJob
{
	public function run($args)
	{
		$env = new PC_Engine_Env();
		$tscanner = new PC_Engine_TypeScannerFrontend($env);

		$msgs = array();
		foreach($args as $file)
		{
			try
			{
				$tscanner->scan_file($file);
			}
			catch(PC_Engine_Exception $e)
			{
				$msgs[] = $e->__toString();
			}
		}
		
		foreach($env->get_types()->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($env->get_types()->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($env->get_types()->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
		foreach($env->get_errors()->get() as $err)
			PC_DAO::get_errors()->create($err);
		
		// write msgs to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_errors($msgs);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
