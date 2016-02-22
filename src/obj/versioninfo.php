<?php
/**
 * Contains the version-info-class
 * 
 * @package			PHPCheck
 * @subpackage	src.obj
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
 * Is used to store the version information about functions and classes
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_VersionInfo extends FWS_Object
{
	/**
	 * The version since this method exists
	 * 
	 * @var array
	 */
	private $min = array();
	
	/**
	 * The version till this method exists
	 * 
	 * @var array
	 */
	private $max = array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->min = array();
		$this->max = array();
	}
	
	/**
	 * @return boolean true if no version is set
	 */
	public function is_empty()
	{
		return count($this->min) == 0 && count($this->max) == 0;
	}
	
	/**
	 * @return array the version since the method exists
	 */
	public function get_min()
	{
		return $this->min;
	}
	
	/**
	 * Adds the given version string to the minimum versions
	 *
	 * @param string $version the version
	 */
	public function add_min($version)
	{
		$this->min[] = $version;
	}
	
	/**
	 * @return array the version till the method exists
	 */
	public function get_max()
	{
		return $this->max;
	}
	
	/**
	 * Adds the given version string to the maximum versions
	 *
	 * @param string $version the version
	 */
	public function add_max($version)
	{
		$this->max[] = $version;
	}
	
	/**
	 * Sets the version info
	 *
	 * @param array $min the minimal versions
	 * @param array $max the maximal versions
	 */
	public function set($min,$max)
	{
		$this->min = array();
		$this->max = array();
		foreach($min as $v)
			$this->add_min($v);
		foreach($max as $v)
			$this->add_max($v);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
