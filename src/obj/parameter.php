<?php
/**
 * Contains the parameter-class
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
 * Is used for function-/method-parameters
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Obj_Parameter extends FWS_Object
{
	/**
	 * The name of the variable
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Param optional?
	 *
	 * @var boolean
	 */
	private $optional = false;
	
	/**
	 * Is it a reference?
	 * 
	 * @var boolean
	 */
	private $reference = false;
	
	/**
	 * Whether its the first variable argument of the function
	 * 
	 * @var boolean
	 */
	private $first_var_arg = false;
	
	/**
	 * Whether this parameter has a PHPDoc-description
	 * 
	 * @var boolean
	 */
	private $has_doc = false;
	
	/**
	 * The possible types of the parameter
	 *
	 * @var PC_Obj_MultiType
	 */
	private $mtype;
	
	/**
	 * Whether the current mtype is from the default value.
	 *
	 * @var boolean
	 */
	private $mtype_is_default = false;
	
	/**
	 * Constructor
	 * 
	 * @param string $name the name
	 * @param PC_Obj_MultiType $mtype the type (default = unknown)
	 */
	public function __construct($name = '',$mtype = null)
	{
		parent::__construct();
		
		$this->name = $name;
		$this->set_mtype($mtype === null ? new PC_Obj_MultiType() : $mtype);
	}
	
	/**
	 * @return string the name
	 */
	public function get_name()
	{
		return $this->name;
	}
	
	/**
	 * Sets the name
	 *
	 * @param string $name the new value
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return boolean whether this parameter has a PHPDoc-description
	 */
	public function has_doc()
	{
		return $this->has_doc;
	}
	
	/**
	 * Sets whether this parameter has a PHPDoc-description
	 * 
	 * @param boolean $has_doc the new value
	 */
	public function set_has_doc($has_doc)
	{
		$this->has_doc = $has_doc;
	}
	
	/**
	 * @return PC_Obj_MultiType all types that may be used for this parameter
	 */
	public function get_mtype()
	{
		return $this->mtype;
	}
	
	/**
	 * Sets the multi-type-instance for this parameter. Will reset is_mtype_default() to false.
	 *
	 * @param PC_Obj_MultiType $mtype the new value
	 */
	public function set_mtype($mtype)
	{
		if(!($mtype instanceof PC_Obj_MultiType))
			FWS_Helper::def_error('instance','mtype','PC_Obj_MultiType',$mtype);
		
		$this->mtype = $mtype;
		$this->mtype_is_default = false;
	}
	
	/**
	 * @return boolean whether the current mtype is from the default value
	 */
	public function is_mtype_default()
	{
		return $this->mtype_is_default;
	}
	
	/**
	 * Sets whether the current mtype is from the default value
	 *
	 * @param boolean $def the new value
	 */
	public function set_mtype_default($def)
	{
		$this->mtype_is_default = $def;
	}
	
	/**
	 * @return boolean whether the parameter is optional
	 */
	public function is_optional()
	{
		return $this->optional;
	}
	
	/**
	 * Sets whether the parameter is optional
	 *
	 * @param boolean $opt the new value
	 */
	public function set_optional($opt)
	{
		$this->optional = (bool)$opt;
		if($this->optional)
			$this->first_var_arg = false;
	}
	
	/**
	 * @return boolean whether the parameter is a reference
	 */
	public function is_reference()
	{
		return $this->reference;
	}
	
	/**
	 * Sets whether the parameter is a reference
	 *
	 * @param boolean $ref the new value
	 */
	public function set_reference($ref)
	{
		$this->reference = (bool)$ref;
	}
	
	/**
	 * @return boolean whether the parameter is the first variable argument
	 */
	public function is_first_vararg()
	{
		return $this->first_var_arg;
	}
	
	/**
	 * Sets whether the parameter is the first variable argument
	 *
	 * @param boolean $first the new value
	 */
	public function set_first_vararg($first)
	{
		$this->first_var_arg = (bool)$first;
		if($this->first_var_arg)
			$this->optional = false;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	public function __ToString()
	{
		$str = '';
		if($this->reference)
			$str .= '&';
		$str .= $this->mtype.($this->optional ? '?' : ($this->first_var_arg ? '*' : ''));
		return $str;
	}
}
