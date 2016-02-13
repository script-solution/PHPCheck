<?php
/**
 * Contains the variable-class
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
 * Is used to store all properties of variables
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Variable extends FWS_Object
{
	/**
	 * Represents the global scope
	 */
	const SCOPE_GLOBAL = '#global';

	/**
	 * Creates a variable with given type and no value
	 * 
	 * @param int $type the type
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_type($type,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_type($type));
	}
	
	/**
	 * Creates a variable with type OBJECT and given class-name
	 * 
	 * @param string $classname the class-name
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_object($classname,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_object($classname));
	}
	
	/**
	 * Creates a variable with type STRING and given value
	 * 
	 * @param string $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_string($value = null,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_string($value));
	}
	
	/**
	 * Creates a variable with type TARRAY and given value
	 * 
	 * @param array $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_array($value = null,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_array($value));
	}
	
	/**
	 * Creates a variable with type INT and given value
	 * 
	 * @param int $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_int($value = null,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_int($value));
	}
	
	/**
	 * Creates a variable with type FLOAT and given value
	 * 
	 * @param float $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_float($value = null,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_float($value));
	}
	
	/**
	 * Creates a multitype with type BOOL and given value
	 * 
	 * @param bool $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_bool($value = null,$varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_bool($value));
	}
	
	/**
	 * Creates a variable with type TCALLABLE
	 * 
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_callable($varname = '')
	{
		return new self($varname,PC_Obj_MultiType::create_callable());
	}
	
	/**
	 * For assigning values to array-elements: Store the reference to the array so that we can
	 * put the value into the array as soon as we assign it to it. Before the array doesn't know
	 * about this value (if it didn't exist before)
	 * 
	 * @var PC_Obj_Type
	 */
	private $arrayref = null;
	/**
	 * For assigning values to array-elements: Store the offset for the reference-array
	 * 
	 * @var mixed
	 */
	private $arrayoff = null;
	
	/**
	 * The name of the variable
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * If method/function-scope: the function-name
	 *
	 * @var string
	 */
	private $function;
	
	/**
	 * If method-scope: the class-name
	 *
	 * @var string
	 */
	private $class;
	
	/**
	 * The type of the variable
	 *
	 * @var PC_Obj_MultiType
	 */
	private $type;
	
	/**
	 * Constructor
	 * 
	 * @param string $name the name
	 * @param PC_Obj_MultiType $type the type
	 * @param string $function the function-name (scope)
	 * @param string $class the class-name (scope)
	 */
	public function __construct($name,$type = null,$function = '',$class = '')
	{
		parent::__construct();
		
		if($type !== null && !($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->name = $name;
		$this->type = $type ? $type : new PC_Obj_MultiType();
		$this->function = $function;
		$this->class = $class;
	}
	
	public function __clone()
	{
		parent::__clone();
		$this->type = clone $this->type;
		$this->arrayref = null;
		$this->arrayoff = null;
	}
	
	/**
	 * @return string the name
	 */
	public function get_name()
	{
		return $this->name;
	}
	
	/**
	 * @return PC_Obj_MultiType the type
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	/**
	 * Sets the type
	 * 
	 * @param PC_Obj_MultiType $type the new value
	 */
	public function set_type($type)
	{
		if(!($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		if($this->arrayref !== null)
			$this->arrayref->set_array_type($this->arrayoff,$type);
		$this->type = $type;
	}
	
	/**
	 * Returns a variable pointing to the array-element with given key. If it does not exist, it
	 * will be created as soon as the type is assigned.
	 *
	 * @param mixed $key the key (null = append)
	 * @return PC_Obj_Variable the type of the element
	 */
	public function array_offset($key)
	{
		assert(!$this->type->is_multiple() && !$this->type->is_unknown());
		$first = $this->type->get_first();
		assert($first->get_type() == PC_Obj_Type::TARRAY);
		if($key === null)
			$key = $first->get_next_array_key();
		
		// fetch element or create it
		$el = $first->get_array_type($key);
		if($el === null)
			$el = new PC_Obj_MultiType();
		$var = new self('',$el);
		// connect the var to us
		$var->arrayref = $first;
		$var->arrayoff = $key;
		return $var;
	}
	
	/**
	 * @return string the function-name (scope)
	 */
	public function get_function()
	{
		return $this->function;
	}
	
	/**
	 * Sets the function
	 * 
	 * @param string $function the new value
	 */
	public function set_function($function)
	{
		$this->function = $function;
	}
	
	/**
	 * @return string the class-name (scope)
	 */
	public function get_class()
	{
		return $this->class;
	}
	
	/**
	 * Sets the class
	 * 
	 * @param string $class the new value
	 */
	public function set_class($class)
	{
		$this->class = $class;
	}
	
	/**
	 * @return string the variable-scope
	 */
	public function get_scope()
	{
		if(!$this->function && !$this->class)
			return self::SCOPE_GLOBAL;
		if($this->class)
			return $this->class.'::'.$this->function;
		return $this->function;
	}
	
	public function __toString()
	{
		$str = '';
		$scope = $this->get_scope();
		$str .= $scope == self::SCOPE_GLOBAL ? '<i>global</i>' : $scope;
		$str .= '['.$this->name.' = '.$this->type.']';
		return $str;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
