<?php
/**
 * Contains the variable-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Is used to store all properties of variables
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Variable extends FWS_Object
{
	/**
	 * Represents the global scope
	 */
	const SCOPE_GLOBAL = '#global';
	
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
	 * @var PC_Obj_Type
	 */
	private $type;
	
	/**
	 * Constructor
	 * 
	 * @param string $name the name
	 * @param PC_Obj_Type $type the type
	 * @param string $function the function-name (scope)
	 * @param string $class the class-name (scope)
	 */
	public function __construct($name,$type,$function = '',$class = '')
	{
		parent::__construct();
		
		if(!($type instanceof PC_Obj_Type))
			FWS_Helper::def_error('instance','type','PC_Obj_Type',$type);
		
		$this->name = $name;
		$this->type = $type;
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
	 * @return PC_Obj_Type the type
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	/**
	 * Sets the type
	 * 
	 * @param PC_Obj_Type $type the new value
	 */
	public function set_type($type)
	{
		if($this->arrayref !== null)
			$this->arrayref->set_array_type($this->arrayoff,$type);
		$this->type = $type;
	}
	
	/**
	 * Returns a variable pointing to the array-element with given key. If it does not exist, it
	 * will be created as soon as the type is assigned.
	 *
	 * @param mixed $key the key (null = append)
	 * @return PC_Obj_Type the type of the element
	 */
	public function array_offset($key)
	{
		if($key === null)
			$key = $this->type->get_array_count();
		
		// fetch element or create it
		$el = $this->type->get_array_type($key);
		if($el === null)
			$el = new PC_Obj_Type(PC_Obj_Type::UNKNOWN);
		$var = new self('',$el);
		// connect the var to us
		$var->arrayref = $this->type;
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
?>