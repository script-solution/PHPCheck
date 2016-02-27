<?php
/**
 * Contains the modifiable-class
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
 * Stores whether an "object" is abstract or final and the name.
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Modifiable extends PC_Obj_Location
{
	/**
	 * Is it abstract?
	 *
	 * @var boolean
	 */
	private $abstract = false;
	
	/**
	 * Is it final?
	 *
	 * @var boolean
	 */
	private $final = false;
	
	/**
	 * The name
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 */
	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
	}
	
	/**
	 * @return string the class-name
	 */
	public function get_name()
	{
		return $this->name;
	}
	
	/**
	 * Sets the name
	 *
	 * @param string $name the new value
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return boolean whether it is final
	 */
	public function is_final()
	{
		return $this->final;
	}
	
	/**
	 * Sets whether it is final
	 *
	 * @param boolean $final the new value
	 */
	public function set_final($final)
	{
		$this->final = (bool)$final;
	}
	
	/**
	 * @return boolean whether it is abstract
	 */
	public function is_abstract()
	{
		return $this->abstract;
	}
	
	/**
	 * Sets whether it is abstract
	 *
	 * @param boolean $abstract the new value
	 */
	public function set_abstract($abstract)
	{
		$this->abstract = (bool)$abstract;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
