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
final class PC_Obj_Error extends FWS_Object
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
	const E_T_INTERFACE_MISSING									= 24;
	const E_T_IF_IS_NO_IF												= 25;
	const E_T_DOC_WITHOUT_PARAM									= 26;
	const E_T_PARAM_WITHOUT_DOC									= 27;
	
	/**
	 * Determines the name of the given type
	 *
	 * @param int $type the type
	 * @return string the name
	 */
	public static function get_type_name($type)
	{
		$types = self::get_types();
		return isset($types[$type]) ? $types[$type] : 'Unknown';
	}
	
	/**
	 * An array of all types: <code>array(<type> => <name>,...)</code>
	 *
	 * @return array all types
	 */
	public static function get_types()
	{
		return array(
			self::E_S_METHOD_MISSING =>									'Method missing',
			self::E_S_ABSTRACT_CLASS_INSTANTIATION =>		'Abstract class instantiation',
			self::E_S_STATIC_CALL => 										'Static call',
			self::E_S_NONSTATIC_CALL =>									'Nonstatic call',
			self::E_S_CLASS_MISSING =>									'Class missing',
			self::E_S_CLASS_UNKNOWN =>									'Class unknown',
			self::E_S_FUNCTION_MISSING =>								'Function missing',
			self::E_S_WRONG_ARGUMENT_COUNT =>						'Wrong arg count',
			self::E_S_WRONG_ARGUMENT_TYPE =>						'Wrong arg type',
			self::E_T_CLASS_POT_USELESS_ABSTRACT =>			'Abstract class',
			self::E_T_FINAL_CLASS_INHERITANCE =>				'Final class inheritance',
			self::E_T_CLASS_NOT_ABSTRACT =>							'Class not abstract',
			self::E_T_CLASS_MISSING =>									'Class missing',
			self::E_T_INTERFACE_MISSING =>							'Interface missing',
			self::E_T_IF_IS_NO_IF =>										'Implemented class',
			self::E_T_DOC_WITHOUT_PARAM =>							'Doc without param',
			self::E_T_PARAM_WITHOUT_DOC =>							'Param without doc',
		);
	}
	
	/**
	 * The error-id
	 * 
	 * @var int
	 */
	private $id = 0;
	
	/**
	 * The error-location
	 *
	 * @var PC_Obj_Location
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
	 * @param PC_Obj_Location $loc the location of the error
	 * @param string $msg the message to display
	 * @param int $type the error-type. See self::E_*
	 */
	public function __construct($loc,$msg,$type)
	{
		parent::__construct();
		
		if(!($loc instanceof PC_Obj_Location))
			FWS_Helper::def_error('instance','loc','PC_Obj_Location',$loc);
		if(!FWS_Helper::is_integer($type) || $type < 0)
			FWS_Helper::def_error('intge0','type',$type);
		
		$this->loc = $loc;
		$this->msg = $msg;
		$this->type = $type;
	}
	
	/**
	 * @return int the id
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * Sets the id
	 * 
	 * @param int $id the new value
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return PC_Obj_Location the error-location
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