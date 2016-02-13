<?php
/**
 * Contains the constant-class
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
 * Represents a constant
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Obj_Constant extends PC_Obj_Location
{
	/**
	 * The name of the constant
	 *
	 * @var string
	 */
	private $_name;
	
	/**
	 * The type (and maybe value)
	 *
	 * @var PC_Obj_MultiType
	 */
	private $_type;
	
	/**
	 * The class-id
	 * 
	 * @var int
	 */
	private $_class;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 * @param string $name the constant-name
	 * @param PC_Obj_MultiType $type the type
	 * @param int $classid the class-id if loaded from db
	 */
	public function __construct($file,$line,$name,$type = null,$classid = 0)
	{
		parent::__construct($file,$line);
		
		$this->set_name($name);
		$this->set_type($type);
		$this->_class = $classid;
	}
	
	public function __clone()
	{
		parent::__clone();
		
		if($this->_type !== null)
			$this->_type = clone $this->_type;
	}
	
	/**
	 * @return int the class-id (just present if loaded from db!)
	 */
	public function get_class()
	{
		return $this->_class;
	}
	
	/**
	 * @return string the constant-name
	 */
	public function get_name()
	{
		return $this->_name;
	}
	
	/**
	 * Sets the name
	 * 
	 * @param string $name the new name
	 */
	public function set_name($name)
	{
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$this->_name = $name;
	}
	
	/**
	 * @return PC_Obj_MultiType the type (and maybe value) of the constant
	 */
	public function get_type()
	{
		return $this->_type;
	}
	
	/**
	 * Sets the type
	 * 
	 * @param PC_Obj_MultiType $type the new type
	 */
	public function set_type($type)
	{
		if($type !== null && !($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->_type = $type;
	}
	
	public function __toString()
	{
		return 'const '.$this->_name.'['.$this->_type.']';
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
