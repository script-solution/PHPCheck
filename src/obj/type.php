<?php
/**
 * Contains the type-class
 * 
 * @package			PHPCheck
 * @subpackage	src.obj
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * This class is used to store the type of a variable, class-field or method-return-type
 *
 * @package			PHPCheck
 * @subpackage	src.obj
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
	const TCALLABLE	= 7;
	const VOID			= 8;
	
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
			
			case 'void':
				return new self(self::VOID);

			case 'mixed':
			case 'NULL':					// return from gettype()
			case 'unknown type':	// return from gettype()
			case 'unknown':
				return null;
			
			case 'callable':
			case 'function':
			case 'func':
				return new self(self::TCALLABLE);
			
			case 'object':
				return new self(self::OBJECT,null,'');
			
			default:
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
			$array = new self(self::TARRAY,array());
			foreach($value as $k => $v)
				$array->set_array_type($k,new PC_Obj_MultiType(self::get_type_by_value($v)));
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
		$this->set_value($value);
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
		
		return $o->get_class() == $this->get_class();
	}
	
	/**
	 * @return bool true if the value is known
	 */
	public function is_val_unknown()
	{
		return $this->_value === null;
	}
	
	/**
	 * For arrays: Check if any element is unknown
	 * 
	 * @return boolean true if any element is unknown
	 */
	public function is_array_unknown()
	{
		if($this->_type == self::TARRAY)
		{
			if(!is_array($this->_value))
				return true;
			foreach($this->_value as $v)
			{
				if($v->is_array_unknown())
					return true;
			}
			return false;
		}
		return $this->is_val_unknown();
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
	 * Determines the next array-key to use
	 * 
	 * @return PC_Obj_MultiType the key
	 */
	public function get_next_array_key()
	{
		if($this->_type != self::TARRAY || $this->_value === null)
			return PC_Obj_MultiType::create_int(0);
		$max = -1;
		foreach(array_keys($this->_value) as $k)
		{
			if(FWS_Helper::is_integer($k) && $k > $max)
				$max = $k;
		}
		return PC_Obj_MultiType::create_int($max + 1);
	}
	
	/**
	 * Returns the type of the array-element with given key
	 *
	 * @param mixed $key the key
	 * @return PC_Obj_MultiType the type of the element or null if it does not exist
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
	 * @param PC_Obj_MultiType $type the element-type
	 * @param bool $append whether to append the value
	 */
	public function set_array_type($key,$type,$append = false)
	{
		if($key === null)
		{
			$this->_value = null;
			return;
		}
		if($type !== null && !($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		if(!($key instanceof PC_Obj_MultiType) && !is_scalar($key))
			FWS_Helper::error('$key has to be a scalar or PC_Obj_MultiType (got '.gettype($key).')');
		
		// if it is already an array, but unknown, leave it unknown
		if($this->_type == self::TARRAY && $this->_value === null)
			return;
		
		// convert implicitly to an array
		$this->_type = self::TARRAY;
		if(!is_array($this->_value))
			$this->_value = array();
		if($key instanceof PC_Obj_MultiType)
		{
			// if we don't know what to set, we don't know the content of the whole array anymore
			if($key->is_unknown() || $key->is_val_unknown() || $key->is_multiple())
			{
				$this->_value = null;
				return;
			}
			
			$key = $key->get_first()->get_value_for_use();
		}
		
		$val = $type === null ? new PC_Obj_MultiType() : $type;
		if($append)
			$this->_value[] = $val;
		else
			$this->_value[$key] = $val;
	}
	
	/**
	 * @return boolean wether this type is a scalar type
	 */
	public function is_scalar()
	{
		return in_array($this->_type,array(self::BOOL,self::FLOAT,self::INT,self::STRING));
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
	 * @return string the value as string
	 */
	public function get_value_as_str()
	{
		// TODO remove!
		if($this->_value === null)
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
		if($this->_value === null)
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
		if($this->_value === null)
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
					$types = $v->get_types();
					assert(count($types) == 1);
					$str .= ' => '.$this->array_to_str($types[0]).',';
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
		if($this->_value !== null)
		{
			switch($this->_type)
			{
				case self::BOOL:
					$this->_value = (bool)$this->_value;
					break;
				case self::INT:
					if(is_string($this->_value))
					{
						if(strcasecmp(substr($this->_value,0,2),'0x') == 0)
							$this->_value = hexdec(substr($this->_value,2));
						else if(substr($this->_value,0,1) == '0')
							$this->_value = octdec(substr($this->_value,1));
						else
							$this->_value = (int)$this->_value;
					}
					else
						$this->_value = (int)$this->_value;
					break;
				case self::FLOAT:
					$this->_value = (float)$this->_value;
					break;
				case self::STRING:
					$this->_value = (string)$this->_value;
					break;
				case self::TARRAY:
					$this->_value = (array)$this->_value;
					break;
			}
		}
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
	private function get_type_name($type)
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
			case self::OBJECT:
				return 'object';
			case self::TCALLABLE:
				return 'callable';
			case self::VOID:
				return 'void';
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
		if($this->_type == self::OBJECT && $this->_class)
			return (string)$this->_class;
		if($this->_type == self::TARRAY)
		{
			$str = 'array';
			if($this->_value !== null)
				$str .= '='.FWS_Printer::to_string($this->_value,!PC_UNITTESTS && PHP_SAPI != 'cli',false);
			return $str;
		}
		
		$str = $this->get_type_name($this->_type);
		if($this->_value !== null)
			$str .= '='.FWS_Printer::to_string($this->_value,!PC_UNITTESTS && PHP_SAPI != 'cli',false);
		return $str;
	}
}
