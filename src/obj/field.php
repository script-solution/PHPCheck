<?php
/**
 * Contains the field-class
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
 * Is used for class-fields
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Field extends PC_Obj_Location implements PC_Obj_Visible
{
	/**
	 * The name of the field
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The visibility
	 *
	 * @var string
	 */
	private $visibility;
	
	/**
	 * The type of the variable
	 *
	 * @var PC_Obj_MultiType
	 */
	private $type;
	
	/**
	 * Wether the field is static
	 *
	 * @var boolean
	 */
	private $static = false;
	
	/**
	 * The class-id
	 * 
	 * @var int
	 */
	private $class;
	
	/**
	 * Constructor
	 * 
	 * @param string $file the file of the field
	 * @param int $line the line of the field
	 * @param string $name the name of the field
	 * @param PC_Obj_MultiType $type the type of the field
	 * @param string $visibility the visibility
	 * @param int $classid the class-id if loaded from db
	 */
	public function __construct($file,$line,$name = '',$type = null,$visibility = self::V_PUBLIC,$classid = 0)
	{
		parent::__construct($file,$line);
		
		$this->name = $name;
		$this->visibility = $visibility;
		$this->class = $classid;
		$type = $type === null ? new PC_Obj_MultiType() : $type;
		$this->set_type($type);
	}
	
	public function __clone()
	{
		parent::__clone();
		
		$this->type = clone $this->type;
	}
	
	/**
	 * @return int the class-id (just present if loaded from db!)
	 */
	public function get_class()
	{
		return $this->class;
	}
	
	/**
	 * @return string the name
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
	 * @return boolean wether the method is static
	 */
	public function is_static()
	{
		return $this->static;
	}
	
	/**
	 * Sets wether the method is static
	 *
	 * @param boolean $static the new value
	 */
	public function set_static($static)
	{
		$this->static = (bool)$static;
	}
	
	/**
	 * @return PC_Obj_MultiType the type of the variable
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	/**
	 * Sets the type of the variable
	 *
	 * @param PC_Obj_MultiType $type the new value
	 */
	public function set_type($type)
	{
		if(!($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->type = $type;
	}
	
	/**
	 * @see PC_Obj_Visible::get_visibility()
	 * 
	 * @return string
	 */
	public function get_visibility()
	{
		return $this->visibility;
	}
	
	/**
	 * @see PC_Obj_Visible::set_visibility()
	 *
	 * @param string $visibility
	 */
	public function set_visibility($visibility)
	{
		$valid = array(self::V_PUBLIC,self::V_PROTECTED,self::V_PRIVATE);
		if(!in_array($visibility,$valid))
			FWS_Helper::def_error('inarray','visibility',$valid,$visibility);
		
		$this->visibility = $visibility;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		$str = $this->visibility.' '.($this->static ? 'static ' : '').$this->get_name();
		if(!$this->type->is_unknown())
			$str .= '['.$this->type.']';
		return $str;
	}
}
