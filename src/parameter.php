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
class PC_Parameter extends PC_Variable
{
	/**
	 * Param optional?
	 *
	 * @var boolean
	 */
	private $optional = false;
	
	/**
	 * The possible types of the parameter
	 *
	 * @var PC_MultiType
	 */
	private $mtype;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->mtype = new PC_MultiType();
	}
	
	/**
	 * @return PC_MultiType all types that may be used for this parameter
	 */
	public function get_mtype()
	{
		return $this->mtype;
	}
	
	/**
	 * Sets the multi-type-instance for this parameter
	 *
	 * @param PC_MultiType $mtype the new value
	 */
	public function set_mtype($mtype)
	{
		if(!($mtype instanceof PC_MultiType))
			FWS_Helper::def_error('instance','mtype','PC_MultiType',$mtype);
		
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
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		return $this->mtype.($this->optional ? '?' : '');
	}
}
?>