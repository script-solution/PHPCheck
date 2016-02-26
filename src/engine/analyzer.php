<?php
/**
 * Contains the analyzer-class
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
 * Analyzes a given set of classes, functions, calls and so on and stores possible errors.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_Analyzer extends FWS_Object
{
	/**
	 * The container for all errors / warnings
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * The options
	 *
	 * @var PC_Engine_Options
	 */
	private $options;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Options $options the options
	 */
	public function __construct($options)
	{
		if(!($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		
		$this->options = $options;
	}
	
	/**
	 * @return array all found errors
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * Checks wether the method with given name may be a method of a subclass of $class
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Class $class the class
	 * @param string $name the method-name
	 * @return bool true if so
	 */
	private function is_method_of_sub($types,$class,$name)
	{
		if($class->is_final())
			return false;
		$cname = $class->get_name();
		$isif = $class->is_interface();
		foreach($types->get_classes() as $sub)
		{
			if($sub && ((!$isif && $sub->get_super_class() == $cname) ||
				($isif && in_array($cname,$sub->get_interfaces()))))
			{
				if($sub->contains_method($name))
					return true;
				if($this->is_method_of_sub($types,$sub,$name))
					return true;
			}
		}
		return false;
	}
	
	/**
	 * Analyzes the given function-/method-calls
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param array $calls an array with function-/method-calls
	 */
	public function analyze_calls($types,$calls)
	{
		foreach($calls as $call)
		{
			if($call->get_class() !== null)
				$this->analyze_method_call($types,$call);
			else
				$this->analyze_func_call($types,$call);
		}
	}
	
	/**
	 * Analyzes the given method call
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Call $call the call
	 */
	private function analyze_method_call($types,$call)
	{
		$name = $call->get_function();
		$classname = $call->get_class();
		$c = $types->get_class($classname);
		if($c !== null)
		{
			if(!$c->contains_method($name))
			{
				// no obj-creation here because the constructor can be named '__construct' or
				// '<classname>'. the call uses always '__construct'.
				if(!$call->is_object_creation() && !$this->is_method_of_sub($types,$c,$name))
				{
					$this->_report(
						$call,
						'The method "'.$name.'" does not exist in the class "#'.$classname.'#"!',
						PC_Obj_Error::E_A_METHOD_MISSING
					);
				}
			}
			else if($call->is_object_creation() && $c->is_abstract())
			{
				$this->_report(
					$call,
					'You can\'t instantiate the abstract class "#'.$c->get_name().'#"!',
					PC_Obj_Error::E_A_ABSTRACT_CLASS_INSTANTIATION
				);
			}
			else
			{
				// check for static / not static
				$m = $c->get_method($name);
				if($call->is_static() && !$m->is_static())
				{
					$this->_report(
						$call,
						'Your call "'.$this->_get_call_link($call).'" calls "'.$m->get_name()
							.'" statically, but the method is not static!',
						PC_Obj_Error::E_A_STATIC_CALL
					);
				}
				else if(!$call->is_static() && $m->is_static())
				{
					$this->_report(
						$call,
						'Your call "'.$this->_get_call_link($call).'" calls "'.$m->get_name()
							.'" not statically, but the method is static!',
						PC_Obj_Error::E_A_NONSTATIC_CALL
					);
				}
				
				$this->_check_params($types,$call,$m);
			}
		}
		else if($this->options->get_report_unknown() && $classname == PC_Obj_Class::UNKNOWN)
		{
			$this->_report(
				$call,
				'The class of the object in the call "'.$this->_get_call_link($call).'" is unknown!',
				PC_Obj_Error::E_A_CLASS_UNKNOWN
			);
		}
		else
		{
			$this->_report(
				$call,
				'The class "#'.$classname.'#" does not exist!',
				PC_Obj_Error::E_A_CLASS_MISSING
			);
		}
	}
	
	/**
	 * Analyzes the given function call
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Call $call the call
	 */
	private function analyze_func_call($types,$call)
	{
		$name = $call->get_function();
		$func = $types->get_function($name);
		if($func === null)
		{
			$this->_report(
				$call,
				'The function "'.$name.'" does not exist!',
				PC_Obj_Error::E_A_FUNCTION_MISSING
			);
		}
		else
			$this->_check_params($types,$call,$func);
	}
	
	/**
	 * Analyzes the given classes
	 * 
	 * @param PC_Engine_TypeContainer $types the types
	 * @param array $classes an array with classes (name as key)
	 */
	public function analyze_classes($types,$classes)
	{
		// check classes for issues
		foreach($classes as $class)
		{
			/* @var $class PC_Obj_Class */
			
			// test wether abstract is used in a usefull way
			$abstractcount = 0;
			foreach($class->get_methods() as $method)
			{
				if($method->is_abstract())
					$abstractcount++;
			}
			
			if(!$class->is_abstract() && $abstractcount > 0)
			{
				$this->_report(
					$class,
					'The class "#'.$class->get_name().'#" is NOT abstract but'
						.' contains abstract methods!',
					PC_Obj_Error::E_A_CLASS_NOT_ABSTRACT
				);
			}
			
			// check super-class
			if($class->get_super_class())
			{
				// do we know the class?
				$sclass = $types->get_class($class->get_super_class());
				
				if($sclass === null)
				{
					$this->_report(
						$class,
						'The class "#'.$class->get_super_class().'#" does not exist!',
						PC_Obj_Error::E_A_CLASS_MISSING
					);
				}
				// super-class final?
				else if($sclass->is_final())
				{
					$this->_report(
						$class,
						'The class "#'.$class->get_name().'#" inherits from the final '
							.'class "#'.$sclass->get_name().'#"!',
						PC_Obj_Error::E_A_FINAL_CLASS_INHERITANCE
					);
				}
			}
			
			// check implemented interfaces
			foreach($class->get_interfaces() as $ifname)
			{
				$if = $types->get_class($ifname);
				if($if === null)
				{
					$this->_report(
						$class,
						'The interface "#'.$ifname.'#" does not exist!',
						PC_Obj_Error::E_A_INTERFACE_MISSING
					);
				}
				else if(!$if->is_interface())
				{
					$this->_report(
						$class,
						'"#'.$ifname.'#" is no interface, but implemented by class #'.$class->get_name().'#!',
						PC_Obj_Error::E_A_IF_IS_NO_IF
					);
				}
			}
		}
	}

	/**
	 * Checks the parameters of the call against the given ones
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Call $call the call
	 * @param PC_Obj_Method $method the method
	 */
	private function _check_params($types,$call,$method)
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
			$this->_report(
				$call,
				'The function/method called by "'.$this->_get_call_link($call).'" requires '.$reqparams
					.' arguments but you have given '.count($arguments),
				PC_Obj_Error::E_A_WRONG_ARGUMENT_COUNT
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
				if(!$this->options->get_report_unknown() &&
					($arg === null || $arg->is_unknown() || $param->get_mtype()->is_unknown()))
				{
					$i++;
					continue;
				}
				
				if(!$this->_is_argument_ok($types,$call,$arg,$param))
				{
					$trequired = $param->get_mtype();
					$tactual = $arg;
					$this->_report(
						$call,
						'The argument '.($i + 1).' in "'.$this->_get_call_link($call).'" requires '
							.$this->_get_article($trequired).' "'.$trequired.'" but you have given '
							.$this->_get_article($tactual).' "'.($tactual === null ? "<i>NULL</i>" : $tactual).'"',
						PC_Obj_Error::E_A_WRONG_ARGUMENT_TYPE
					);
				}
				$i++;
			}
		}
	}
	
	/**
	 * Checks whether $arg is ok for $param
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Location $loc the location
	 * @param PC_Obj_MultiType $arg the argument
	 * @param PC_Obj_Parameter $param the parameter
	 * @return boolean true if so
	 */
	private function _is_argument_ok($types,$loc,$arg,$param)
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
				$this->_check_callable($types,$loc,$arg);
				return true;
			}
		}
		
		// arg in the allowed types?
		foreach($arg->get_types() as $type)
		{
			// every int can be converted to float
			if($type->get_type() == PC_Obj_Type::INT &&
					$param->get_mtype()->contains(new PC_Obj_Type(PC_Obj_Type::FLOAT)))
				return true;
			if($param->get_mtype()->contains($type))
				return true;
		}
		return false;
	}
	
	/**
	 * Checks whether $arg is ok for $param, assumes that $param is a callable and $arg is known.
	 *
	 * @param PC_Engine_TypeContainer $types the types
	 * @param PC_Obj_Location $loc the location
	 * @param PC_Obj_MultiType $arg the argument
	 */
	private function _check_callable($types,$loc,$arg)
	{
		$first = $arg->get_first();
		if($first->get_type() == PC_Obj_Type::STRING)
		{
			if(!$this->options->get_report_unknown() && $first->is_val_unknown())
				return;
			
			$func = $types->get_function($first->get_value());
			if($func === null)
			{
				$this->_report(
					$loc,
					'The function "'.$first->get_value().'" does not exist!',
					PC_Obj_Error::E_A_FUNCTION_MISSING
				);
			}
		}
		else if($first->get_type() == PC_Obj_Type::TARRAY)
		{
			if(!$this->options->get_report_unknown() && $first->is_val_unknown())
				return;
			
			$callable = $first->get_value();
			if(count($callable) != 2 ||
				(!$callable[0]->is_unknown() && $callable[0]->get_first()->get_type() != PC_Obj_Type::OBJECT) ||
				(!$callable[1]->is_unknown() && $callable[1]->get_first()->get_type() != PC_Obj_Type::STRING))
			{
				$this->_report(
					$loc,
					'Invalid callable: '.FWS_Printer::to_string($first).'!',
					PC_Obj_Error::E_A_CALLABLE_INVALID
				);
			}
			else
			{
				if(!$this->options->get_report_unknown() && ($callable[0]->is_unknown() ||
					 $callable[1]->is_unknown()))
					return;
				
				$obj = $callable[0]->get_first();
				$name = $callable[1]->get_first();
				
				$classname = $obj->get_class();
				$class = $types->get_class($classname);
				if(!$class)
				{
					$this->_report(
						$loc,
						'The class "#'.$classname.'#" does not exist!',
						PC_Obj_Error::E_A_CLASS_MISSING
					);
				}
				else if(!$class->contains_method($name->get_value()))
				{
					$this->_report(
						$loc,
						'The method "'.$name->get_value().'" does not exist in the class "#'.$classname.'#"!',
						PC_Obj_Error::E_A_METHOD_MISSING
					);
				}
			}
		}
		else if($first->get_type() != PC_Obj_Type::TCALLABLE)
		{
			$this->_report(
				$loc,
				'Invalid callable: '.FWS_Printer::to_string($first).'!',
				PC_Obj_Error::E_A_CALLABLE_INVALID
			);
		}
	}
	
	/**
	 * Builds a link for the given call
	 *
	 * @param PC_Obj_Call $call the call
	 * @return string the link
	 */
	private function _get_call_link($call)
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
	 * Reports the given error
	 *
	 * @param PC_Obj_Location $loc the location
	 * @param string $msg the message to display
	 * @param int $type the error-type
	 */
	private function _report($loc,$msg,$type)
	{
		$this->errors[] = new PC_Obj_Error($loc,$msg,$type);
	}
	
	/**
	 * Determines the article for the given type ('a' or 'an'), depending on the first char
	 * 
	 * @param PC_Obj_MultiType $type the type
	 * @return string the article
	 */
	private function _get_article($type)
	{
		$str = $type === null ? 'x' : $type->__ToString();
		return isset($str[0]) && in_array(strtolower($str[0]),array('i','a','o','u','e')) ? 'an' : 'a';
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
