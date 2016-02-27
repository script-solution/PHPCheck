<?php
/**
 * Contains the modifiers-analyzer class
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
 * Is responsible for analyzing the modifiers of called methods.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Modifiers extends PC_Analyzer
{
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Env $env the environment
	 */
	public function __construct($env)
	{
		parent::__construct($env);
	}
	
	/**
	 * Analyzes whether the given method is visible at the given call.
	 *
	 * @param PC_Engine_Scope $scope the current scope
	 * @param PC_Obj_Call $call the method call
	 * @param PC_Obj_Method $method the method object
	 */
	public function analyze($scope,$call,$method)
	{
		if($method && $method->get_visibility() == PC_Obj_Method::V_PUBLIC)
			return;
		
		// if we don't know it yet, search in the superclasses. in this case, it's always private
		if(!$method && $call->get_class())
			$method = $this->env->get_types()->get_method_of_super($call->get_class(),$call->get_function());
		else if($method)
		{
			$cur_class = $scope->get_name_of(T_CLASS_C);
			if($cur_class)
			{
				// the owner is not restricted in any way
				if($cur_class == $call->get_class())
					return;
				
				// calling a protected method from a subclass is ok as well
				$isprot = $method->get_visibility() == PC_Obj_Method::V_PROTECTED;
				if($isprot && $this->is_subclass_of($cur_class,$call->get_class()))
					return;
			}
		}
		
		if($method)
		{
			// everything else is not. i.e., calling a private/protected method from a non-subclass
			$name = $call->get_class().'::'.$call->get_function();
			$this->report(
				$method,
				'The function/method "'.$name.'" is '.$method->get_visibility().' at this location',
				PC_Obj_Error::E_S_METHOD_VISIBILITY
			);
		}
	}
	
	/**
	 * Determines whether $class is a subclass of $super.
	 *
	 * @param string $class the class name
	 * @param string $super the potential superclass name
	 * @return bool true if so
	 */
	private function is_subclass_of($class,$super)
	{
		$cobj = $this->env->get_types()->get_class($class);
		if(!$cobj)
			return false;
		if($cobj->get_super_class() == $super)
			return true;
		return $this->is_subclass_of($cobj->get_super_class(),$super);
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
