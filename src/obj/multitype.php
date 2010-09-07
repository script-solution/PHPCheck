<?php
/**
 * Contains the multi-type-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * This class is used to represent multiple types which may be specified in phpdoc for parameters
 * or return-values.
 * 
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_MultiType extends FWS_Object
{
	/**
	 * Creates a multitype with given type and no value
	 * 
	 * @param int $type the type
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_type($type)
	{
		return new self(array(new PC_Obj_Type($type)));
	}
	
	/**
	 * Creates a multitype with type OBJECT and given class-name
	 * 
	 * @param string $classname the class-name
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_object($classname)
	{
		return new self(array(new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$classname)));
	}
	
	/**
	 * Creates a multitype with type OBJECT and given class-name
	 * 
	 * @param string $value the value
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_string($value = null)
	{
		return new self(array(new PC_Obj_Type(PC_Obj_Type::STRING,$value)));
	}
	
	/**
	 * Creates a multitype with type TARRAY and given value
	 * 
	 * @param array $value the value
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_array($value = null)
	{
		$val = $value !== null ? PC_Obj_Type::get_type_by_value($value) : null;
		return new self(array(new PC_Obj_Type(PC_Obj_Type::TARRAY,$val)));
	}
	
	/**
	 * Creates a multitype with type INT and given value
	 * 
	 * @param int $value the value
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_int($value = null)
	{
		return new self(array(new PC_Obj_Type(PC_Obj_Type::INT,$value)));
	}
	
	/**
	 * Creates a multitype with type FLOAT and given value
	 * 
	 * @param float $value the value
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_float($value = null)
	{
		return new self(array(new PC_Obj_Type(PC_Obj_Type::FLOAT,$value)));
	}
	
	/**
	 * Creates a multitype with type BOOL and given value
	 * 
	 * @param bool $value the value
	 * @return PC_Obj_MultiType the multitype
	 */
	public static function create_bool($value = null)
	{
		return new self(array(new PC_Obj_Type(PC_Obj_Type::BOOL,$value)));
	}
	
	/**
	 * Builds the MultiType-instance from the given name. '|' will be assumed as separator
	 * of the types.
	 *
	 * @param string $name the name
	 * @return PC_Obj_MultiType the instance
	 */
	public static function get_type_by_name($name)
	{
		$types = explode('|',$name);
		$ts = array();
		foreach($types as $type)
		{
			$type = trim($type);
			$typeobj = PC_Obj_Type::get_type_by_name($type);
			if($typeobj !== null)
				$ts[] = $typeobj;
		}
		return new PC_Obj_MultiType($ts);
	}
	
	/**
	 * An array of possible types
	 *
	 * @var array
	 */
	private $types = array();
	
	/**
	 * Constructor
	 *
	 * @param PC_Obj_Type|array $types the types to set
	 */
	public function __construct($types = array())
	{
		parent::__construct();
		
		if($types instanceof PC_Obj_Type)
			$this->types = array($types);
		else if(is_array($types))
			$this->types = $types;
	}
	
	public function __clone()
	{
		parent::__clone();
		
		foreach($this->types as $k => $t)
			$this->types[$k] = clone $t;
	}
	
	/**
	 * Checks wether all types in the given multitype are equal to those in $this
	 * 
	 * @param PC_Obj_MultiType $mtype the multitype
	 * @return bool true if equal
	 */
	public function equals($mtype)
	{
		if(!($mtype instanceof PC_Obj_MultiType))
			return false;
		
		$tcount = count($this->types);
		if($tcount != count($mtype->types))
			return false;
		$ttypes = array();
		$mtypes = array();
		for($i = 0; $i < $tcount; $i++)
		{
			$ttypes[$this->types[$i]->get_type()] = true;
			$mtypes[$mtype->types[$i]->get_type()] = true;
		}
		return count(array_diff_key($ttypes,$mtypes)) == 0;
	}
	
	/**
	 * Merges all types in the given multitype into this one. Clones the types in it.
	 * 
	 * @param PC_Obj_MultiType $mtype the type to merge with
	 * @param bool $set_unknown whether to set the types to unknown when $this or $mtype is unknown
	 */
	public function merge($mtype,$set_unknown = true)
	{
		// if one is unknown, the merged type is unknown as well
		if($set_unknown && ($this->is_unknown() || $mtype->is_unknown()))
		{
			$this->types = array();
			return;
		}
		if(!isset($mtype->types))
			return;
		foreach($mtype->types as $type)
		{
			$found = false;
			foreach($this->types as $ttype)
			{
				if($ttype->equals($type))
				{
					$found = true;
					break;
				}
			}
			// if we already have this type, check if the values are different
			if($found)
			{
				// if so simply unset the value, because we don't know it anymore
				if($ttype->get_value() !== $type->get_value())
					$ttype->set_value(null);
			}
			// otherwise add the type
			else
				$this->types[] = clone $type;
		}
	}
	
	/**
	 * Clears the value in all types
	 */
	public function clear_values()
	{
		foreach($this->types as $t)
			$t->set_value(null);
	}
	
	/**
	 * If this multitype is an array and not multiple, it tries to insert $value at offset $key.
	 * 
	 * @param PC_Obj_MultiType $key the key; null = next array key
	 * @param PC_Obj_MultiType $value the value to set
	 */
	public function array_insert($key,$value)
	{
		if($this->get_array() === null || ($key !== null && ($skey = $key->get_scalar()) === null))
			return;
		$first = $this->get_first();
		if($key === null)
			$skey = $first->get_next_array_key();
		$first->set_array_type($skey,$value);
	}
	
	/**
	 * @return PC_Obj_Type the first type of this multitype
	 */
	public function get_first()
	{
		return $this->types[0];
	}
	
	/**
	 * Expects that this multitype contains one type, a scalar. If so and the value is known the
	 * value is returned. Otherwise you get null.
	 * 
	 * @return int|float|bool|string the value of this multitype, otherwise null
	 */
	public function get_scalar()
	{
		if(count($this->types) != 1)
			return false;
		$first = $this->get_first();
		if($first->is_scalar() && !$first->is_val_unknown())
			return $first->get_value();
		return null;
	}
	
	/**
	 * Expects that this multitype contains one type, a scalar. If so and the value is known the
	 * value is returned, as string. Otherwise you get null.
	 * 
	 * @return string the value of this multitype as string, otherwise null
	 */
	public function get_string()
	{
		if(count($this->types) != 1)
			return null;
		$first = $this->get_first();
		if($first->is_scalar() && !$first->is_val_unknown())
			return (string)$first->get_value();
		return null;
	}
	
	/**
	 * Expects that this multitype contains one type, an array. If so it is returned. Otherwise null
	 * is returned
	 * 
	 * @return array the array or null
	 */
	public function get_array()
	{
		if(count($this->types) != 1)
			return null;
		$first = $this->get_first();
		if($first->get_type() != PC_Obj_Type::TARRAY)
			return null;
		$val = $first->get_value();
		return $val === null ? array() : $val;
	}
	
	/**
	 * Expects that this multitype contains one type, an object. If so and the classname is known
	 * it is returned. Otherwise null is returned
	 * 
	 * @return string the classname or null
	 */
	public function get_classname()
	{
		if(count($this->types) != 1)
			return null;
		$first = $this->get_first();
		if($first->get_type() != PC_Obj_Type::OBJECT)
			return null;
		$name = $first->get_class();
		return $name ? $name : null;
	}
	
	/**
	 * @return boolean wether the type is unknown
	 */
	public function is_unknown()
	{
		return count($this->types) == 0;
	}
	
	/**
	 * @return boolean wether the value is unknown (which is also true, if the type is unknown etc.)
	 */
	public function is_val_unknown()
	{
		return count($this->types) != 1 || $this->get_first()->is_val_unknown();
	}
	
	/**
	 * For arrays: Check if any element is unknown
	 * 
	 * @return boolean true if any element is unknown
	 */
	public function is_array_unknown()
	{
		if(count($this->types) != 1)
			return true;
		return $this->get_first()->is_array_unknown();
	}
	
	/**
	 * @return boolean wether multiple types are allowed
	 */
	public function is_multiple()
	{
		return count($this->types) > 1;
	}
	
	/**
	 * @return array all types (PC_Obj_Type)
	 */
	public function get_types()
	{
		return $this->types;
	}
	
	/**
	 * Checks wether it contains the given type
	 *
	 * @param PC_Obj_Type $type the type
	 * @return boolean true if so
	 */
	public function contains($type)
	{
		if(!($type instanceof PC_Obj_Type))
			FWS_Helper::def_error('instance','type','PC_Obj_Type',$type);
		
		foreach($this->types as $t)
		{
			if($t->equals($type))
				return true;
		}
		return false;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	public function __ToString()
	{
		return $this->is_unknown() ? 'unknown' : implode(' or ',$this->types);
	}
}
?>