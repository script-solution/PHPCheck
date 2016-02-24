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
final class PC_Module_Analyze_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * The number of calls
	 *
	 * @var int
	 */
	private $_callnum;
	
	/**
	 * The number of classes
	 *
	 * @var int
	 */
	private $_classnum;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_callnum = PC_DAO::get_calls()->get_count();
		$this->_classnum = PC_DAO::get_classes()->get_count();
	}
	
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		return $this->_callnum + $this->_classnum;
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		$project = FWS_Props::get()->project();
		$options = new PC_Engine_Options();
		if($project !== null)
		{
			$options->set_report_mixed($project->get_report_mixed());
			$options->set_report_unknown($project->get_report_unknown());
		}
		
		$an = new PC_Engine_Analyzer($options);
		$types = new PC_Engine_TypeContainer($options);
		
		// we need all classes in the type-container to be able to search for sub-classes and interface-
		// implementations
		$types->add_classes(PC_DAO::get_classes()->get_list());
		
		// analyze calls
		if($pos < $this->_callnum)
		{
			$calls = PC_DAO::get_calls()->get_list($pos,$ops);
			$an->analyze_calls($types,$calls);
			$pos += count($calls);
		}
		
		// analyze types
		if($pos >= $this->_callnum)
		{
			$classes = PC_DAO::get_classes()->get_list($pos - $this->_callnum,$ops);
			$an->analyze_classes($types,$classes);
		}
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
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
