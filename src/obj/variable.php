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
	 * @return string the function-name (scope)
	 */
	public function get_function()
	{
		return $this->function;
	}
	
	/**
	 * @return string the class-name (scope)
	 */
	public function get_class()
	{
		return $this->class;
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