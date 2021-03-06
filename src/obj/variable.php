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
class PC_Obj_Variable extends PC_Obj_Location
{
	/**
	 * Represents the global scope
	 */
	const SCOPE_GLOBAL = '#global';

	/**
	 * Creates a variable with given type and no value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param int $type the type
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_type($file,$line,$type,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_type($type));
	}
	
	/**
	 * Creates a variable with type OBJECT and given class-name
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param string $classname the class-name
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_object($file,$line,$classname,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_object($classname));
	}
	
	/**
	 * Creates a variable with type STRING and given value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param string $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_string($file,$line,$value = null,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_string($value));
	}
	
	/**
	 * Creates a variable with type TARRAY and given value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param array $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_array($file,$line,$value = null,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_array($value));
	}
	
	/**
	 * Creates a variable with type INT and given value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param int $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_int($file,$line,$value = null,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_int($value));
	}
	
	/**
	 * Creates a variable with type FLOAT and given value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param float $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_float($file,$line,$value = null,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_float($value));
	}
	
	/**
	 * Creates a multitype with type BOOL and given value
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param bool $value the value
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_bool($file,$line,$value = null,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_bool($value));
	}
	
	/**
	 * Creates a variable with type TCALLABLE
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param string $varname optionally, the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_callable($file,$line,$varname = '')
	{
		return new self($file,$line,$varname,PC_Obj_MultiType::create_callable());
	}
	
	/**
	 * Creates a variable from the given type
	 * 
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param string $name the variable-name
	 * @param PC_Obj_MultiType $type the type
	 * @param string $scope the scope
	 * @return PC_Obj_Variable the variable
	 */
	public static function create_from_scope($file,$line,$name,$type,$scope)
	{
		if($scope == self::SCOPE_GLOBAL)
			return new self($file,$line,$name,$type);
		if(strstr($scope,'::'))
		{
			list($class,$func) = explode('::',$scope);
			return new self($file,$line,$name,$type,$func,$class);
		}
		return new self($file,$line,$name,$type,$scope);
	}
	
	/**
	 * The call-id
	 * 
	 * @var int
	 */
	private $id = 0;
	/**
	 * For assigning values to array-elements: Store the reference to the array so that we can
	 * put the value into the array as soon as we assign it to it. Before the array doesn't know
	 * about this value (if it didn't exist before)
	 * 
	 * @var PC_Obj_Variable
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
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param string $name the name
	 * @param PC_Obj_MultiType $type the type
	 * @param string $function the function-name (scope)
	 * @param string $class the class-name (scope)
	 */
	public function __construct($file,$line,$name,$type = null,$function = '',$class = '')
	{
		parent::__construct($file,$line);
		
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
		{
			assert(!$this->arrayref->type->is_multiple() && !$this->arrayref->type->is_unknown());
			$this->arrayref->type->get_first()->set_array_type($this->arrayoff,$type);
		}
		$this->type = $type;
	}
	
	/**
	 * @return PC_Obj_Variable the array reference, if set_type() will set the type there
	 */
	public function get_array_ref()
	{
		return $this->arrayref;
	}
	
	/**
	 * Returns a variable pointing to the array-element with given key. If it does not exist, it
	 * will be created as soon as the type is assigned.
	 *
	 * @param PC_Obj_MultiType $key the key (null = append)
	 * @return PC_Obj_Variable the type of the element
	 */
	public function array_offset($key)
	{
		assert(!$this->type->is_multiple() && !$this->type->is_unknown());
		$first = $this->type->get_first();
		assert($first->get_type() == PC_Obj_Type::TARRAY);

		// fetch element or create it
		$akey = $key;
		if($key === null)
			$akey = $key = $first->get_next_array_key();
		else if(!$key->is_unknown())
		{
			$akey = $key;
			$key = $first->get_array_type($key->get_first()->get_value());
		}
		$var = new self($this->get_file(),$this->get_line(),'',$key);
		// connect the var to us
		$var->arrayref = $this;
		$var->arrayoff = $akey;
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
		if($scope == self::SCOPE_GLOBAL)
			$str .= PHP_SAPI == 'cli' ? '#global' : '<i>global</i>';
		else
			$str .= $scope;
		$str .= '['.$this->name.' = '.$this->type.']';
		return $str;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
