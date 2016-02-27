<?php
/**
 * Contains the job-data-class
 * 
 * @package			PHPCheck
 * @subpackage	src
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
 * A class that holds all data that is shared between all currently running jobs
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_JobData
{
	/**
	 * The number of jobs done
	 * 
	 * @var int
	 */
	private $done = 0;
	/**
	 * The errors that occurred
	 * 
	 * @var array
	 */
	private $errors = array();
	/**
	 * Misc data for other purposes
	 * 
	 * @var mixed
	 */
	private $misc = null;
	
	/**
	 * @return int the number of jobs done so far
	 */
	public function get_done()
	{
		return $this->done;
	}
	
	/**
	 * Increases the number of finished jobs by 1
	 */
	public function increase_done()
	{
		$this->done++;
	}
	
	/**
	 * @return array the occurred errors
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * Adds the given error
	 * 
	 * @param string $msg the error-message
	 */
	public function add_error($msg)
	{
		if(!is_string($msg))
			FWS_Helper::def_error('string','msg',$msg);
		$this->errors[] = $msg;
	}
	
	/**
	 * Adds all given errors
	 * 
	 * @param array $errors
	 */
	public function add_errors($errors)
	{
		if(!is_array($errors))
			FWS_Helper::def_error('array','errors',$errors);
		$this->errors = array_merge($this->errors,$errors);
	}
	
	/**
	 * @return mixed the misc-data
	 */
	public function get_misc()
	{
		return $this->misc;
	}
	
	/**
	 * Sets the misc-data
	 * 
	 * @param mixed $misc the new value
	 */
	public function set_misc($misc)
	{
		$this->misc = $misc;
	}
}
