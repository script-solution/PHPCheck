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
	 * Report an error if only one possible type of arguments/returns violates the spec.
	 * 
	 * @var boolean
	 */
	private $report_argret_strictly = false;
	/**
	 * Whether unused variables should be reported.
	 *
	 * @var boolean
	 */
	private $report_unused = false;
	
	/**
	 * Whether the db should be queried if a type can't be found
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
	 * The minimum requirement (PHP, PECL modules, ...)
	 *
	 * @var array
	 */
	private $min_req = array();
	/**
	 * The maximum requirement (PHP, PECL modules, ...), i.e., the first unsupported versions
	 *
	 * @var array
	 */
	private $max_req = array();
	
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
	 * @return bool whether to report an error if only one possible type violates the spec.
	 */
	public function get_report_argret_strictly()
	{
		return $this->report_argret_strictly;
	}
	
	/**
	 * Sets whether to report an error if only one possible type violates the spec.
	 * 
	 * @param bool $report the new value
	 */
	public function set_report_argret_strictly($report)
	{
		$this->report_argret_strictly = $report;
	}
	
	/**
	 * @return bool whether unused variables should be reported
	 */
	public function get_report_unused()
	{
		return $this->report_unused;
	}
	
	/**
	 * Sets whether unused variables should be reported
	 * 
	 * @param bool $report the new value
	 */
	public function set_report_unused($report)
	{
		$this->report_unused = $report;
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
	
	/**
	 * @return array an array of minimum requirements (inclusive): array(<name> => <version>)
	 */
	public function get_min_req()
	{
		return $this->min_req;
	}
	
	/**
	 * Adds the given minimum requirement (inclusive).
	 * 
	 * @param string $name the component name (e.g., PHP or PECL pdo)
	 * @param string $version the version
	 */
	public function add_min_req($name,$version)
	{
		$this->min_req[$name] = $version;
	}
	
	/**
	 * @return array an array of maximum requirements (exclusive): array(<name> => <version>)
	 */
	public function get_max_req()
	{
		return $this->max_req;
	}
	
	/**
	 * Adds the given maximum requirement (exclusive).
	 * 
	 * @param string $name the component name (e.g., PHP or PECL pdo)
	 * @param string $version the version
	 */
	public function add_max_req($name,$version)
	{
		$this->max_req[$name] = $version;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
