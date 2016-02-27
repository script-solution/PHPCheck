<?php
/**
 * Contains the classes-analyzer class
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
 * Is responsible for finding errors in classes.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Classes extends PC_Analyzer
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
	 * Analyzes the given class
	 * 
	 * @param PC_Obj_Class $class the class
	 */
	public function analyze($class)
	{
		// test wether abstract is used in a usefull way
		$abstractcount = 0;
		foreach($class->get_methods() as $method)
		{
			if($method->is_abstract())
				$abstractcount++;
		}
		
		if(!$class->is_abstract() && $abstractcount > 0)
		{
			$this->report(
				$class,
				'The class "#'.$class->get_name().'#" is NOT abstract but'
					.' contains abstract methods!',
				PC_Obj_Error::E_S_CLASS_NOT_ABSTRACT
			);
		}
		
		// check super-class
		if($class->get_super_class())
		{
			// do we know the class?
			$sclass = $this->env->get_types()->get_class($class->get_super_class());
			
			if($sclass === null)
			{
				$this->report(
					$class,
					'The class "#'.$class->get_super_class().'#" does not exist!',
					PC_Obj_Error::E_S_CLASS_MISSING
				);
			}
			// super-class final?
			else if($sclass->is_final())
			{
				$this->report(
					$class,
					'The class "#'.$class->get_name().'#" inherits from the final '
						.'class "#'.$sclass->get_name().'#"!',
					PC_Obj_Error::E_S_FINAL_CLASS_INHERITANCE
				);
			}
		}
		
		// check implemented interfaces
		foreach($class->get_interfaces() as $ifname)
		{
			$if = $this->env->get_types()->get_class($ifname);
			if($if === null)
			{
				$this->report(
					$class,
					'The interface "#'.$ifname.'#" does not exist!',
					PC_Obj_Error::E_S_INTERFACE_MISSING
				);
			}
			else if(!$if->is_interface())
			{
				$this->report(
					$class,
					'"#'.$ifname.'#" is no interface, but implemented by class #'.$class->get_name().'#!',
					PC_Obj_Error::E_S_IF_IS_NO_IF
				);
			}
		}
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
