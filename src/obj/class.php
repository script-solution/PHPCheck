<?php
/**
 * Contains the class PC_Obj_Class
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
 * This is used to store the properties of a class-definition
 * 
 * @package			PHPCheck
 * @subpackage	src.obj
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
	 * The project-id
	 * 
	 * @var int
	 */
	private $pid;
	
	/**
	 * Is it an interface?
	 *
	 * @var boolean
	 */
	private $interface = false;
	
	/**
	 * Whether it is an anonymous class.
	 *
	 * @var boolean
	 */
	private $anonymous = false;
	
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
	private $constants = null;
	
	/**
	 * An array of fields of the class, represented as PC_Obj_Field
	 *
	 * @var array
	 */
	private $fields = null;
	
	/**
	 * An array of methods, represented as PC_Obj_Method
	 *
	 * @var array
	 */
	private $methods = null;
	
	/**
	 * The version  info
	 *
	 * @var PC_Obj_VersionInfo
	 */
	private $version;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 * @param int $id the class-id (just used when loaded from db)
	 * @param int $pid the project-id (just used when loaded from db)
	 * @param bool $lazy whether to load fields, methods and constants lazy
	 */
	public function __construct($file,$line,$id = 0,$pid = 0,$lazy = true)
	{
		parent::__construct($file,$line);
		
		$this->id = $id;
		$this->pid = $pid;
		$this->interfaces = array();
		if($id === 0 || !$lazy)
		{
			$this->fields = array();
			$this->methods = array();
			$this->constants = array();
		}
		$this->version = new PC_Obj_VersionInfo();
	}
	
	/**
	 * @return int the id of this class (in the db). May be 0 if not loaded from db
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * @return boolean whether it is an interface
	 */
	public function is_interface()
	{
		return $this->interface;
	}
	
	/**
	 * Sets whether it is an interface
	 *
	 * @param boolean $if the new value
	 */
	public function set_interface($if)
	{
		$this->interface = (bool)$if;
	}
	
	/**
	 * @return boolean whether the class is anonymous
	 */
	public function is_anonymous()
	{
		return $this->anonymous;
	}
	
	/**
	 * Sets whether this an anonymous class
	 * 
	 * @param bool $anon the new value
	 */
	public function set_anonymous($anon)
	{
		$this->anonymous = $anon;
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
	 * @param string $interface the name
	 * @return boolean true if this class implements the given interface
	 */
	public function is_implementing($interface)
	{
		foreach($this->interfaces as $if)
		{
			if(strcasecmp($if,$interface) == 0)
				return true;
		}
		return false;
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
		$this->load_constants();
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
		$this->load_constants();
		$lname = strtolower($name);
		return isset($this->constants[$lname]) ? $this->constants[$lname] : null;
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
		
		$this->load_constants();
		$this->constants[strtolower($const->get_name())] = $const;
	}
	
	/**
	 * Loads the constants from db, if not already done
	 */
	private function load_constants()
	{
		if($this->constants === null)
		{
			$this->constants = array();
			foreach(PC_DAO::get_constants()->get_list($this->id,'','',$this->pid) as $const)
				$this->constants[strtolower($const->get_name())] = $const;
		}
	}
	
	/**
	 * @return array an array of PC_Obj_Field's
	 */
	public function get_fields()
	{
		$this->load_fields();
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
		$this->load_fields();
		$lname = strtolower($name);
		return isset($this->fields[$lname]) ? $this->fields[$lname] : null;
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
		
		$this->load_fields();
		$this->fields[strtolower($field->get_name())] = $field;
	}
	
	/**
	 * Loads the fields from db, if not already done
	 */
	private function load_fields()
	{
		if($this->fields === null)
		{
			$this->fields = array();
			foreach(PC_DAO::get_classfields()->get_all($this->id,$this->pid) as $field)
				$this->fields[strtolower($field->get_name())] = $field;
		}
	}
	
	/**
	 * @return array an array of PC_Obj_Method's
	 */
	public function get_methods()
	{
		$this->load_methods();
		return $this->methods;
	}
	
	/**
	 * Checks whether the given method exists
	 *
	 * @param string $name the method-name
	 * @return boolean true if so
	 */
	public function contains_method($name)
	{
		$this->load_methods();
		return isset($this->methods[strtolower($name)]);
	}
	
	/**
	 * Returns with method with given name
	 *
	 * @param string $name the method-name
	 * @return PC_Obj_Method the method or null
	 */
	public function get_method($name)
	{
		$this->load_methods();
		$lname = strtolower($name);
		if(!isset($this->methods[$lname]))
			return null;
		return $this->methods[$lname];
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
		
		$this->load_methods();
		$this->methods[strtolower($method->get_name())] = $method;
	}
	
	/**
	 * Loads the fields from db, if not already done
	 */
	private function load_methods()
	{
		if($this->methods === null)
		{
			$this->methods = array();
			foreach(PC_DAO::get_functions()->get_list($this->id,0,0,'','',$this->pid) as $method)
				$this->methods[strtolower($method->get_name())] = $method;
		}
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
			$str .= 'class ';
			if(!$this->anonymous)
				$str .= $name.' ';
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
	
	/**
	 * @return PC_Obj_VersionInfo the version info
	 */
	public function get_version()
	{
		return $this->version;
	}
	
	public function __toString()
	{
		$this->load_fields();
		$this->load_constants();
		$this->load_methods();
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
