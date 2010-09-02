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

/**
 * This class is used to store the type of a variable, class-field or method-return-type
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Obj_Type extends FWS_Object
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
	
	/**
	 * Determines the type-instance by the given type-name
	 *
	 * @param string $name the type-name
	 * @return PC_Obj_Type the type-instance
	 */
	public static function get_type_by_name($name)
	{
		switch(FWS_String::strtolower($name))
		{
			case 'number':	// TODO keep that here?
			case 'integer':
			case 'int':
			case 'long':
			case 'short':
			case 'byte':
				return new self(self::INT);
			
			case 'bool':
			case 'boolean':
				return new self(self::BOOL);
			
			case 'float':
			case 'double':
				return new self(self::FLOAT);
			
			case 'string':
			case 'str':
			case 'char':
				return new self(self::STRING);
			
			case 'array':
				return new self(self::TARRAY);
			
			case 'resource':
			case 'res':
				return new self(self::RESOURCE);
			
			case 'object':
				return new self(self::UNKNOWN);
			
			default:
				// TODO check if we know the class?
				return new self(self::OBJECT,null,$name);
		}
	}
	
	/**
	 * Builds the corresponding type from the given value. This way, it can also handle
	 * arrays-elements.
	 * 
	 * @param mixed $value the value
	 * @return PC_Obj_Type the type
	 */
	public static function get_type_by_value($value)
	{
		if(is_array($value))
		{
			$array = new self(self::TARRAY);
			foreach($value as $k => $v)
				$array->set_array_type($k,self::get_type_by_value($v));
			return $array;
		}
		$type = self::get_type_by_name(gettype($value));
		$type->set_value($value);
		return $type;
	}
	
	/**
	 * The type
	 *
	 * @var int
	 */
	private $_type;
	
	/**
	 * The value (if known)
	 *
	 * @var mixed
	 */
	private $_value;
	
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
	 * @param mixed $value if known the value
	 * @param string $class the class-name (for self::OBJECT)
	 */
	public function __construct($type,$value = null,$class = '')
	{
		parent::__construct();
		
		if(!FWS_Helper::is_integer($type))
			FWS_Helper::def_error('int','type',$type);
		
		$this->_type = $type;
		$this->_value = $value;
		$this->_class = $class;
	}
	
	/**
	 * Clones an object of this class. Ensures that references will be cloned, too.
	 */
	public function __clone()
	{
		parent::__clone();
		
		// clone array-types
		if(is_array($this->_value))
		{
			// note that this does handle multi-dimensional arrays!
			foreach($this->_value as $k => $v)
				$this->_value[$k] = clone $v;
		}
	}
	
	/**
	 * Checks wether this type is equal to the given one
	 *
	 * @param object $o the object to compare with
	 * @return boolean true if they are equal
	 */
	public function equals($o)
	{
		if(!($o instanceof PC_Obj_Type))
			return false;
		
		if($o->get_type() != $this->get_type())
			return false;
		
		return $o->get_class() == $o->get_class();
	}
	
	/**
	 * @return int the number of elements in the array
	 */
	public function get_array_count()
	{
		if($this->_type == self::TARRAY && $this->_value !== null)
			return count($this->_value);
		return 0;
	}
	
	/**
	 * Returns the type of the array-element with given key
	 *
	 * @param mixed $key the key
	 * @return PC_Obj_Type the type of the element or null if it does not exist
	 */
	public function get_array_type($key)
	{
		if($this->_type == self::TARRAY && isset($this->_value[$key]))
			return $this->_value[$key];
		return null;
	}
	
	/**
	 * Sets the array-element-type for the given key to given type
	 *
	 * @param mixed $key the key
	 * @param PC_Obj_Type $type the element-type
	 */
	public function set_array_type($key,$type)
	{
		if($type !== null && !($type instanceof PC_Obj_Type))
			FWS_Helper::def_error('instance','type','PC_Obj_Type',$type);
		
		// convert implicitly to an array
		$this->_type = self::TARRAY;
		if(!is_array($this->_value))
			$this->_value = array();
		if($key instanceof PC_Obj_Type)
			$key = $key->get_value_for_use();
		$this->_value[$key] = $type === null ? new PC_Obj_Type(PC_Obj_Type::UNKNOWN) : $type;
	}
	
	/**
	 * @return boolean wether this type is a scalar type
	 */
	public function is_scalar()
	{
		return in_array($this->_type,array(self::BOOL,self::FLOAT,self::INT,self::STRING));
	}
	
	/**
	 * @return boolean true if the type is unknown
	 */
	public function is_unknown()
	{
		return $this->_type == self::UNKNOWN;
	}
	
	/**
	 * For arrays: Check if any element is unknown
	 * 
	 * @return boolean true if any element is unknown
	 */
	public function is_array_unknown()
	{
		if(is_array($this->_value))
		{
			foreach($this->_value as $v)
			{
				if($v->is_unknown() || $v->is_array_unknown())
					return true;
			}
		}
		return false;
	}
	
	/**
	 * @return int the type
	 */
	public function get_type()
	{
		return $this->_type;
	}
	
	/**
	 * Sets the type
	 *
	 * @param int $type the new value
	 */
	public function set_type($type)
	{
		if(!FWS_Helper::is_integer($type))
			FWS_Helper::def_error('int','type',$type);
		
		$this->_type = $type;
	}
	
	/**
	 * @return mixed value (null may mean unknown or the value is null)
	 */
	public function get_value()
	{
		return $this->_value;
	}
	
	/**
	 * @return mixed the value that should be used for arithmethic (+,-,...)
	 */
	public function get_value_as_number()
	{
		if($this->_value === null || !in_array($this->_type,array(self::BOOL,self::FLOAT,self::INT)))
			return 0;
		return $this->_value;
	}
	
	/**
	 * @return string the value as string
	 */
	public function get_value_as_str()
	{
		if($this->_value === null || $this->_type == self::UNKNOWN)
			return '\'\'';
		if($this->_type == self::STRING)
			return (string)$this->_value;
		return '\''.(string)$this->_value.'\'';
	}
	
	/**
	 * @return mixed a value that can be used (will take care of unknown values)
	 */
	public function get_value_for_use()
	{
		if($this->_value === null || $this->_type == self::UNKNOWN)
			return 0;
		switch($this->_type)
		{
			case self::STRING:
				return (string)$this->_value;
			case self::INT:
				return (int)$this->_value;
			case self::FLOAT:
				return (float)$this->_value;
			case self::BOOL:
				return (bool)$this->_value;
			default:
				return $this->_value;
		}
	}
	
	/**
	 * @return string a value that can be used for eval
	 */
	public function get_value_for_eval()
	{
		if($this->_value === null || $this->_type == self::UNKNOWN)
			return '0';
		if($this->_type == self::BOOL)
			return $this->_value ? 'true' : 'false';
		if($this->_type == self::STRING)
			return '\''.str_replace(array('\\','\''),array('\\\\','\\\''),$this->_value).'\'';
		if($this->_type == self::TARRAY)
			return $this->array_to_str($this);
		return $this->_value;
	}
	
	/**
	 * Converts the given array to string so that PHP can eval it
	 * 
	 * @param PC_Obj_Type $val the value
	 * @return string the string
	 */
	private function array_to_str($val)
	{
		if($val->_type == self::TARRAY)
		{
			if(is_array($val->_value))
			{
				$str = 'array(';
				foreach($val->_value as $k => $v)
				{
					if(is_string($k))
						$str .= '\''.$k.'\'';
					else
						$str .= (string)$k;
					$str .= ' => '.$this->array_to_str($v).',';
				}
				return substr($str,0,-1).')';
			}
			return '';
		}
		return $val->get_value_for_eval();
	}
	
	/**
	 * Sets the value
	 *
	 * @param mixed $value the new value
	 */
	public function set_value($value)
	{
		$this->_value = $value;
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
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	/**
	 * The string-representation of the type
	 *
	 * @return string the string
	 */
	public function __toString()
	{
		if($this->_type == self::OBJECT)
			return (string)$this->_class;
		if($this->_type == self::TARRAY)
		{
			$str = 'array';
			if($this->_value !== null)
				$str .= '='.FWS_Printer::to_string($this->_value,PHP_SAPI != 'cli',false);
			return $str;
		}
		
		$str = $this->_get_type_name($this->_type);
		if($this->_value !== null)
			$str .= '='.FWS_Printer::to_string($this->_value,PHP_SAPI != 'cli',false);
		return $str;
	}
}
?>