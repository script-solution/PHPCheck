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

define('REPORT_MIXED',true);
define('REPORT_UNKNOWN',false);

/**
 * Analyzes a given set of classes, functions, calls and so on and stores possible errors.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Compile_Analyzer extends FWS_Object
{
	/**
	 * Wether errors with mixed types involved should be reported
	 * 
	 * @var boolean
	 */
	private $report_mixed;
	/**
	 * Wether errors with unknown types involved should be reported
	 * 
	 * @var boolean
	 */
	private $report_unknown;
	/**
	 * The container for all errors / warnings
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * Constructor
	 * 
	 * @param $report_mixed boolean wether errors with mixed types involved should be reported
	 * @param $report_unknown boolean wether errors with unknown types involved should be reported
	 */
	public function __construct($report_mixed = false,$report_unknown = false)
	{
		$this->report_mixed = $report_mixed;
		$this->report_unknown = $report_unknown;
	}
	
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
	 * @param PC_Compile_TypeContainer $types the types
	 * @param array $calls an array with function-/method-calls
	 */
	public function analyze_calls($types,$calls)
	{
		foreach($calls as $call)
		{
			/* @var $call PC_Obj_Call */
			$name = $call->get_function();
			$classname = $call->get_class();
			if($classname !== null)
			{
				if($classname)
				{
					$c = $types->get_class($classname);
					if($c !== null)
					{
						if(!$c->contains_method($name))
						{
							$this->_report(
								$call,
								'The method "'.$name.'" does not exist in the class "#'.$classname.'#"!',
								PC_Obj_Error::E_S_METHOD_MISSING
							);
						}
						else if($call->is_object_creation() && $c->is_abstract())
						{
							$this->_report(
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
								$this->_report(
									$call,
									'Your call "'.$this->_get_call_link($call).'" calls "'.$m->get_name()
										.'" statically, but the method is not static!',
									PC_Obj_Error::E_S_STATIC_CALL
								);
							}
							else if(!$call->is_static() && $m->is_static())
							{
								$this->_report(
									$call,
									'Your call "'.$this->_get_call_link($call).'" calls "'.$m->get_name()
										.'" not statically, but the method is static!',
									PC_Obj_Error::E_S_NONSTATIC_CALL
								);
							}
							
							$this->_check_params($call,$m);
						}
					}
					// check wether it's a buildin-php-class
					else if($classname != PC_Obj_Class::UNKNOWN && !class_exists($classname,false))
					{
						$this->_report(
							$call,
							'The class "#'.$classname.'#" does not exist!',
							PC_Obj_Error::E_S_CLASS_MISSING
						);
					}
					else if($this->report_unknown && $classname == PC_Obj_Class::UNKNOWN)
					{
						$this->_report(
							$call,
							'The class of the object in the call "'.$this->_get_call_link($call).'" is unknown!',
							PC_Obj_Error::E_S_CLASS_UNKNOWN
						);
					}
				}
			}
			else
			{
				$func = $types->get_function($name);
				if($func === null)
				{
					if($types->is_db_used())
					{
						// check if its a builtin function we know
						$func = PC_DAO::get_functions()->get_by_name($name,PC_Project::PHPREF_ID);
						if($func === null)
						{
							$this->_report(
								$call,
								'The function "'.$name.'" does not exist!',
								PC_Obj_Error::E_S_FUNCTION_MISSING
							);
						}
						// if so, check params
						else
							$this->_check_params($call,$func);
					}
					// if we should use no db, check at least if the function exists currently */
					else if(!function_exists($name))
					{
						$this->_report(
							$call,
							'The function "'.$name.'" does not exist!',
							PC_Obj_Error::E_S_FUNCTION_MISSING
						);
					}
				}
				else
					$this->_check_params($call,$func);
			}
		}
	}
	
	/**
	 * Analyzes the given classes
	 * 
	 * @param PC_Compile_TypeContainer $types the types
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
			
			if($class->is_abstract() && $abstractcount == 0)
			{
				$this->_report(
					$class,
					'The class "#'.$class->get_name().'#" is abstract but'
						.' has no abstract method!',
					PC_Obj_Error::E_T_CLASS_POT_USELESS_ABSTRACT
				);
			}
			else if(!$class->is_abstract() && $abstractcount > 0)
			{
				$this->_report(
					$class,
					'The class "#'.$class->get_name().'#" is NOT abstract but'
						.' contains abstract methods!',
					PC_Obj_Error::E_T_CLASS_NOT_ABSTRACT
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
						PC_Obj_Error::E_T_CLASS_MISSING
					);
				}
				// super-class final?
				else if($sclass !== null && $sclass->is_final())
				{
					$this->_report(
						$class,
						'The class "#'.$class->get_name().'#" inherits from the final '
							.'class "#'.$sclass->get_name().'#"!',
						PC_Obj_Error::E_T_FINAL_CLASS_INHERITANCE
					);
				}
			}
		}
	}

	/**
	 * Checks the parameters of the call against the given ones
	 *
	 * @param PC_Obj_Call $call the call
	 * @param PC_Obj_Method $method the method
	 */
	private function _check_params($call,$method)
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
				PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT
			);
		}
		else
		{
			$tmixed = new PC_Obj_Type(PC_Obj_Type::OBJECT,null,'mixed');
			$tunknown = new PC_Obj_Type(PC_Obj_Type::UNKNOWN);
			$i = 0;
			foreach($method->get_params() as $param)
			{
				/* @var $param PC_Obj_Parameter */
				$arg = isset($arguments[$i]) ? $arguments[$i] : null;
				if($this->report_mixed || (!$param->get_mtype()->contains($tmixed) &&
					($arg === null || !$arg->equals($tmixed))))
				{
					if($this->report_unknown || $arg === null || !$arg->equals($tunknown))
					{
						if(!$this->_is_argument_ok($arg,$param))
						{
							$trequired = $param->get_mtype();
							$tactual = $arg;
							$this->_report(
								$call,
								'The argument '.($i + 1).' in "'.$this->_get_call_link($call).'" requires '
									.$this->_get_article($trequired).' "'.$trequired.'" but you have given '
									.$this->_get_article($tactual).' "'.($tactual === null ? "<i>NULL</i>" : $tactual).'"',
								PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE
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
	 * @param PC_Obj_Type $arg the argument
	 * @param PC_Obj_Parameter $param the parameter
	 * @return boolean true if so
	 */
	private function _is_argument_ok($arg,$param)
	{
		// not present but required?
		if($arg === null && !$param->is_optional() && !$param->is_first_vararg())
			return false;
		
		// unknown / not present
		if($arg === null)
			return true;
		
		// arg in the allowed types?
		if($param->get_mtype()->contains($arg))
			return true;
		
		// every int can be converted to float
		if($arg->get_type() == PC_Obj_Type::INT &&
				$param->get_mtype()->contains(new PC_Obj_Type(PC_Obj_Type::FLOAT)))
			return true;
		
		return false;
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
	 * @param PC_Obj_Location $loc the location
	 * @param string $msg the message to display
	 * @param int $type the error-type
	 */
	private function _report($loc,$msg,$type)
	{
		$this->errors[] = new PC_Obj_Error($loc,$msg,$type);
	}
	
	private function _get_article($type)
	{
		$str = $type === null ? 'x' : $type->__ToString();
		return isset($str[0]) && in_array($str[0],array('i','a','o','u','e')) ? 'an' : 'a';
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>