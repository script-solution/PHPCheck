<?php
/**
 * Contains the stmtscan-task
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
 * The task to scan for statements
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_StmtScan_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * The number of calls we insert into the db at once.
	 * 20 seems to be a good value. A lower and higher value leads (for me) to worse performance
	 * 
	 * @var int
	 */
	const CALLS_AT_ONCE		= 20;
	
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('stmtscan_files',array()));
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
		
		// scan for statements
		$options = new PC_Engine_Options();
		$types = new PC_Engine_TypeContainer($options);
		$ascanner = new PC_Engine_StmtScannerFrontend($types,$options);
		$files = $user->get_session_data('stmtscan_files',array());
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
		{
			try
			{
				$ascanner->scan_file($files[$i]);
			}
			catch(PC_Engine_Exception $e)
			{
				$msgs->add_error($e->__toString());
			}
		}
		
		// insert vars and calls into db
		$calls = $types->get_calls();
		for($i = 0, $len = count($calls); $i < $len; $i += self::CALLS_AT_ONCE)
			PC_DAO::get_calls()->create_bulk(array_slice($calls,$i,self::CALLS_AT_ONCE));
		foreach($types->get_errors() as $err)
			PC_DAO::get_errors()->create($err);
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
