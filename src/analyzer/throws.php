<?php
/**
 * Contains the throws-analyzer class
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
 * Is responsible for analyzing thrown exceptions against the PHPDoc comments.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Throws extends PC_Analyzer
{
	/**
	 * An array of all throw types of the current function/method
	 *
	 * @var array
	 */
	private $allthrows = array();
	
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
	 * Adds the given return expression.
	 *
	 * @param string $type the type
	 * @param PC_Obj_MultiType $expr the thrown expression
	 */
	public function add($type,$expr)
	{
		$this->allthrows[] = array($type,$expr);
	}
	
	/**
	 * Analyzes all throw statements that have been found in the current function and checks their
	 * validity against the PHPDoc throw specifications.
	 *
	 * @param PC_Engine_Scope $scope the current scope
	 */
	public function analyze($scope)
	{
		$funcname = $scope->get_name_of(T_FUNC_C);
		$classname = $scope->get_name_of(T_CLASS_C);
		$func = $this->env->get_types()->get_method_or_func($classname,$funcname);
		if($func && !$func->is_abstract())
		{
			$name = ($classname ? '#'.$classname.'#::' : '').$funcname;
			foreach($func->get_throws() as $tclass => $ttype)
			{
				// if only the parent function specifies it, we are not forced to throw it
				if($ttype == PC_Obj_Method::THROW_PARENT)
					continue;
				
				$found = false;
				foreach($this->allthrows as list($origin,$mtype))
				{
					foreach($mtype->get_types() as $type)
					{
						if(strcasecmp($type->get_class(),$tclass) == 0)
						{
							$found = true;
							break;
						}
					}
				}
				
				if(!$found)
				{
					$this->report(
						$func,
						'The function/method "'.$name.'" throws "'.$tclass.'" according to PHPDoc'
						.', but does not throw it',
						PC_Obj_Error::E_S_DOC_WITHOUT_THROW
					);
				}
			}
			
			foreach($this->allthrows as list($origin,$mtype))
			{
				// ignore missing throws specifications that are only thrown by called functions
				if($origin == PC_Obj_Method::THROW_FUNC)
					continue;
				
				foreach($mtype->get_types() as $type)
				{
					if($type->get_type() == PC_Obj_Type::OBJECT)
					{
						if(!$func->contains_throw($type->get_class()))
						{
							$this->report(
								$func,
								'The function/method "'.$name.'" does not throw "'.$type.'" according to PHPDoc'
								.', but throws it',
								PC_Obj_Error::E_S_THROW_NOT_IN_DOC
							);
						}
					}
					else
					{
						$this->report(
							$func,
							'The function/method "'.$name.'" throws a non-object ('.$type.')',
							PC_Obj_Error::E_S_THROW_INVALID
						);
					}
				}
			}
		}
		
		$this->allthrows = array();
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
