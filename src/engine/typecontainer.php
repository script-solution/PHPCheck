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
	private $_missing = array(
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
	private $_classes = array();
	/**
	 * All currently known functions
	 *
	 * @var array
	 */
	private $_functions = array();
	/**
	 * All currently known methods
	 *
	 * @var array
	 */
	private $_methods = array();
	/**
	 * All currently known constants
	 *
	 * @var array
	 */
	private $_constants = array();
	/**
	 * The calls
	 * 
	 * @var array
	 */
	private $_calls = array();
	
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
	 * @return PC_Engine_Options the options
	 */
	public function get_options()
	{
		return $this->options;
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
			$this->_classes[$class->get_name()] = $class;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <class>)</code>
	 */
	public function get_classes()
	{
		return $this->_classes;
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
		if(!isset($this->_missing['classes'][$name]))
		{
			if(!isset($this->_classes[$name]) && $this->options->get_use_db())
			{
				$c = PC_DAO::get_classes()->get_by_name($name,$this->options->get_pid());
				if($c)
					$this->_classes[$name] = $c;
				else
					$this->_missing['classes'][$name] = true;
			}
			if(!isset($this->_classes[$name]) && $this->options->get_use_phpref())
			{
				$c = PC_DAO::get_classes()->get_by_name($name,PC_Project::PHPREF_ID);
				if($c)
					$this->_classes[$name] = $c;
				else
					$this->_missing['classes'][$name] = true;
			}
		}
		if(isset($this->_classes[$name]))
			return $this->_classes[$name];
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
			$this->_functions[$func->get_name()] = $func;
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
		if(!isset($this->_missing['funcs'][$name]))
		{
			if(!isset($this->_functions[$name]) && $this->options->get_use_db())
			{
				$f = PC_DAO::get_functions()->get_by_name($name,$this->options->get_pid());
				if($f)
					$this->_functions[$name] = $f;
				else
					$this->_missing['funcs'][$name] = true;
			}
			if(!isset($this->_functions[$name]) && $this->options->get_use_phpref())
			{
				$f = PC_DAO::get_functions()->get_by_name($name,PC_Project::PHPREF_ID);
				if($f)
					$this->_functions[$name] = $f;
				else
					$this->_missing['funcs'][$name] = true;
			}
		}
		if(isset($this->_functions[$name]))
			return $this->_functions[$name];
		return null;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <function>)</code>
	 */
	public function get_functions()
	{
		return $this->_functions;
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
		if(!isset($this->_missing['methods'][$class.'::'.$method]))
		{
			// move the method over from the class, if necessary
			if(!isset($this->_methods[$class.'::'.$method]))
			{
				$cobj = $this->get_class($class);
				if($cobj && $cobj->contains_method($method))
					$this->_methods[$class.'::'.$method] = $cobj->get_method($method);
			}
			
			if(!isset($this->_methods[$class.'::'.$method]) && $this->options->get_use_db())
			{
				$f = PC_DAO::get_functions()->get_by_name($method,$this->options->get_pid(),$class);
				if($f)
					$this->_methods[$class.'::'.$method] = $f;
				else
					$this->_missing['methods'][$class.'::'.$method] = true;
			}
			if(!isset($this->_methods[$class.'::'.$method]) && $this->options->get_use_phpref())
			{
				$f = PC_DAO::get_functions()->get_by_name($method,PC_Project::PHPREF_ID,$class);
				if($f)
					$this->_methods[$class.'::'.$method] = $f;
				else
					$this->_missing['methods'][$class.'::'.$method] = true;
			}
		}
		if(isset($this->_methods[$class.'::'.$method]))
			return $this->_methods[$class.'::'.$method];
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
	 * Adds all given constants to the container
	 *
	 * @param array $consts an array of constants
	 */
	public function add_constants($consts)
	{
		foreach($consts as $const)
			$this->_constants[$const->get_name()] = $const;
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
		if(!isset($this->_missing['consts'][$name]))
		{
			if(!isset($this->_constants[$name]) && $this->options->get_use_db())
			{
				$c = PC_DAO::get_constants()->get_by_name($name,$this->options->get_pid());
				if($c)
					$this->_constants[$name] = $c;
				else
					$this->_missing['consts'][$name] = true;
			}
			if(!isset($this->_constants[$name]) && $this->options->get_use_phpref())
			{
				$c = PC_DAO::get_constants()->get_by_name($name,PC_Project::PHPREF_ID);
				if($c)
					$this->_constants[$name] = $c;
				else
					$this->_missing['consts'][$name] = true;
			}
		}
		if(isset($this->_constants[$name]))
			return $this->_constants[$name];
		return null;
	}
	
	/**
	 * @return array an associative array of <code>array(<name> => <const>)</code>
	 */
	public function get_constants()
	{
		return $this->_constants;
	}
	
	/**
	 * Adds the given call
	 * 
	 * @param PC_Obj_Call $call the call
	 */
	public function add_call($call)
	{
		$this->_calls[] = $call;
	}
	
	/**
	 * @return array the found function-calls
	 */
	public function get_calls()
	{
		return $this->_calls;
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
