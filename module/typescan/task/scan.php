<?php
/**
 * Contains the typescan-task
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
 * The task to scan for types
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_TypeScan_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('typescan_files',array()));
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		$user = FWS_Props::get()->user();
		$msgs = FWS_Props::get()->msgs();
		
		$files = $user->get_session_data('typescan_files',array());
		
		$options = new PC_Engine_Options();
		$tscanner = new PC_Engine_TypeScannerFrontend($options);
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
		{
			try
			{
				$tscanner->scan_file($files[$i]);
			}
			catch(PC_Engine_Exception $e)
			{
				$msgs->add_error($e->__toString());
			}
		}
		
		// insert into db and session
		$typecon = $tscanner->get_types();
		foreach($typecon->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($typecon->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($typecon->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
		foreach($typecon->get_errors() as $err)
			PC_DAO::get_errors()->create($err);
		
		// finish all classes if the typescan is finished
		if($pos + $ops >= $this->get_total_operations())
		{
			$typecon = new PC_Engine_TypeContainer($options);
			$typecon->add_classes(PC_DAO::get_classes()->get_list());
			$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_DB());
			$fin->finalize();
		}
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
