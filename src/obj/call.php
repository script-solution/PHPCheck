<?php
/**
 * Contains the call-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Is used to store information about a function-/method-call
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Call extends PC_Obj_Location
{
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
	 * Indicates wether this function-call is an object-creation (new)
	 *
	 * @var boolean
	 */
	private $objcreation = false;
	
	/**
	 * Wether the method is static
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
	 * @return boolean wether the call is static
	 */
	public function is_static()
	{
		return $this->static;
	}
	
	/**
	 * Sets wether the call is static
	 *
	 * @param boolean $static the new value
	 */
	public function set_static($static)
	{
		$this->static = (bool)$static;
	}
	
	/**
	 * @return boolean wether the call is an object-creation
	 */
	public function is_object_creation()
	{
		return $this->objcreation;
	}
	
	/**
	 * Sets wether the call is an object-creation
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
	 * @param PC_Obj_Type $type the type
	 */
	public function add_argument($type)
	{
		if(!($type instanceof PC_Obj_Type))
			FWS_Helper::def_error('instance','type','PC_Obj_Type',$type);
		
		$this->arguments[] = clone $type;
	}
	
	/**
	 * Builds a string-representation of the call
	 * 
	 * @param bool $use_db wether to query the db for more info
	 * @return string
	 */
	public function get_call($use_db = true)
	{
		$classname = $this->class && $this->class == PC_Obj_Class::UNKNOWN ? '<i>UNKNOWN</i>' : $this->class;
		$url = PC_URL::get_mod_url('class');
		$url->set('name',$classname);
		$str = '';
		if($classname)
			$str .= '<a href="'.$url->to_url().'">'.$classname.'</a>'.($this->static ? '::' : '->');
		if($use_db)
			$func = PC_DAO::get_functions()->get_by_name($this->function,0,$this->class);
		else
			$func = null;
		if($func)
		{
			$url = PC_URL::get_mod_url('class');
			$url->set('name',$classname);
			$url->set_anchor('l'.$func->get_line());
			$str .= '<a href="'.$url->to_url().'">'.$this->function.'</a>(';
		}
		else
			$str .= $this->function.'(';
		$str .= implode(', ',$this->arguments);
		$str .= ')';
		return $str;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		return $this->get_call(false).' in "'.$this->get_file().'", line '.$this->get_line();
	}
}
?>