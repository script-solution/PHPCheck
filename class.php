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
	 * An array of fields of the class, represented as PC_Variable
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
		$this->interface = $if;
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
	 * Adds the given field to the class
	 *
	 * @param PC_Varible $field the field
	 */
	public function add_field($field)
	{
		$this->fields[$field->get_name()] = $field;
	}
	
	/**
	 * @return array an array of PC_Variable's
	 */
	public function get_fields()
	{
		return $this->fields;
	}
	
	/**
	 * Adds the given method to the class
	 *
	 * @param PC_Method $method the method
	 */
	public function add_method($method)
	{
		$this->methods[$method->get_name()] = $method;
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
	
	protected function get_print_vars()
	{
		return array_merge(parent::get_print_vars(),get_object_vars($this));
	}
}
?>