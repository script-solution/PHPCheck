<?php
/**
 * Contains the type-container-class
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
 * A container for types. Ensures that every type will be loaded just once
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_TypeContainer extends FWS_Object
{
	/**
	 * An array of already tried names, that did not exist
	 *
	 * @var array
	 */
	private $missing = array(
		'classes' => array(),
		'funcs' => array(),
		'methods' => array(),
		'consts' => array(),
	);
	
	/**
	 * All currently known classes
	 *
	 * @var array
	 */
	private $classes = array();
	/**
	 * All currently known functions
	 *
	 * @var array
	 */
	private $functions = array();
	/**
	 * All currently known methods
	 *
	 * @var array
	 */
	private $methods = array();
	/**
	 * All currently known constants
	 *
	 * @var array
	 */
	private $constants = array();
	/**
	 * The calls
	 * 
	 * @var array
	 */
	private $calls = array();
	
	/**
	 * The options
	 *
	 * @var PC_Engine_Options
	 */
	private $options;
	
	/**
	 * Constructor
	 *
	 * @param PC_Engine_Options $options the options
	 */
	public function __construct($options)
	{
		parent::__construct();
		
		if(!($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		
		$this->options = $options;
	}
	
	/**
	 * Prefetches all classes and functions from DB.
	 */
	public function prefetch()
	{
		foreach($this->options->get_projects() as $pid)
		{
			$this->add_classes(PC_DAO::get_classes()->get_list(0,0,'','',$pid));
			$this->add_functions(PC_DAO::get_functions()->get_list(array(0),0,0,'','',$pid));
		}
	}
	
	/**
	 * Adds all from the given type-container into this one (does not make clones of the objects!)
	 * 
	 * @param PC_Engine_TypeContainer $typecon the container
	 */
	public function add($typecon)
	{
		$this->add_functions($typecon->get_functions());
		$this->add_classes($typecon->get_classes());
		$this->add_constants($typecon->get_constants());
	}
	
	/**
	 * Adds all given classes to the container
	 *
	 * @param array $classes an array of classes
	 */
	public function add_classes($classes)
	{
		foreach($classes as $class)
			$this->classes[strtolower($class->get_name())] = $class;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <class>)</code>
	 */
	public function get_classes()
	{
		return $this->classes;
	}
	
	/**
	 * Returns the class with given name. Will fetch it from db if not already present
	 *
	 * @param string $name the class-name
	 * @return PC_Obj_Class the class or null if not found
	 */
	public function get_class($name)
	{
		if(empty($name))
			return null;
		
		$lname = strtolower($name);
		
		if(!isset($this->missing['classes'][$lname]))
		{
			if(!isset($this->classes[$lname]))
			{
				foreach($this->options->get_projects() as $pid)
				{
					$c = PC_DAO::get_classes()->get_by_name($name,$pid);
					if($c)
					{
						$this->classes[$lname] = $c;
						break;
					}
				}
			}
		}
		
		if(isset($this->classes[$lname]))
			return $this->classes[$lname];
		
		$this->missing['classes'][$lname] = true;
		return null;
	}
	
	/**
	 * Adds all given functions to the container
	 *
	 * @param array $funcs an array of functions
	 */
	public function add_functions($funcs)
	{
		foreach($funcs as $func)
			$this->functions[strtolower($func->get_name())] = $func;
	}
	
	/**
	 * Returns the (free) function with given name. Will fetch it from db if not already present
	 *
	 * @param string $name the function-name
	 * @return PC_Obj_Method the function or null if not found
	 */
	public function get_function($name)
	{
		if(empty($name))
			return null;
		
		$lname = strtolower($name);
		
		if(!isset($this->missing['funcs'][$lname]))
		{
			if(!isset($this->functions[$lname]))
			{
				foreach($this->options->get_projects() as $pid)
				{
					$f = PC_DAO::get_functions()->get_by_name($name,$pid);
					if($f)
					{
						$this->functions[$lname] = $f;
						break;
					}
				}
			}
		}
		
		if(isset($this->functions[$lname]))
			return $this->functions[$lname];
		
		$this->missing['funcs'][$lname] = true;
		return null;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <function>)</code>
	 */
	public function get_functions()
	{
		return $this->functions;
	}
	
	/**
	 * Returns the method or freestanding function with given name/class.
	 *
	 * @param string $class the class-name
	 * @param string $method the method-name
	 * @return PC_Obj_Method the function or null if not found
	 */
	public function get_method_or_func($class,$method)
	{
		if($class == '')
			return $this->get_function($method);
		return $this->get_method($class,$method);
	}
	
	/**
	 * Returns the method with given name. Will fetch it from db if not already present
	 *
	 * @param string $class the class-name
	 * @param string $method the method-name
	 * @return PC_Obj_Method the function or null if not found
	 */
	public function get_method($class,$method)
	{
		$lclass = strtolower($class);
		$lmethod = strtolower($method);
		
		if(!isset($this->missing['methods'][$lclass.'::'.$lmethod]))
		{
			// move the method over from the class, if necessary
			if(!isset($this->methods[$lclass.'::'.$lmethod]))
			{
				$cobj = $this->get_class($lclass);
				if($cobj && $cobj->contains_method($lmethod))
					$this->methods[$lclass.'::'.$lmethod] = $cobj->get_method($lmethod);
			}
			
			if(!isset($this->methods[$lclass.'::'.$lmethod]))
			{
				foreach($this->options->get_projects() as $pid)
				{
					$f = PC_DAO::get_functions()->get_by_name($method,$pid,$class);
					if($f)
					{
						$this->methods[$lclass.'::'.$lmethod] = $f;
						break;
					}
				}
			}
		}
		
		if(isset($this->methods[$lclass.'::'.$lmethod]))
			return $this->methods[$lclass.'::'.$lmethod];
		
		$this->missing['methods'][$lclass.'::'.$lmethod] = true;
		return null;
	}
	
	/**
	 * Searches for the given method in any superclass.
	 *
	 * @param string $class the class name
	 * @param string $name the method name
	 * @return PC_Obj_Method the method or null
	 */
	public function get_method_of_super($class,$name)
	{
		$cobj = $this->get_class($class);
		if(!$cobj)
			return null;
		if($cobj->contains_method($name))
			return $cobj->get_method($name);
		return $this->get_method_of_super($cobj->get_super_class(),$name);
	}
	
	/**
	 * Determines whether $class is a subclass of $super or if $super is an implemented interface.
	 *
	 * @param string $class the class name
	 * @param string $super the potential superclass name
	 * @return bool true if so
	 */
	public function is_subclass_of($class,$super)
	{
		$cobj = $this->get_class($class);
		if(!$cobj)
			return false;
		if(strcasecmp($cobj->get_super_class(),$super) == 0)
			return true;
		foreach($cobj->get_interfaces() as $if)
		{
			if(strcasecmp($if,$super) == 0 || $this->is_subclass_of($if,$super))
				return true;
		}
		return $this->is_subclass_of($cobj->get_super_class(),$super);
	}
	
	/**
	 * Checks whether $actual is okay for $spec.
	 *
	 * @param PC_Obj_MultiType $actual the actual type
	 * @param PC_Obj_MultiType $spec the specified type, i.e., the one to check against
	 * @return bool true if ok
	 */
	public function is_type_conforming($actual,$spec)
	{
		if($actual->is_unknown() || $spec->is_unknown())
			return true;
		
		// every actual type has to be contained in at least one of the specified types
		$count = 0;
		foreach($actual->get_types() as $atype)
		{
			$ok = false;
			foreach($spec->get_types() as $stype)
			{
				if($atype->equals($stype))
				{
					$ok = true;
					break;
				}
				
				// floats can accept ints
				if($atype->get_type() == PC_Obj_Type::INT && $stype->get_type() == PC_Obj_Type::FLOAT)
				{
					$ok = true;
					break;
				}
				
				// if both are objects, check if the actual is the same or a subclass of the spec
				$objs = $atype->get_type() == PC_Obj_Type::OBJECT &&
					$stype->get_type() == PC_Obj_Type::OBJECT;
				if($objs &&
					($stype->get_class() == '' ||
					 (strcasecmp($atype->get_class(),$stype->get_class()) ||
					 $this->is_subclass_of($atype->get_class(),$stype->get_class()))))
				{
					$ok = true;
					break;
				}
			}
			
			// early exit?
			if(!$ok && $this->options->get_report_argret_strictly())
				return false;
			if($ok && !$this->options->get_report_argret_strictly())
				return true;
			
			if($ok)
				$count++;
		}
		return $count > 0;
	}
	
	/**
	 * Adds all given constants to the container
	 *
	 * @param array $consts an array of constants
	 */
	public function add_constants($consts)
	{
		foreach($consts as $const)
			$this->constants[strtolower($const->get_name())] = $const;
	}
	
	/**
	 * Returns the (free) constant with given name. Will fetch it from db if not already present
	 *
	 * @param string $name the constant-name
	 * @return PC_Obj_Constant the constant or null if not found
	 */
	public function get_constant($name)
	{
		if(empty($name))
			return null;
		
		$lname = strtolower($name);
		
		if(!isset($this->missing['consts'][$lname]))
		{
			if(!isset($this->constants[$lname]))
			{
				foreach($this->options->get_projects() as $pid)
				{
					$c = PC_DAO::get_constants()->get_by_name($name,$pid);
					if($c)
					{
						$this->constants[$lname] = $c;
						break;
					}
				}
			}
		}
		
		if(isset($this->constants[$lname]))
			return $this->constants[$lname];
		
		$this->missing['consts'][$lname] = true;
		return null;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <const>)</code>
	 */
	public function get_constants()
	{
		return $this->constants;
	}
	
	/**
	 * Adds the given call
	 * 
	 * @param PC_Obj_Call $call the call
	 */
	public function add_call($call)
	{
		$this->calls[] = $call;
	}
	
	/**
	 * @return array the found function-calls
	 */
	public function get_calls()
	{
		return $this->calls;
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
