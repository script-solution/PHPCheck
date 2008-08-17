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
class PC_Call extends PC_Location
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
	 * @param PC_Type $type the type
	 */
	public function add_argument($type)
	{
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
		$this->arguments[] = $type;
	}
	
	/**
	 * Builds a string-representation of the call
	 * 
	 * @return string
	 */
	public function get_call()
	{
		$classname = $this->class && $this->class == PC_Class::UNKNOWN ? '<i>UNKNOWN</i>' : $this->class;
		$url = new FWS_URL();
		$url->set('module','class');
		$url->set('name',$classname);
		$str = $classname ? '<a href="'.$url->to_url().'">'.$classname.'</a>'.($this->static ? '::' : '->') : '';
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
		return $this->get_call().' in "'.$this->get_file().'", line '.$this->get_line();
	}
}
?>