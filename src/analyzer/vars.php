<?php
/**
 * Contains the variable-analyzer class
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
 * Is responsible for finding unused variables and parameters.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Vars extends PC_Analyzer
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
	 * Finds variables that have not been read in the given scope.
	 *
	 * @param PC_Engine_VarContainer $vars the variables
	 * @param string $scope the scope
	 */
	public function analyze($vars,$scope)
	{
		if(!$this->env->get_options()->get_report_unused())
			return;
		
		$accesses = $vars->get_accesses();
		if(isset($accesses[$scope]))
		{
			// determine the parameters to handle them special
			$params = array();
			if($scope != PC_Obj_Variable::SCOPE_GLOBAL)
			{
				if(strstr($scope,'::'))
				{
					list($cname,$fname) = explode('::',$scope);
					$func = $this->env->get_types()->get_method($cname,$fname);
				}
				else
					$func = $this->env->get_types()->get_function($scope);
				
				if($func)
				{
					// don't report anything for abstract methods
					if($func->is_abstract())
						return;
					
					$params = $func->get_params();
				}
			}
			
			foreach($accesses[$scope] as $vname => $count)
			{
				if($count == 0)
				{
					if(isset($params[$vname]))
					{
						// if it's a method and this method exists in a superclass, too, don't report the error
						// because it happens quite often that you don't use a parameter in subclasses, but you
						// are still forced to specify it.
						if(strstr($scope,'::'))
						{
							list($cname,$fname) = explode('::',$scope);
							$class = $this->env->get_types()->get_class($cname);
							if($class)
							{
								$super = $class->get_super_class();
								if($this->env->get_types()->get_method_of_super($super,$fname) !== null)
									return;
								
								foreach($class->get_interfaces() as $if)
								{
									if($this->env->get_types()->get_method_of_super($if,$fname))
										return;
								}
							}
						}
						
						// for references, write-only is ok
						if($params[$vname]->is_reference())
							return;
						
						$name = 'parameter';
						$error = PC_Obj_Error::E_S_PARAM_UNUSED;
					}
					else
					{
						$name = 'variable';
						$error = PC_Obj_Error::E_S_VAR_UNUSED;
					}

					$var = $vars->get($scope,$vname);
					$this->report(
						$var,
						'The '.$name.' $'.$vname.' in #'.$scope.'# is unused',
						$error
					);
				}
			}
		}
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
