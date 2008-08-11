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
 * Is used to store all properties of class-fields and method-/function-parameters
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Variable extends FWS_Object implements PC_Visible
{
	/**
	 * The name of the variable
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The type of the variable
	 *
	 * @var PC_Type
	 */
	private $type;
	
	/**
	 * The visibility
	 *
	 * @var string
	 */
	private $visibility = self::V_PUBLIC;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->type = PC_Type::$UNKNOWN;
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
	 * @return PC_Type the type of the variable
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	/**
	 * Sets the type of the variable
	 *
	 * @param PC_Type $type the new value
	 */
	public function set_type($type)
	{
		$this->type = $type;
	}
	
	/**
	 * @see PC_Visible::get_visibility()
	 * 
	 * @return string
	 */
	public function get_visibility()
	{
		return $this->visibility;
	}
	
	/**
	 * @see PC_Visible::set_visibity()
	 *
	 * @param string $visibility
	 */
	public function set_visibity($visibility)
	{
		$valid = array(self::V_PUBLIC,self::V_PROTECTED,self::V_PRIVATE);
		if(!in_array($visibility,$valid))
			FWS_Helper::def_error('inarray','visibility',$valid,$visibility);
		
		$this->visibility = $visibility;
	}
	
	protected function get_print_vars()
	{
		return get_object_vars($this);
	}
}
?>