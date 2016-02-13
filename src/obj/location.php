<?php
/**
 * Contains the location-class
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
 * Is used as base-class for all objects that have a location (file,line)
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Location extends FWS_Object
{
	/**
	 * The file
	 *
	 * @var string
	 */
	private $file;
	
	/**
	 * The line
	 *
	 * @var int
	 */
	private $line;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 */
	public function __construct($file,$line)
	{
		parent::__construct();
		
		$this->file = $file;
		$this->line = $line;
	}

	/**
	 * @return string the file
	 */
	public function get_file()
	{
		return $this->file;
	}

	/**
	 * @return int the line
	 */
	public function get_line()
	{
		return $this->line;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
