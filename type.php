<?php
/**
 * Contains the type-class
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

// init static fields
PC_Type::$UNKNOWN = new PC_Type(PC_Type::UNKNOWN);
PC_Type::$INT = new PC_Type(PC_Type::INT);
PC_Type::$BOOL = new PC_Type(PC_Type::BOOL);
PC_Type::$FLOAT = new PC_Type(PC_Type::FLOAT);
PC_Type::$STRING = new PC_Type(PC_Type::STRING);
PC_Type::$TARRAY = new PC_Type(PC_Type::TARRAY);
PC_Type::$RESOURCE = new PC_Type(PC_Type::RESOURCE);

/**
 * This class is used to store the type of a variable, class-field or method-return-type
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Type extends FWS_Object
{
	// the different types
	const INT				= 0;
	const BOOL			= 1;
	const FLOAT			= 2;
	const STRING		= 3;
	const TARRAY		= 4;
	const OBJECT		= 5;
	const RESOURCE	= 6;
	const UNKNOWN		= 7;
	
	// some static ones that are always the same
	public static $INT;
	public static $BOOL;
	public static $FLOAT;
	public static $STRING;
	public static $TARRAY;
	public static $RESOURCE;
	public static $UNKNOWN;
	
	/**
	 * Determines the type-instance by the given type-name
	 *
	 * @param string $name the type-name
	 * @return PC_Type the type-instance
	 */
	public static function get_type_by_name($name)
	{
		switch($name)
		{
			case 'integer':
			case 'int':
			case 'long':
			case 'short':
			case 'byte':
				return self::$INT;
			
			case 'bool':
			case 'boolean':
				return self::$BOOL;
			
			case 'float':
			case 'double':
				return self::$FLOAT;
			
			case 'string':
			case 'str':
				return self::$STRING;
			
			case 'array':
				return self::$TARRAY;
			
			case 'resource':
			case 'res':
				return self::$RESOURCE;
			
			default:
				// TODO check if we know the class?
				return new PC_Type(self::OBJECT,$name);
		}
	}
	
	/**
	 * The type
	 *
	 * @var int
	 */
	private $_type;
	
	/**
	 * The class-name (for self::OBJECT)
	 *
	 * @var string
	 */
	private $_class;
	
	/**
	 * Constructor
	 *
	 * @param int $type the type
	 * @param string $class the class-name (for self::OBJECT)
	 */
	public function __construct($type,$class = '')
	{
		parent::__construct();
		
		$this->_type = $type;
		$this->_class = $class;
	}
	
	/**
	 * Checks wether this type is equal to the given one
	 *
	 * @param object $o the object to compare with
	 * @return boolean true if they are equal
	 */
	public function equals($o)
	{
		if(!($o instanceof PC_Type))
			return false;
		
		if($o->get_type() != $this->get_type())
			return false;
		
		return $o->get_class() == $o->get_class();
	}
	
	/**
	 * @return int the type
	 */
	public function get_type()
	{
		return $this->_type;
	}
	
	/**
	 * @return string the class-name (empty for other than self::OBJECT)
	 */
	public function get_class()
	{
		return $this->_class;
	}
	
	/**
	 * Returns the name of the constant
	 *
	 * @param int $type the type
	 * @return string the name
	 */
	private function _get_type_name($type)
	{
		switch($type)
		{
			case self::INT:
				return 'integer';
			case self::FLOAT:
				return 'float';
			case self::BOOL:
				return 'bool';
			case self::STRING:
				return 'string';
			case self::TARRAY:
				return 'array';
			case self::RESOURCE:
				return 'resource';
			default:
				return 'unknown';
		}
	}
	
	/**
	 * @see FWS_Object::get_print_vars()
	 *
	 * @return array
	 */
	protected function get_print_vars()
	{
		return get_object_vars($this);
	}
	
	/**
	 * The string-representation of the type
	 *
	 * @param boolean $use_html dummy param
	 * @return string the string
	 */
	public function __toString($use_html = false)
	{
		return $this->_type == self::OBJECT ? $this->_class : $this->_get_type_name($this->_type);
	}
}
?>