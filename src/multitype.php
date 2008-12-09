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
class PC_MultiType extends FWS_Object
{
	/**
	 * Builds the MultiType-instance from the given name. '|' will be assumed as separator
	 * of the types.
	 *
	 * @param string $name the name
	 * @return PC_MultiType the instance
	 */
	public static function get_type_by_name($name)
	{
		$types = explode('|',$name);
		$ts = array();
		foreach($types as $type)
		{
			$type = trim($type);
			$ts[] = PC_Type::get_type_by_name($type);
		}
		return new PC_MultiType($ts);
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
	 * @param array $types the types to set
	 */
	public function __construct($types = array())
	{
		parent::__construct();
		
		$this->types = $types;
	}
	
	/**
	 * @return boolean wether the type is unknown
	 */
	public function is_unknown()
	{
		if(count($this->types) == 0)
			return true;
		
		// TODO keep this?
		foreach($this->types as $type)
		{
			if($type->is_unknown())
				return true;
		}
		return false;
	}
	
	/**
	 * @return boolean wether multiple types are allowed
	 */
	public function is_multiple()
	{
		return count($this->types) > 1;
	}
	
	/**
	 * @return array all types (PC_Type)
	 */
	public function get_types()
	{
		return $this->types;
	}
	
	/**
	 * Checks wether it contains the given type
	 *
	 * @param PC_Type $type the type
	 * @return boolean true if so
	 */
	public function contains($type)
	{
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
		// special case
		if($type->get_type() == PC_Type::UNKNOWN && count($this->types) == 0)
			return true;
		
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