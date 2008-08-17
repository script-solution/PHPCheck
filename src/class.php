<?php
/**
 * Contains the class PC_Class
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
class PC_Class extends PC_Modifiable
{
	// use an invalid identifier for an unknown class
	const UNKNOWN = '#UNKNOWN';
	
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
	 * An array of fields of the class, represented as PC_Field
	 *
	 * @var array
	 */
	private $fields;
	
	/**
	 * An array of methods, represented as PC_Method
	 *
	 * @var array
	 */
	private $methods;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 */
	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
		
		$this->interfaces = array();
		$this->fields = array();
		$this->methods = array();
		$this->constants = array();
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
	 * @param PC_Type $type the type or null
	 */
	public function get_constant($name)
	{
		return isset($this->constants[$name]) ? $this->constants[$name] : null;
	}
	
	/**
	 * Adds the given constant to the class
	 *
	 * @param string $name the constant-name
	 * @param PC_Type $type the type
	 */
	public function add_constant($name,$type)
	{
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
		$this->constants[$name] = $type;
	}
	
	/**
	 * @return array an array of PC_Field's
	 */
	public function get_fields()
	{
		return $this->fields;
	}
	
	/**
	 * Returns the class-field with given name
	 *
	 * @param string $name the name (including "$"!)
	 * @return PC_Field the field or null
	 */
	public function get_field($name)
	{
		return isset($this->fields[$name]) ? $this->fields[$name] : null;
	}
	
	/**
	 * Adds the given field to the class
	 *
	 * @param PC_Field $field the field
	 */
	public function add_field($field)
	{
		if(!($field instanceof PC_Field))
			FWS_Helper::def_error('instance','field','PC_Field',$field);
		
		$this->fields[$field->get_name()] = $field;
	}
	
	/**
	 * @return array an array of PC_Method's
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
	 * @return PC_Method the method or null
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
	 * @param PC_Method $method the method
	 */
	public function add_method($method)
	{
		if(!($method instanceof PC_Method))
			FWS_Helper::def_error('instance','method','PC_Method',$method);
		
		$this->methods[$method->get_name()] = $method;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
?>