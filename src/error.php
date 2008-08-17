<?php
/**
 * Contains the error-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Stores all properties of an error that has been found
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Error extends FWS_Object
{
	const E_METHOD_MISSING										= 0;
	const E_ABSTRACT_CLASS_INSTANTIATION			= 1;
	const E_STATIC_CALL												= 2;
	const E_NONSTATIC_CALL										= 3;
	const E_CLASS_MISSING											= 4;
	const E_CLASS_UNKNOWN											= 5;
	const E_FUNCTION_MISSING									= 6;
	const E_CLASS_POT_USELESS_ABSTRACT				= 7;
	const E_FINAL_CLASS_INHERITANCE						= 8;
	const E_WRONG_ARGUMENT_COUNT							= 9;
	const E_WRONG_ARGUMENT_TYPE								= 10;
	const E_CLASS_NOT_ABSTRACT								= 11;
	
	/**
	 * The error-location
	 *
	 * @var PC_Location
	 */
	private $loc;
	
	/**
	 * The error-type
	 * 
	 * @var int
	 */
	private $type;
	
	/**
	 * The error-message
	 * 
	 * @var string
	 */
	private $msg;
	
	/**
	 * Constructor
	 *
	 * @param PC_Location $loc the location of the error
	 * @param string $msg the message to display
	 * @param int $type the error-type. See self::E_*
	 */
	public function __construct($loc,$msg,$type)
	{
		parent::__construct();
		
		if(!($loc instanceof PC_Location))
			FWS_Helper::def_error('instance','loc','PC_Location',$loc);
		if(!FWS_Helper::is_integer($type) || $type < 0)
			FWS_Helper::def_error('intge0','type',$type);
		
		$this->loc = $loc;
		$this->msg = $msg;
		$this->type = $type;
	}
	
	/**
	 * @return PC_Location the error-location
	 */
	public function get_loc()
	{
		return $this->loc;
	}
	
	/**
	 * @return string the error-message
	 */
	public function get_msg()
	{
		return $this->msg;
	}
	
	/**
	 * @return int the error-type
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	public function __ToString()
	{
		return '['.$this->loc->get_file().', '.$this->loc->get_line().'] #'.$this->type.' '.$this->msg;
	}
}
?>