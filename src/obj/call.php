<?php
/**
 * Contains the call-class
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
 * Is used to store information about a function-/method-call
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Call extends PC_Obj_Location
{
	/**
	 * The call-id
	 * 
	 * @var int
	 */
	private $id = 0;
	/**
	 * The function-name
	 *
	 * @var string
	 */
	private $function;
	
	/**
	 * The class-name
	 *
	 * @var string
	 */
	private $class = null;
	
	/**
	 * Indicates whether this function-call is an object-creation (new)
	 *
	 * @var boolean
	 */
	private $objcreation = false;
	
	/**
	 * Whether the method is static
	 *
	 * @var boolean
	 */
	private $static = false;
	
	/**
	 * The arguments passed to the function/method
	 * 
	 * @var array
	 */
	private $arguments = array();

	/**
	 * Constructor
	 *
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 */
	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
	}
	
	/**
	 * @return int the id
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * Sets the id
	 * 
	 * @param int $id the new value
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return boolean whether the call is static
	 */
	public function is_static()
	{
		return $this->static;
	}
	
	/**
	 * Sets whether the call is static
	 *
	 * @param boolean $static the new value
	 */
	public function set_static($static)
	{
		$this->static = (bool)$static;
	}
	
	/**
	 * @return boolean whether the call is an object-creation
	 */
	public function is_object_creation()
	{
		return $this->objcreation;
	}
	
	/**
	 * Sets whether the call is an object-creation
	 *
	 * @param boolean $val the new value
	 */
	public function set_object_creation($val)
	{
		$this->objcreation = (bool)$val;
	}

	/**
	 * @return string the function-name
	 */
	public function get_function()
	{
		return $this->function;
	}

	/**
	 * Sets the function-name
	 *
	 * @param string $function the new value
	 */
	public function set_function($function)
	{
		$this->function = $function;
	}

	/**
	 * @return string the class-name
	 */
	public function get_class()
	{
		return $this->class;
	}

	/**
	 * Sets the class-name
	 *
	 * @param string $class the new value
	 */
	public function set_class($class)
	{
		$this->class = $class;
	}

	/**
	 * @return array the passed arguments to the function/method
	 */
	public function get_arguments()
	{
		return $this->arguments;
	}

	/**
	 * Adds the given type as argument
	 *
	 * @param PC_Obj_MultiType $type the type
	 */
	public function add_argument($type)
	{
		if(!($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->arguments[] = clone $type;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		if($this->class && $this->class == PC_Obj_Class::UNKNOWN)
			$classname = '<i>UNKNOWN</i>';
		else
			$classname = $this->class;
		
		$str = '';
		if($classname)
			$str .= $classname.($this->static ? '::' : '->');
		$str .= $this->function.'('.implode(', ',$this->arguments).')';
		
		return $str;
	}
}
