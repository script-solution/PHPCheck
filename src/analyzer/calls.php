<?php
/**
 * Contains the calls-analyzer class
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
 * Is responsible for analyzing function calls.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Calls extends PC_Analyzer
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
	 * Analyzes the given call
	 *
	 * @param PC_Obj_Call $call the function-/method call
	 */
	public function analyze($call)
	{
		if($call->get_class() !== null)
			$this->analyze_method_call($call);
		else
			$this->analyze_func_call($call);
	}
	
	/**
	 * Analyzes the given method call
	 *
	 * @param PC_Obj_Call $call the call
	 */
	private function analyze_method_call($call)
	{
		$name = $call->get_function();
		$classname = $call->get_class();
		$c = $this->env->get_types()->get_class($classname);
		if($c !== null)
		{
			if(!$c->contains_method($name))
			{
				// no obj-creation here because the constructor can be named '__construct' or
				// '<classname>'. the call uses always '__construct'.
				if(!$call->is_object_creation() && !$this->is_method_of_sub($c,$name))
				{
					$this->report(
						$call,
						'The method "'.$name.'" does not exist in the class "#'.$classname.'#"!',
						PC_Obj_Error::E_S_METHOD_MISSING
					);
				}
			}
			else if($call->is_object_creation() && $c->is_abstract())
			{
				$this->report(
					$call,
					'You can\'t instantiate the abstract class "#'.$c->get_name().'#"!',
					PC_Obj_Error::E_S_ABSTRACT_CLASS_INSTANTIATION
				);
			}
			else
			{
				// check for static / not static
				$m = $c->get_method($name);
				if($call->is_static() && !$m->is_static())
				{
					$this->report(
						$call,
						'Your call "'.$this->get_call_link($call).'" calls "'.$m->get_name()
							.'" statically, but the method is not static!',
						PC_Obj_Error::E_S_STATIC_CALL
					);
				}
				else if(!$call->is_static() && $m->is_static())
				{
					$this->report(
						$call,
						'Your call "'.$this->get_call_link($call).'" calls "'.$m->get_name()
							.'" not statically, but the method is static!',
						PC_Obj_Error::E_S_NONSTATIC_CALL
					);
				}
				
				$this->check_params($call,$m);
			}
		}
		else
		{
			$this->report(
				$call,
				'The class "#'.$classname.'#" does not exist!',
				PC_Obj_Error::E_S_CLASS_MISSING
			);
		}
	}
	
	/**
	 * Analyzes the given function call
	 *
	 * @param PC_Obj_Call $call the call
	 */
	private function analyze_func_call($call)
	{
		$name = $call->get_function();
		$func = $this->env->get_types()->get_function($name);
		if($func === null)
		{
			$this->report(
				$call,
				'The function "'.$name.'" does not exist!',
				PC_Obj_Error::E_S_FUNCTION_MISSING
			);
		}
		else
			$this->check_params($call,$func);
	}

	/**
	 * Checks the parameters of the call against the given ones
	 *
	 * @param PC_Obj_Call $call the call
	 * @param PC_Obj_Method $method the method
	 */
	private function check_params($call,$method)
	{
		$arguments = $call->get_arguments();
		$nparams = $method->get_required_param_count();
		$nmaxparams = $method->get_param_count();
		if(count($arguments) < $nparams || ($nmaxparams >= 0 && count($arguments) > $nmaxparams))
		{
			if($nparams != $nmaxparams)
				$reqparams = $nparams.' to '.($nmaxparams == -1 ? '*' : $nmaxparams);
			else
				$reqparams = $nparams;
			
			$this->report(
				$call,
				'The function/method called by "'.$this->get_call_link($call).'" requires '.$reqparams
					.' arguments but you have given '.count($arguments),
				PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT
			);
		}
		else
		{
			$i = 0;
			foreach($method->get_params() as $param)
			{
				/* @var $param PC_Obj_Parameter */
				$arg = isset($arguments[$i]) ? $arguments[$i] : null;
				// arg- or param-type unknown?
				if($arg === null || $arg->is_unknown() || $param->get_mtype()->is_unknown())
				{
					$i++;
					continue;
				}
				
				if(!$this->is_argument_ok($call,$arg,$param))
				{
					$trequired = $param->get_mtype();
					$tactual = $arg;
					$this->report(
						$call,
						'The parameter '.($i + 1).' in "'.$this->get_call_link($call).'" requires '
							.$this->get_article($trequired).' "'.$trequired.'" but you have given '
							.$this->get_article($tactual).' "'.($tactual === null ? "<i>NULL</i>" : $tactual).'"',
						PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE
					);
				}
				$i++;
			}
		}
	}
	
	/**
	 * Checks whether $arg is ok for $param
	 *
	 * @param PC_Obj_Location $loc the location
	 * @param PC_Obj_MultiType $arg the argument
	 * @param PC_Obj_Parameter $param the parameter
	 * @return boolean true if so
	 */
	private function is_argument_ok($loc,$arg,$param)
	{
		// not present but required?
		if($arg === null && !$param->is_optional() && !$param->is_first_vararg())
			return false;
		
		// unknown / not present
		if($arg === null)
			return true;
		
		// callables are special
		if($param->get_mtype()->get_first()->get_type() == PC_Obj_Type::TCALLABLE)
		{
			if(!$arg->is_unknown())
			{
				$this->check_callable($loc,$arg);
				return true;
			}
		}
		
		// arg in the allowed types?
		return $this->env->get_types()->is_type_conforming($arg,$param->get_mtype());
	}
	
	/**
	 * Checks whether $arg is ok for $param, assumes that $param is a callable and $arg is known.
	 *
	 * @param PC_Obj_Location $loc the location
	 * @param PC_Obj_MultiType $arg the argument
	 */
	private function check_callable($loc,$arg)
	{
		$first = $arg->get_first();
		if($first->get_type() == PC_Obj_Type::STRING)
		{
			if($first->is_val_unknown())
				return;
			
			$func = $this->env->get_types()->get_function($first->get_value());
			if($func === null)
			{
				$this->report(
					$loc,
					'The function "'.$first->get_value().'" does not exist!',
					PC_Obj_Error::E_S_FUNCTION_MISSING
				);
			}
		}
		else if($first->get_type() == PC_Obj_Type::TARRAY)
		{
			if($first->is_val_unknown())
				return;
			
			$callable = $first->get_value();
			if(count($callable) != 2 ||
				(!$callable[0]->is_unknown() && $callable[0]->get_first()->get_type() != PC_Obj_Type::OBJECT) ||
				(!$callable[1]->is_unknown() && $callable[1]->get_first()->get_type() != PC_Obj_Type::STRING))
			{
				$this->report(
					$loc,
					'Invalid callable: '.FWS_Printer::to_string($first).'!',
					PC_Obj_Error::E_S_CALLABLE_INVALID
				);
			}
			else
			{
				if($callable[0]->is_unknown() || $callable[1]->is_unknown())
					return;
				
				$obj = $callable[0]->get_first();
				$name = $callable[1]->get_first();
				
				$classname = $obj->get_class();
				$class = $this->env->get_types()->get_class($classname);
				if(!$class)
				{
					$this->report(
						$loc,
						'The class "#'.$classname.'#" does not exist!',
						PC_Obj_Error::E_S_CLASS_MISSING
					);
				}
				else if(!$class->contains_method($name->get_value()))
				{
					$this->report(
						$loc,
						'The method "'.$name->get_value().'" does not exist in the class "#'.$classname.'#"!',
						PC_Obj_Error::E_S_METHOD_MISSING
					);
				}
			}
		}
		else if($first->get_type() != PC_Obj_Type::TCALLABLE)
		{
			$this->report(
				$loc,
				'Invalid callable: '.FWS_Printer::to_string($first).'!',
				PC_Obj_Error::E_S_CALLABLE_INVALID
			);
		}
	}
	
	/**
	 * Checks whether the method with given name may be a method of a subclass of $class
	 *
	 * @param PC_Obj_Class $class the class
	 * @param string $name the method-name
	 * @return bool true if so
	 */
	private function is_method_of_sub($class,$name)
	{
		if($class->is_final())
			return false;
		
		$cname = $class->get_name();
		$isif = $class->is_interface();
		foreach($this->env->get_types()->get_classes() as $sub)
		{
			if($sub && ((!$isif && strcasecmp($sub->get_super_class(),$cname) == 0) ||
				($isif && $sub->is_implementing($cname))))
			{
				if($sub->contains_method($name))
					return true;
				if($this->is_method_of_sub($sub,$name))
					return true;
			}
		}
		return false;
	}
	
	/**
	 * Builds a link for the given call
	 *
	 * @param PC_Obj_Call $call the call
	 * @return string the link
	 */
	private function get_call_link($call)
	{
		$str = '';
		if($call->get_class())
		{
			$str .= '#'.$call->get_class().'#';
			if($call->is_static())
				$str .= '::';
			else
				$str .= '->';
		}
		$str .= $call->get_function().'('.implode(', ',$call->get_arguments()).')';
		return $str;
	}
	
	/**
	 * Determines the article for the given type ('a' or 'an'), depending on the first char
	 * 
	 * @param PC_Obj_MultiType $type the type
	 * @return string the article
	 */
	private function get_article($type)
	{
		$str = $type === null ? 'x' : $type->__ToString();
		return isset($str[0]) && in_array(strtolower($str[0]),array('i','a','o','u','e')) ? 'an' : 'a';
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
