<?php
/**
 * Contains the field-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Is used for class-fields
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Field extends PC_Variable implements PC_Visible
{
	/**
	 * The visibility
	 *
	 * @var string
	 */
	private $visibility = self::V_PUBLIC;
	
	/**
	 * The type of the variable
	 *
	 * @var PC_Type
	 */
	private $type;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->type = PC_Type::$UNKNOWN;
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
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
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
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		return $this->get_name().'['.$this->type.']';
	}
}
?>