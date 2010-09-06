<?php
/**
 * Contains the type-container-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * A container for types. Ensures that every type will be loaded just once
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Compile_TypeContainer extends FWS_Object
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
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>