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
		$errors = array();
		$tscanner = new PC_Engine_TypeScannerFrontend();
		foreach($args as $file)
		{
			try
			{
				$tscanner->scan_file($file);
			}
			catch(PC_Engine_Exception $e)
			{
				$errors[] = $e->__toString();
			}
		}
		
		$typecon = $tscanner->get_types();
		foreach($typecon->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($typecon->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($typecon->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
		foreach($typecon->get_errors() as $err)
			PC_DAO::get_errors()->create($err);
		
		// write errors to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_errors($errors);
		$mutex->write(serialize($data));
		$mutex->close();
	}
}
