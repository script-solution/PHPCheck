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
	 * The project-id
	 *
	 * @var int
	 */
	private $_pid;
	
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
	 * All currently known constants
	 *
	 * @var array
	 */
	private $_constants = array();
	/**
	 * The found errors
	 * 
	 * @var array
	 */
	private $_errors = array();
	/**
	 * The potential errors, processed later in the finalizer
	 * 
	 * @var array
	 */
	private $_poterrors = array();
	/**
	 * The calls
	 * 
	 * @var array
	 */
	private $_calls = array();
	
	/**
	 * Wether the db should be queried if a type can't be found
	 *
	 * @var bool
	 */
	private $_use_db;
	/**
	 * Whether to query also the phpref-entries in the db
	 *
	 * @var bool
	 */
	private $_use_phpref;
	
	/**
	 * Constructor
	 *
	 * @param int $pid the project-id
	 * @param bool $use_db wether the db should be queried if a type can't be found
	 * @param bool $use_phpref whether to query also the phpref-entries in the db
	 * 	(ignored if $use_db is false)
	 */
	public function __construct($pid = PC_Project::CURRENT_ID,$use_db = true,$use_phpref = true)
	{
		parent::__construct();
		$this->_pid = PC_Utils::get_project_id($pid);
		$this->_use_db = $use_db;
		$this->_use_phpref = $use_db && $use_phpref;
	}
	
	/**
	 * @return bool wether the db is used
	 */
	public function is_db_used()
	{
		return $this->_use_db;
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
		$this->add_errors($typecon->get_errors());
		$this->add_pot_errors($typecon->get_pot_errors());
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
		if(!isset($this->_classes[$name]) && $this->_use_db)
			$this->_classes[$name] = PC_DAO::get_classes()->get_by_name($name,$this->_pid);
		if(!isset($this->_classes[$name]) && $this->_use_phpref)
			$this->_classes[$name] = PC_DAO::get_classes()->get_by_name($name,PC_Project::PHPREF_ID);
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
		if(!isset($this->_functions[$name]) && $this->_use_db)
			$this->_functions[$name] = PC_DAO::get_functions()->get_by_name($name,$this->_pid);
		if(!isset($this->_functions[$name]) && $this->_use_phpref)
			$this->_functions[$name] = PC_DAO::get_functions()->get_by_name($name,PC_Project::PHPREF_ID);
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
		if(!isset($this->_constants[$name]) && $this->_use_db)
			$this->_constants[$name] = PC_DAO::get_constants()->get_by_name($name,$this->_pid);
		if(!isset($this->_constants[$name]) && $this->_use_phpref)
			$this->_constants[$name] = PC_DAO::get_constants()->get_by_name($name,PC_Project::PHPREF_ID);
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
	 * Adds the given errors
	 * 
	 * @param array $errors the errors to add
	 */
	public function add_errors($errors)
	{
		$this->_errors = array_merge($this->_errors,$errors);
	}
	
	/**
	 * @return array the found errors
	 */
	public function get_errors()
	{
		return $this->_errors;
	}
	
	/**
	 * Adds the given potential errors
	 * 
	 * @param array $errors the errors to add
	 */
	public function add_pot_errors($errors)
	{
		$this->_poterrors = array_merge($this->_poterrors,$errors);
	}
	
	/**
	 * @return array the found potential errors
	 */
	public function get_pot_errors()
	{
		return $this->_poterrors;
	}
	
	/**
	 * Removes the pot-error with given index
	 * 
	 * @param int $index the index
	 */
	public function remove_pot_error($index)
	{
		unset($this->_poterrors[$index]);
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
