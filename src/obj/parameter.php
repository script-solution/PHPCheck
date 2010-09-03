<?php
/**
 * Contains the parameter-class
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Is used for function-/method-parameters
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Parameter extends FWS_Object
{
	/**
	 * The name of the variable
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Param optional?
	 *
	 * @var boolean
	 */
	private $optional = false;
	
	/**
	 * Wether its the first variable argument of the function
	 * 
	 * @var boolean
	 */
	private $first_var_arg = false;
	
	/**
	 * The possible types of the parameter
	 *
	 * @var PC_Obj_MultiType
	 */
	private $mtype;
	
	/**
	 * Constructor
	 * 
	 * @param string $name the name
	 */
	public function __construct($name = '')
	{
		parent::__construct();
		
		$this->name = $name;
		$this->mtype = new PC_Obj_MultiType();
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
	 * @return PC_Obj_MultiType all types that may be used for this parameter
	 */
	public function get_mtype()
	{
		return $this->mtype;
	}
	
	/**
	 * Sets the multi-type-instance for this parameter
	 *
	 * @param PC_Obj_MultiType $mtype the new value
	 */
	public function set_mtype($mtype)
	{
		if(!($mtype instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','mtype','PC_Obj_MultiType',$mtype);
		
		$this->mtype = $mtype;
	}
	
	/**
	 * @return boolean wether the parameter is optional
	 */
	public function is_optional()
	{
		return $this->optional;
	}
	
	/**
	 * Sets wether the parameter is optional
	 *
	 * @param boolean $opt the new value
	 */
	public function set_optional($opt)
	{
		$this->optional = (bool)$opt;
		if($this->optional)
			$this->first_var_arg = false;
	}
	
	/**
	 * @return boolean wether the parameter is the first variable argument
	 */
	public function is_first_vararg()
	{
		return $this->first_var_arg;
	}
	
	/**
	 * Sets wether the parameter is the first variable argument
	 *
	 * @param boolean $first the new value
	 */
	public function set_first_vararg($first)
	{
		$this->first_var_arg = (bool)$first;
		if($this->first_var_arg)
			$this->optional = false;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	public function __ToString()
	{
		return $this->mtype.($this->optional ? '?' : ($this->first_var_arg ? '*' : ''));
	}
}
?>