<?php
/**
 * Contains the return-analyzer class
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
 * Is responsible for analyzing return types.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Returns extends PC_Analyzer
{
	/**
	 * An array of all return-types of the current function/method
	 * 
	 * @var array
	 */
	private $allrettypes = array();
	
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
	 * @param PC_Obj_MultiType $expr the returned expression (null for "return;")
	 */
	public function add($expr)
	{
		$this->allrettypes[] = $expr;
	}
	
	/**
	 * Analyzes the return-types of the current function and reports errors, if necessary
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
			$hasnull = false;
			$hasother = false;
			foreach($this->allrettypes as $t)
			{
				if($t === null)
					$hasnull = true;
				else
					$hasother = true;
			}
			
			if(count($this->allrettypes) == 0)
				$mtype = PC_Obj_MultiType::create_void();
			else
			{
				$mtype = new PC_Obj_MultiType();
				foreach($this->allrettypes as $t)
				{
					if($t !== null)
						$mtype->merge($t,false);
					else
						$mtype->merge(PC_Obj_MultiType::create_void(),false);
				}
			}
			
			if($classname && $funcname == '__construct')
			{
				if($hasother)
				{
					$this->report(
						$func,
						'The constructor of "'.$classname.'" has a return-statement with expression',
						PC_Obj_Error::E_S_CONSTR_RETURN
					);
				}
			}
			else
			{
				$name = ($classname ? '#'.$classname.'#::' : '').$funcname;
				// empty return-expression and non-empty?
				if($hasnull && $hasother)
				{
					$this->report(
						$func,
						'The function/method "'.$name.'" has return-'
						.'statements without expression and return-statements with expression',
						PC_Obj_Error::E_S_MIXED_RET_AND_NO_RET
					);
				}
				
				$void = new PC_Obj_Type(PC_Obj_Type::VOID);
				$docreturn = $func->has_return_doc() && !$func->get_return_type()->contains($void);
				if($docreturn && !$hasother)
				{
					$this->report(
						$func,
						'The function/method "'.$name.'" has a return-specification in PHPDoc'
						.', but does not return a value',
						PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET
					);
				}
				else if(!$docreturn && !$func->is_anonymous() && $hasother)
				{
					$this->report(
						$func,
						'The function/method "'.$name.'" has no return-specification in PHPDoc'
						.', but does return a value',
						PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC
					);
				}
				else if($this->has_forbidden($this->allrettypes,$func->get_return_type()))
				{
					$this->report(
						$func,
						'The return-specification (PHPDoc) of function/method "'.$name.'" does not match with '
						.'the returned values (spec="'.$func->get_return_type().'", returns="'.$mtype.'")',
						PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC
					);
				}
			}
			
			if($func->get_return_type()->is_unknown())
				$func->set_return_type($mtype);
		}
		
		$this->allrettypes = array();
	}
	
	/**
	 * Checks whether $types contains a type, that is not contained in $mtype.
	 * 
	 * @param array $types the types
	 * @param PC_Obj_MultiType $mtype the multitype
	 * @return bool true if so
	 */
	private function has_forbidden($types,$mtype)
	{
		// if the type is unknown (mixed), its always ok
		if($mtype->is_unknown())
			return false;
		foreach($types as $t)
		{
			if($t !== null)
			{
				foreach($t->get_types() as $t1)
				{
					if(!$mtype->contains($t1))
						return true;
				}
			}
		}
		return false;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
