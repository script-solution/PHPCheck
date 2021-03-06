<?php
/**
 * Contains the scope-class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Manages the scope for the statement-scanner
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_Scope extends FWS_Object
{
	/**
	 * The current class we're in or empty; we can't nest classes
	 * (Although the grammar allows defining classes in class-methods, PHP silently dies in this case :))
	 * 
	 * @var string
	 */
	private $class = '';
	/**
	 * Whether the current class-definition is in a function
	 * 
	 * @var bool
	 */
	private $classInFunc = false;
	/**
	 * A stack of function-names we're currently in
	 * 
	 * @var array
	 */
	private $funcscope = array(PC_Obj_Variable::SCOPE_GLOBAL);
	
	/**
	 * @param bool $parent whether to use the parent scope
	 * @return string the name of the current/parent scope
	 */
	public function get_name($parent = false)
	{
		$name = '';
		if(($class = $this->get_name_of(T_CLASS_C)))
			$name .= $class.'::';
		$off = $parent ? 2 : 1;
		assert(count($this->funcscope) >= $off);
		$name .= $this->funcscope[count($this->funcscope) - $off];
		return $name;
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @param bool $parent whether to use the parent scope
	 * @return string the scope-part-name
	 */
	public function get_name_of($part,$parent = false)
	{
		$str = '';
		switch($part)
		{
			case T_METHOD_C:
			case T_FUNC_C:
				$off = $parent ? 2 : 1;
				if(count($this->funcscope) > $off)
					$str = $this->funcscope[count($this->funcscope) - $off];
				break;
			case T_CLASS_C:
				// if we define a function in a class-method, it is global
				if($this->class && !$this->classInFunc && count($this->funcscope) > 2)
					$str = '';
				else
					$str = $this->class;
				break;
		}
		return $str;
	}
	
	/**
	 * Enters the class with given name
	 * 
	 * @param string $name the class-name
	 */
	public function enter_class($name)
	{
		assert($this->class == '');
		$this->classInFunc = count($this->funcscope) > 1;
		$this->class = $name;
	}
	
	/**
	 * Enters the function with given name
	 * 
	 * @param string $name the function-name
	 */
	public function enter_function($name)
	{
		array_push($this->funcscope,$name);
	}
	
	/**
	 * Leaves the current class
	 */
	public function leave_class()
	{
		assert($this->class != '');
		$this->class = '';
		$this->classInFunc = false;
	}
	
	/**
	 * Leaves the current function
	 */
	public function leave_function()
	{
		// we can't leave the global scope
		assert(count($this->funcscope) > 1);
		array_pop($this->funcscope);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
