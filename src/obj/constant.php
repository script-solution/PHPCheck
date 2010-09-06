<?php
/**
 * Contains the constant-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Represents a constant
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Obj_Constant extends PC_Obj_Location
{
	/**
	 * The name of the constant
	 *
	 * @var string
	 */
	private $_name;
	
	/**
	 * The type (and maybe value)
	 *
	 * @var PC_Obj_MultiType
	 */
	private $_type;
	
	/**
	 * The class-id
	 * 
	 * @var int
	 */
	private $_class;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 * @param string $name the constant-name
	 * @param PC_Obj_MultiType $type the type
	 * @param int $classid the class-id if loaded from db
	 */
	public function __construct($file,$line,$name,$type = null,$classid = 0)
	{
		parent::__construct($file,$line);
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		if($type !== null && !($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->_name = $name;
		$this->_type = $type;
		$this->_class = $classid;
	}
	
	/**
	 * @return int the class-id (just present if loaded from db!)
	 */
	public function get_class()
	{
		return $this->_class;
	}
	
	/**
	 * @return string the constant-name
	 */
	public function get_name()
	{
		return $this->_name;
	}
	
	/**
	 * @return PC_Obj_MultiType the type (and maybe value) of the constant
	 */
	public function get_type()
	{
		return $this->_type;
	}
	
	/**
	 * Sets the type
	 * 
	 * @param PC_Obj_MultiType $type the new type
	 */
	public function set_type($type)
	{
		if($type !== null && !($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->_type = $type;
	}
	
	public function __toString()
	{
		return 'const '.$this->_name.'['.$this->_type.']';
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
?>