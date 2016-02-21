<?php
/**
 * Contains the method-class
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
 * Is used to store the properties of a method / function
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Method extends PC_Obj_Modifiable implements PC_Obj_Visible
{
	/**
	 * The prefix for anonymous functions
	 *
	 * @var string
	 */
	const ANON_PREFIX 	= '#anon';
	
	/**
	 * Indicates that a function has a throw statement itself.
	 *
	 * @var string
	 */
	const THROW_SELF		= 'self';
	/**
	 * Indicates that a parent method has a throw specification.
	 *
	 * @var string
	 */
	const THROW_PARENT	= 'parent';
	/**
	 * Indicates that a function calls a function with a throw specification.
	 *
	 * @var string
	 */
	const THROW_FUNC		= 'func';
	
	/**
	 * The id of this function
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 * The visibility
	 *
	 * @var string
	 */
	private $visibility = self::V_PUBLIC;
	
	/**
	 * Wether the method is static
	 *
	 * @var boolean
	 */
	private $static = false;
	
	/**
	 * Whether it is an anonymous function.
	 *
	 * @var boolean
	 */
	private $anonymous = false;
	
	/**
	 * Wether this is a free function (belongs to no class)
	 *
	 * @var boolean
	 */
	private $free;
	
	/**
	 * The return-type
	 *
	 * @var PC_Obj_MultiType
	 */
	private $return;
	
	/**
	 * Whether this method has a PHPDoc-description of the return-type
	 * 
	 * @var bool
	 */
	private $has_return_doc = false;
	
	/**
	 * An array of parameter
	 * 
	 * @var array
	 */
	private $params;
	
	/**
	 * An array of class names
	 *
	 * @var array
	 */
	private $throws;
	
	/**
	 * The version since when this method exists
	 * 
	 * @var string
	 */
	private $since = '';
	
	/**
	 * The class-id
	 * 
	 * @var int
	 */
	private $class;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 * @param boolean $free wether it is a free function
	 * @param int $id the function-id
	 * @param int $classid the class-id if loaded from db
	 */
	public function __construct($file,$line,$free,$id = 0,$classid = 0)
	{
		parent::__construct($file,$line);
		
		$this->id = $id;
		$this->params = array();
		$this->throws = array();
		$this->return = new PC_Obj_MultiType();
		$this->free = $free;
		$this->class = $classid;
	}
	
	/**
	 * @return int the id of this function (in the db). May be 0 if not loaded from db
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * Sets the id of the method
	 * 
	 * @param int $id the id
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return bool whether this method has a PHPDoc-description of the return-type
	 */
	public function has_return_doc()
	{
		return $this->has_return_doc;
	}
	
	/**
	 * Sets whether this method has a PHPDoc-description of the return-type
	 * 
	 * @param bool $hasdoc the new value
	 */
	public function set_has_return_doc($hasdoc)
	{
		$this->has_return_doc = $hasdoc;
	}
	
	/**
	 * @return int the class-id (just present if loaded from db!)
	 */
	public function get_class()
	{
		return $this->class;
	}
	
	/**
	 * @return boolean wether this is a free function (belongs to no class)
	 */
	public function is_free()
	{
		return $this->free;
	}
	
	/**
	 * @return boolean wether the method is static
	 */
	public function is_static()
	{
		return $this->static;
	}
	
	/**
	 * Sets wether the method is static
	 *
	 * @param boolean $static the new value
	 */
	public function set_static($static)
	{
		$this->static = (bool)$static;
	}
	
	/**
	 * @return boolean whether the function is anonymous
	 */
	public function is_anonymous()
	{
		return $this->anonymous;
	}
	
	/**
	 * Sets whether this an anonymous function
	 * 
	 * @param bool $anon the new value
	 */
	public function set_anonymous($anon)
	{
		$this->anonymous = $anon;
	}
	
	/**
	 * @see PC_Obj_Visible::get_visibility()
	 * 
	 * @return string
	 */
	public function get_visibility()
	{
		return $this->visibility;
	}
	
	/**
	 * @see PC_Obj_Visible::set_visibility()
	 *
	 * @param string $visibility
	 */
	public function set_visibility($visibility)
	{
		$valid = array(self::V_PUBLIC,self::V_PROTECTED,self::V_PRIVATE);
		if(!in_array($visibility,$valid))
			FWS_Helper::def_error('inarray','visibility',$valid,$visibility);
		
		$this->visibility = $visibility;
	}
	
	/**
	 * @return PC_Obj_MultiType the return-type
	 */
	public function get_return_type()
	{
		return $this->return;
	}
	
	/**
	 * Sets the return-type of this method
	 *
	 * @param PC_Obj_MultiType $type the new value
	 */
	public function set_return_type($type)
	{
		if(!($type instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','type','PC_Obj_MultiType',$type);
		
		$this->return = $type;
	}
	
	/**
	 * Puts the parameter to the method
	 *
	 * @param PC_Obj_Parameter $param the param
	 */
	public function put_param($param)
	{
		if(!($param instanceof PC_Obj_Parameter))
			FWS_Helper::def_error('instance','param','PC_Obj_Parameter',$param);
		
		$this->params[$param->get_name()] = $param;
	}
	
	/**
	 * @return int the total number of params (including optional ones)
	 */
	public function get_param_count()
	{
		foreach($this->params as $param)
		{
			if($param->is_first_vararg())
				return -1;
		}
		return count($this->params);
	}
	
	/**
	 * Determines the number of required parameters
	 *
	 * @return int the number
	 */
	public function get_required_param_count()
	{
		$n = 0;
		foreach($this->params as $param)
		{
			if($param->is_first_vararg())
				break;
			if(!$param->is_optional())
				$n++;
		}
		return $n;
	}
	
	/**
	 * @return array associative array with the parameter
	 */
	public function get_params()
	{
		return $this->params;
	}
	
	/**
	 * Returns the parameter with given name
	 *
	 * @param string $name the param-name
	 * @return PC_Obj_Parameter the param or null
	 */
	public function get_param($name)
	{
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Checks wether the parameter with given name exists
	 *
	 * @param string $name the param-name
	 * @return boolean true if so
	 */
	public function contains_param($name)
	{
		return isset($this->params[$name]);
	}
	
	/**
	 * @return array all throw class names
	 */
	public function get_throws()
	{
		return $this->throws;
	}
	
	/**
	 * @param string $name the class name
	 * @return bool if the function throws an object of given class
	 */
	public function contains_throw($name)
	{
		return isset($this->throws[$name]);
	}
	
	/**
	 * Adds the given class name to the list of throws.
	 *
	 * @param string $name the class name
	 * @param string $type the type (THROW_*)
	 */
	public function add_throw($name,$type)
	{
		if(!isset($this->throws[$name]))
			$this->throws[$name] = $type;
	}
	
	/**
	 * @return string the version in which the method exists
	 */
	public function get_since()
	{
		return $this->since;
	}
	
	/**
	 * Sets the since-value
	 * 
	 * @param string $since the new value
	 */
	public function set_since($since)
	{
		$this->since = $since;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
	
	public function __ToString()
	{
		$str = '';
		if(!$this->free)
		{
			$str = $this->get_visibility().' ';
			if($this->is_static())
				$str .= 'static ';
			if($this->is_abstract())
				$str .= 'abstract ';
			if($this->is_final())
				$str .= 'final ';
		}
		$str .= 'function';
		if(!$this->is_anonymous())
		{
			$str .= ' ';
			if(PHP_SAPI != 'cli')
				$str .= '<b>';
			$str .= $this->get_name();
			if(PHP_SAPI != 'cli')
				$str .= '</b>';
		}
		$str .= '(';
		$str .= implode(', ',$this->get_params());
		$str .= '): '.$this->get_return_type();
		if(count($this->throws) > 0)
			$str .= ' throws '.implode(', ',array_keys($this->throws));
		return $str;
	}
}
