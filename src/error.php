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
	const E_S_METHOD_MISSING										= 0;
	const E_S_ABSTRACT_CLASS_INSTANTIATION			= 1;
	const E_S_STATIC_CALL												= 2;
	const E_S_NONSTATIC_CALL										= 3;
	const E_S_CLASS_MISSING											= 4;
	const E_S_CLASS_UNKNOWN											= 5;
	const E_S_FUNCTION_MISSING									= 6;
	const E_S_WRONG_ARGUMENT_COUNT							= 7;
	const E_S_WRONG_ARGUMENT_TYPE								= 8;
	const E_T_CLASS_POT_USELESS_ABSTRACT				= 20;
	const E_T_FINAL_CLASS_INHERITANCE						= 21;
	const E_T_CLASS_NOT_ABSTRACT								= 22;
	const E_T_CLASS_MISSING											= 23;
	
	/**
	 * Determines the name of the given type
	 *
	 * @param int $type the type
	 * @return string the name
	 */
	public static function get_type_name($type)
	{
		switch($type)
		{
			case self::E_S_METHOD_MISSING:
				return 'Method missing';
			case self::E_S_ABSTRACT_CLASS_INSTANTIATION:
				return 'Abstract class instantiation';
			case self::E_S_STATIC_CALL:
				return 'Static call';
			case self::E_S_NONSTATIC_CALL:
				return 'Nonstatic call';
			case self::E_S_CLASS_MISSING:
				return 'Class missing';
			case self::E_S_CLASS_UNKNOWN:
				return 'Class unknown';
			case self::E_S_FUNCTION_MISSING:
				return 'Function missing';
			case self::E_S_WRONG_ARGUMENT_COUNT:
				return 'Wrong arg count';
			case self::E_S_WRONG_ARGUMENT_TYPE:
				return 'Wrong arg type';
			case self::E_T_CLASS_POT_USELESS_ABSTRACT:
				return 'Abstract class';
			case self::E_T_FINAL_CLASS_INHERITANCE:
				return 'Final class inheritance';
			case self::E_T_CLASS_NOT_ABSTRACT:
				return 'Class not abstract';
			case self::E_T_CLASS_MISSING:
				return 'Class missing';
			
			default:
				return 'Unknown';
		}
	}
	
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