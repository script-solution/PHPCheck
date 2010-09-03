<?php
/**
 * Contains the class PC_Obj_Class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * This is used to store the properties of a class-definition
 * 
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Class extends PC_Obj_Modifiable
{
	// use an invalid identifier for an unknown class
	const UNKNOWN = '#UNKNOWN';
	
	/**
	 * The id of this class
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 * Is it an interface?
	 *
	 * @var boolean
	 */
	private $interface = false;
	
	/**
	 * The super-class-name or null
	 *
	 * @var string
	 */
	private $superclass;
	
	/**
	 * An array of interfaces that are implemented. This is also used for interfaces, that extend
	 * other interfaces.
	 *
	 * @var array
	 */
	private $interfaces;
	
	/**
	 * An array of class-constants
	 *
	 * @var array
	 */
	private $constants;
	
	/**
	 * An array of fields of the class, represented as PC_Obj_Field
	 *
	 * @var array
	 */
	private $fields;
	
	/**
	 * An array of methods, represented as PC_Obj_Method
	 *
	 * @var array
	 */
	private $methods;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 * @param int $id the class-id
	 */
	public function __construct($file,$line,$id = 0)
	{
		parent::__construct($file,$line);
		
		$this->id = $id;
		$this->interfaces = array();
		$this->fields = array();
		$this->methods = array();
		$this->constants = array();
	}
	
	/**
	 * @return int the id of this class (in the db). May be 0 if not loaded from db
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * @return boolean wether it is an interface
	 */
	public function is_interface()
	{
		return $this->interface;
	}
	
	/**
	 * Sets wether it is an interface
	 *
	 * @param boolean $if the new value
	 */
	public function set_interface($if)
	{
		$this->interface = (bool)$if;
	}
	
	/**
	 * @return string the name of the super-class or null
	 */
	public function get_super_class()
	{
		return $this->superclass;
	}
	
	/**
	 * Sets the super-class-name
	 *
	 * @param string $class the new value
	 */
	public function set_super_class($class)
	{
		$this->superclass = $class;
	}
	
	/**
	 * Adds the interface with given name to the interface-list
	 *
	 * @param string $interface the interface-name
	 */
	public function add_interface($interface)
	{
		$this->interfaces[] = $interface;
	}
	
	/**
	 * @return array a numeric array with the interface-names that are implemented
	 */
	public function get_interfaces()
	{
		return $this->interfaces;
	}
	
	/**
	 * @return array the class-constants: <code>array(<name> => <type>)</code>
	 */
	public function get_constants()
	{
		return $this->constants;
	}
	
	/**
	 * Returns the constant with given name
	 *
	 * @param string $name the constant-name
	 * @return PC_Obj_Constant the constant or null
	 */
	public function get_constant($name)
	{
		return isset($this->constants[$name]) ? $this->constants[$name] : null;
	}
	
	/**
	 * Adds the given constant to the class
	 *
	 * @param PC_Obj_Constant $const the constant
	 */
	public function add_constant($const)
	{
		if(!($const instanceof PC_Obj_Constant))
			FWS_Helper::def_error('instance','const','PC_Obj_Constant',$const);
		
		$this->constants[$const->get_name()] = $const;
	}
	
	/**
	 * @return array an array of PC_Obj_Field's
	 */
	public function get_fields()
	{
		return $this->fields;
	}
	
	/**
	 * Returns the class-field with given name
	 *
	 * @param string $name the name (including "$"!)
	 * @return PC_Obj_Field the field or null
	 */
	public function get_field($name)
	{
		return isset($this->fields[$name]) ? $this->fields[$name] : null;
	}
	
	/**
	 * Adds the given field to the class
	 *
	 * @param PC_Obj_Field $field the field
	 */
	public function add_field($field)
	{
		if(!($field instanceof PC_Obj_Field))
			FWS_Helper::def_error('instance','field','PC_Obj_Field',$field);
		
		$this->fields[$field->get_name()] = $field;
	}
	
	/**
	 * @return array an array of PC_Obj_Method's
	 */
	public function get_methods()
	{
		return $this->methods;
	}
	
	/**
	 * Checks wether the given method exists
	 *
	 * @param string $name the method-name
	 * @return boolean true if so
	 */
	public function contains_method($name)
	{
		return isset($this->methods[$name]);
	}
	
	/**
	 * Returns with method with given name
	 *
	 * @param string $name the method-name
	 * @return PC_Obj_Method the method or null
	 */
	public function get_method($name)
	{
		if(!isset($this->methods[$name]))
			return null;
		return $this->methods[$name];
	}
	
	/**
	 * Adds the given method to the class
	 *
	 * @param PC_Obj_Method $method the method
	 */
	public function add_method($method)
	{
		if(!($method instanceof PC_Obj_Method))
			FWS_Helper::def_error('instance','method','PC_Obj_Method',$method);
		
		$this->methods[$method->get_name()] = $method;
	}
	
	/**
	 * Builds the class-signature
	 * 
	 * @param bool $hlnames if enabled, class-names are enclosed in #
	 * @return string the signature
	 */
	public function get_signature($hlnames = false)
	{
		$name = ($hlnames ? '#'.$this->get_name().'#' : $this->get_name());
		$str = '';
		if($this->interface)
		{
			$str .= 'interface '.$name.' ';
			if(count($this->interfaces) > 0)
			{
				if($hlnames)
					$str .= 'extends #'.implode('#, #',$this->interfaces).'# ';
				else
					$str .= 'extends '.implode(', ',$this->interfaces).' ';
			}
		}
		else
		{
			if($this->is_abstract())
				$str .= 'abstract ';
			else if($this->is_final())
				$str .= 'final ';
			$str .= 'class '.$name.' ';
			if($this->superclass)
				$str .= 'extends '.($hlnames ? '#'.$this->superclass.'#' : $this->superclass).' ';
			if(count($this->interfaces) > 0)
			{
				if($hlnames)
					$str .= 'implements #'.implode('#, #',$this->interfaces).'# ';
				else
					$str .= 'implements '.implode(', ',$this->interfaces).' ';
			}
		}
		return $str;
	}
	
	public function __toString()
	{
		$str = $this->get_signature();
		$str .= '{'."\n";
		foreach($this->constants as $const)
			$str .= "\t".$const.";\n";
		if(count($this->constants))
			$str .= "\n";
		foreach($this->fields as $field)
			$str .= "\t".$field.";\n";
		if(count($this->fields))
			$str .= "\n";
		foreach($this->methods as $method)
			$str .= "\t".$method.";\n";
		$str .= '}';
		return $str;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
?>