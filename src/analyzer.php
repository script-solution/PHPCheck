<?php
/**
 * Contains the analyzer-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

define('REPORT_MIXED',false);
define('REPORT_UNKNOWN',false);

/**
 * Analyzes a given set of classes, functions, calls and so on and stores possible errors.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Analyzer extends FWS_Object
{
	/**
	 * The container for all errors / warnings
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * @return array all found errors
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * Analyzes the given function-/method-calls
	 *
	 * @param PC_TypeContainer $types the types
	 * @param array $calls an array with function-/method-calls
	 */
	public function analyze_calls($types,$calls)
	{
		foreach($calls as $call)
		{
			/* @var $call PC_Call */
			$name = $call->get_function();
			$classname = $call->get_class();
			if($classname !== null)
			{
				if($classname)
				{
					/*if($obj[0] == '$' && isset($vars[$obj]))
						$classname = $vars[$obj];
					else
						$classname = $obj;*/
					
					$c = $types->get_class($classname);
					if($c !== null)
					{
						if(!$c->contains_method($name))
						{
							$this->_report(
								$call,
								'The method "'.$name.'" does not exist in the class "#'.$classname.'#"!',
								PC_Error::E_S_METHOD_MISSING
							);
						}
						else if($call->is_object_creation() && $c->is_abstract())
						{
							$this->_report(
								$call,
								'You can\'t instantiate the abstract class "#'.$c->get_name().'#"!',
								PC_Error::E_S_ABSTRACT_CLASS_INSTANTIATION
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
									PC_Error::E_S_STATIC_CALL
								);
							}
							else if(!$call->is_static() && $m->is_static())
							{
								$this->_report(
									$call,
									'Your call "'.$this->_get_call_link($call).'" calls "'.$m->get_name()
										.'" not statically, but the method is static!',
									PC_Error::E_S_NONSTATIC_CALL
								);
							}
							
							$this->_check_params($call,$m);
						}
					}
					// check wether it's a buildin-php-class
					else if($classname != PC_Class::UNKNOWN && !class_exists($classname,false))
					{
						$this->_report(
							$call,
							'The class "#'.$classname.'#" does not exist!',
							PC_Error::E_S_CLASS_MISSING
						);
					}
					else if(REPORT_UNKNOWN && $classname == PC_Class::UNKNOWN)
					{
						$this->_report(
							$call,
							'The class of the object in the call "'.$this->_get_call_link($call).'" is unknown!',
							PC_Error::E_S_CLASS_UNKNOWN
						);
					}
				}
			}
			else
			{
				$func = $types->get_function($name);
				// check wether it's either a user-defined and known function or a php-buildin-function
				if($func === null && !function_exists($name))
				{
					$this->_report(
						$call,
						'The function "'.$name.'" does not exist!',
						PC_Error::E_S_FUNCTION_MISSING
					);
				}
				else if($func !== null)
					$this->_check_params($call,$func);
			}
		}
	}
	
	/**
	 * Analyzes the given classes
	 * 
	 * @param PC_TypeContainer $types the types
	 * @param array $classes an array with classes (name as key)
	 */
	public function analyze_classes($types,$classes)
	{
		// check classes for issues
		foreach($classes as $class)
		{
			/* @var $class PC_Class */
			
			// test wether abstract is used in a usefull way
			$abstractcount = 0;
			foreach($class->get_methods() as $method)
			{
				if($method->is_abstract())
					$abstractcount++;
			}
			
			if($class->is_abstract() && $abstractcount == 0)
			{
				$this->_report(
					$class,
					'The class "#'.$class->get_name().'#" is abstract but'
						.' has no abstract method!',
					PC_Error::E_T_CLASS_POT_USELESS_ABSTRACT
				);
			}
			else if(!$class->is_abstract() && $abstractcount > 0)
			{
				$this->_report(
					$class,
					'The class "#'.$class->get_name().'#" is NOT abstract but'
						.' contains abstract methods!',
					PC_Error::E_T_CLASS_NOT_ABSTRACT
				);
			}
			
			// check super-class
			if($class->get_super_class() !== null)
			{
				// do we know the class?
				$sclass = $types->get_class($class->get_super_class());
				
				// check for buildin-php-classes, too
				if($sclass === null && !class_exists($class->get_super_class(),false))
				{
					$this->_report(
						$class,
						'The class "#'.$class->get_super_class().'#" does not exist!',
						PC_Error::E_T_CLASS_MISSING
					);
				}
				// super-class final?
				else if($sclass !== null && $sclass->is_final())
				{
					$this->_report(
						$class,
						'The class "#'.$class->get_name().'#" inherits from the final '
							.'class "#'.$sclass->get_name().'#"!',
						PC_Error::E_T_FINAL_CLASS_INHERITANCE
					);
				}
			}
		}
	}

	/**
	 * Checks the parameters of the call against the given ones
	 *
	 * @param PC_Call $call the call
	 * @param PC_Method $method the method
	 */
	private function _check_params($call,$method)
	{
		$arguments = $call->get_arguments();
		$nparams = $method->get_required_param_count();
		$nmaxparams = $method->get_param_count();
		if(count($arguments) < $nparams || count($arguments) > $nmaxparams)
		{
			$reqparams = $nparams != $nmaxparams ? $nparams.' to '.$nmaxparams : $nparams;
			$this->_report(
				$call,
				'The function/method called by "'.$this->_get_call_link($call).'" requires '.$reqparams
					.' arguments but you have given '.count($arguments),
				PC_Error::E_S_WRONG_ARGUMENT_COUNT
			);
		}
		else
		{
			$tmixed = new PC_Type(PC_Type::OBJECT,null,'mixed');
			$tunknown = new PC_Type(PC_Type::UNKNOWN);
			$i = 0;
			foreach($method->get_params() as $param)
			{
				/* @var $param PC_Parameter */
				$arg = isset($arguments[$i]) ? $arguments[$i] : null;
				if(REPORT_MIXED || (!$param->get_mtype()->contains($tmixed) &&
					($arg === null || !$arg->equals($tmixed))))
				{
					if(REPORT_UNKNOWN || $arg === null || !$arg->equals($tunknown))
					{
						if(!$this->_is_argument_ok($arg,$param))
						{
							$trequired = $param->get_mtype();
							$tactual = $arg;
							$this->_report(
								$call,
								'The argument '.($i + 1).' in "'.$this->_get_call_link($call).'" requires '
									.$this->_get_article($trequired).' "'.$trequired.'" but you have given '
									.$this->_get_article($tactual).' "'.$tactual.'"',
								PC_Error::E_S_WRONG_ARGUMENT_TYPE
							);
						}
					}
				}
				$i++;
			}
		}
	}
	
	/**
	 * Checks wether $arg is ok for $param
	 *
	 * @param PC_Type $arg the argument
	 * @param PC_Parameter $param the parameter
	 * @return boolean true if so
	 */
	private function _is_argument_ok($arg,$param)
	{
		// not present but required?
		if($arg === null && !$param->is_optional())
			return false;
		
		// unknown / not present
		if($arg === null)
			return true;
		
		// arg in the allowed types?
		if($param->get_mtype()->contains($arg))
			return true;
		
		// every int can be converted to float
		if($arg->get_type() == PC_Type::INT && $param->get_mtype()->contains(new PC_Type(PC_Type::FLOAT)))
			return true;
		
		return false;
	}
	
	/**
	 * Builds a link for the given call
	 *
	 * @param PC_Call $call the call
	 * @return string the link
	 */
	private function _get_call_link($call)
	{
		$str = '';
		if($call->get_class())
		{
			$url = new FWS_URL();
			$url->set('module','class');
			$url->set('name',$call->get_class());
			$str .= '<a href="'.$url->to_url().'">'.$call->get_class().'</a>';
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
	 * @param PC_Location $loc the location
	 * @param string $msg the message to display
	 * @param int $type the error-type
	 */
	private function _report($loc,$msg,$type)
	{
		$this->errors[] = new PC_Error($loc,$msg,$type);
	}
	
	/*private function _parameter_type_warning($i,$call,$tactual,$trequired)
	{
		warning('The argument '.$i.' in "'.$call->get_call().'" requires '.get_article($tactual)
						.' "'.$tactual.'" but you have given '.get_article($trequired).' "'.$trequired.'"',$call);
	}*/
	
	private function _get_article($type)
	{
		$str = $type === null ? 'x' : $type->__ToString();
		return isset($str[0]) && in_array($str[0],array('i','a','o','u','e')) ? 'an' : 'a';
	}/*
	
	private function _error($msg,$loc)
	{
		echo '['.$loc->get_file().', '.$loc->get_line().'] <b>Error:</b> '.$msg.'<br />';
	}
	
	private function _warning($msg,$loc)
	{
		echo '['.$loc->get_file().', '.$loc->get_line().'] <b>Warning:</b> '.$msg.'<br />';
	}*/
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>