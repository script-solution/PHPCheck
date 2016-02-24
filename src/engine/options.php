<?php
/**
 * Contains the options-class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Contains the configuration options for the engine.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_Options extends FWS_Object
{
	/**
	 * The project-id
	 *
	 * @var int
	 */
	private $pid = PC_Project::CURRENT_ID;
	
	/**
	 * Whether errors with mixed types involved should be reported
	 * 
	 * @var boolean
	 */
	private $report_mixed = false;
	/**
	 * Whether errors with unknown types involved should be reported
	 * 
	 * @var boolean
	 */
	private $report_unknown = false;
	
	/**
	 * Wether the db should be queried if a type can't be found
	 *
	 * @var bool
	 */
	private $use_db = true;
	/**
	 * Whether to query also the phpref-entries in the db
	 *
	 * @var bool
	 */
	private $use_phpref = true;
	
	/**
	 * @return int the project id
	 */
	public function get_pid()
	{
		return $this->pid;
	}
	
	/**
	 * Sets the project id
	 *
	 * @param int $pid the project id
	 */
	public function set_pid($pid)
	{
		$this->pid = $pid;
	}
	
	/**
	 * @return bool whether errors with mixed types involved should be reported
	 */
	public function get_report_mixed()
	{
		return $this->report_mixed;
	}
	
	/**
	 * Sets whether errors with mixed types involved should be reported
	 * 
	 * @param bool $report the new value
	 */
	public function set_report_mixed($report)
	{
		$this->report_mixed = $report;
	}
	
	/**
	 * @return bool whether errors with unknown types involved should be reported
	 */
	public function get_report_unknown()
	{
		return $this->report_unknown;
	}
	
	/**
	 * Sets whether errors with unknown types involved should be reported
	 * 
	 * @param bool $report the new value
	 */
	public function set_report_unknown($report)
	{
		$this->report_unknown = $report;
	}
	
	/**
	 * @return bool whether the DB should be used
	 */
	public function get_use_db()
	{
		return $this->use_db;
	}
	
	/**
	 * Sets whether the DB should be used.
	 * 
	 * @param bool $use the new value
	 */
	public function set_use_db($use)
	{
		$this->use_db = $use;
	}
	
	/**
	 * @return bool whether the PHP reference should be used
	 */
	public function get_use_phpref()
	{
		return $this->use_phpref;
	}
	
	/**
	 * Sets whether the PHP reference should be used.
	 * 
	 * @param bool $use the new value
	 */
	public function set_use_phpref($use)
	{
		$this->use_phpref = $use;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
