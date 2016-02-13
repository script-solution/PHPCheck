<?php
/**
 * Contains the statement-lexer
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
 * Uses the tokens from token_get_all() and walks over the tokens. It stores various information
 * about the current state
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_StmtScanner extends PC_Engine_BaseScanner
{
	/**
	 * @param string $file the filename
	 * @param PC_Engine_TypeContainer $types the type-container
	 * @return PC_Engine_StmtScanner the instance for lexing a file
	 */
	public static function get_for_file($file,$types)
	{
		return new self($file,true,$types);
	}
	
	/**
	 * @param string $string the string
	 * @param PC_Engine_TypeContainer $types the type-container
	 * @return PC_Engine_StmtScanner the instance for lexing a string
	 */
	public static function get_for_string($string,$types)
	{
		return new self($string,false,$types);
	}
	
	/**
	 * The last comment we've checked for "@var $<name> <type>"
	 * 
	 * @var string
	 */
	private $lastCheckComment = '';
	/**
	 * Wether we should ignore the next while-token. This is used for "do ... while" since in that
	 * case while is the end of a loop, not the beginning.
	 * 
	 * @var bool
	 */
	private $ignoreNextWhile = false;
	/**
	 * Wether the last (usefull) token was an T_ELSE
	 * 
	 * @var bool
	 */
	private $lastWasElse = false;
	/**
	 * The current scope
	 * 
	 * @var PC_Engine_Scope
	 */
	private $scope;
	
	/**
	 * The known types
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	/**
	 * The variables
	 * 
	 * @var PC_Engine_VarContainer
	 */
	private $vars;
	/**
	 * An array of all return-types of the current function/method
	 * 
	 * @var array
	 */
	private $allrettypes = array();
	
	/**
	 * The next id for anonymous functions
	 *
	 * @var int
	 */
	private $anon_id = 1;
	
	/**
	 * Constructor
	 * 
	 * @param string $str the file or string
	 * @param bool $is_file wether $str is a file
	 * @param PC_Engine_TypeContainer $types the type-container
	 */
	protected function __construct($str,$is_file,$types)
	{
		parent::__construct($str,$is_file);
		
		$this->types = $types;
		$this->scope = new PC_Engine_Scope();
		$this->vars = new PC_Engine_VarContainer();
	}
	
	/**
	 * @return PC_Engine_VarContainer the variable-container
	 */
	public function get_vars()
	{
		return $this->vars;
	}
	
	/**
	 * Adds a function-call
	 * 
	 * @param PC_Obj_Variable $class the class-name
	 * @param PC_Obj_Variable $func the function-name
	 * @param array $args the function-arguments
	 * @param bool $static wether its a static call
	 * @return PC_Obj_Variable the result
	 */
	public function add_call($class,$func,$args,$static = false)
	{
		// if we don't know the function- or class-name, we can't do anything here
		$cname = $class !== null ? $class->get_type()->get_string() : null;
		$fname = $func->get_type()->get_string();
		if($fname === null || ($class !== null && $cname === null))
			return $this->get_unknown();
		
		// create call
		$call = new PC_Obj_Call($this->get_file(),$this->get_line());
		
		// determine class- and function-name
		$call->set_function($fname);
		$call->set_object_creation(strcasecmp($fname,'__construct') == 0);
		if($class !== null)
		{
			// support for php4 constructors
			if(strcasecmp($cname,$fname) == 0)
				$call->set_object_creation(true);
			else if(strcasecmp($cname,'parent') == 0)
			{
				// in this case its no object-creation for us because it would lead to reports like
				// instantiation of abstract classes when calling the constructor of an abstract
				// parent-class
				$call->set_object_creation(false);
				$cname = $this->scope->get_name_of(T_CLASS_C);
				$classobj = $this->types->get_class($cname);
				if($classobj === null || $classobj->get_super_class() == '')
					return $this->get_unknown();
				$cname = $classobj->get_super_class();
				// if we're in a static method, the call is always static
				$curfname = $this->scope->get_name_of(T_FUNC_C);
				$curfunc = $classobj->get_method($curfname);
				if($curfunc === null || $curfunc->is_static())
					$static = true;
				else
				{
					// if we're in a non-static method, its a static-call if the method we're calling is
					// static, and not if its not. i.e. we basically don't detect errors here
					$func = null;
					$super = $this->types->get_class($cname);
					if($super !== null)
						$func = $super->get_method($fname);
					$static = $func !== null && $func->is_static();
				}
			}
			else if(strcasecmp($cname,'self') == 0)
			{
				$cname = $this->scope->get_name_of(T_CLASS_C);
				// self is static if its not a constructor-call
				$static = strcasecmp($fname,'__construct') != 0 && strcasecmp($fname,$cname) != 0;
			}
			$call->set_class($cname);
		}
		
		// clone because it might be a variable
		foreach($args as $arg)
		{
			$this->check_known($arg);
			$call->add_argument(clone $arg->get_type());
		}
		$call->set_static($static);
		$this->types->add_call($call);
		
		// if its a constructor we know the type directly
		if(strcasecmp($fname,'__construct') == 0 || strcasecmp($fname,$cname) == 0)
			return PC_Obj_Variable::create_object($cname);
		
		// get function-object and determine return-type
		$funcobj = $this->get_method_object($cname,$fname);
		if($funcobj === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',clone $funcobj->get_return_type());
	}
	
	/**
	 * Handles access to an object-property through the given chain and for given object
	 * 
	 * @param PC_Obj_Variable $obj the object
	 * @param array $chain the chain
	 * @return PC_Obj_Variable the result
	 */
	public function handle_object_prop_chain($obj,$chain)
	{
		$objt = null;
		if($obj->get_name() == 'this')
		{
			$classname = $this->scope->get_name_of(T_CLASS_C);
			if($classname)
				$objt = PC_Obj_MultiType::create_object($classname);
		}
		else
		{
			$this->check_known($obj);
			$objt = $obj->get_type();
		}
		foreach($chain as $access)
		{
			// if we don't know the class-name or its no object, stop here
			$classname = $objt !== null ? $objt->get_classname() : null;
			if($classname === null)
				return $this->get_unknown();
			// if we don't know the class, give up, as well
			$class = $this->types->get_class($classname);
			if($class === null)
				return $this->get_unknown();
			
			$prop = $access['prop'];
			$args = $access['args'];
			assert(count($prop) > 0 && $prop[0]['type'] == 'name');
			if(count($prop) > 1 || $args === null)
			{
				$fieldvar = $prop[0]['data'];
				$fieldname = $fieldvar->get_type()->get_string();
				if($fieldname === null)
					return $this->get_unknown();
				$field = $class->get_field($fieldname);
				if($field === null)
				{
					$this->report_error(
						null,
						'Access of not-existing field "'.$fieldname.'" of class "#'.$classname.'#"',
						PC_Obj_Error::E_S_NOT_EXISTING_FIELD
					);
					return $this->get_unknown();
				}
				$res = $field->get_type();
				for($i = 1; $i < count($prop); $i++)
				{
					assert($prop[$i]['type'] == 'array');
					$offset = $prop[$i]['data'];
					// if the offset is null it means that we should append to the array. this is not supported
					// for class-fields. therefore stop here and return unknown
					if($offset === null || $res === null || $res->get_array() === null)
						return $this->get_unknown();
					$off = $offset->get_type()->get_scalar();
					if($off === null)
						return $this->get_unknown();
					$res = $res->get_first()->get_array_type($off);
				}
			}
			else
			{
				$mnamevar = $prop[0]['data'];
				$mname = $mnamevar->get_type()->get_string();
				if($mname === null)
					return $this->get_unknown();
				$this->add_call(PC_Obj_Variable::create_string($class->get_name()),$mnamevar,$args,false);
				$method = $class->get_method($mname);
				if($method === null)
					return $this->get_unknown();
				$res = clone $method->get_return_type();
			}
			$objt = $res;
		}
		if($res === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$res);
	}
	
	/**
	 * Returns the value of the given constant
	 * 
	 * @param string $name the constant-name
	 * @return PC_Obj_Variable the type
	 */
	public function get_constant_type($name)
	{
		$const = $this->types->get_constant($name);
		if($const === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$const->get_type());
	}
	
	/**
	 * Checks wether the given variable is known. If not, it reports an error
	 * 
	 * @param PC_Obj_Variable $var the variable
	 */
	private function check_known($var)
	{
		$name = $var->get_name();
		if($name && !$this->vars->exists($this->scope->get_name(),$name))
		{
			switch($name)
			{
				// ignore predefined variables
				case 'GLOBALS':
				case '_SERVER':
				case '_GET':
				case '_POST':
				case '_FILES':
				case '_REQUEST':
				case '_SESSION':
				case '_ENV':
				case '_COOKIE':
				case 'php_errormsg':
				case 'HTTP_RAW_POST_DATA':
				case 'http_response_header':
				case 'argc':
				case 'argv':
					break;
				
				default:
					$this->report_error(
						null,'The variable "$'.$name.'" is undefined',PC_Obj_Error::E_S_UNDEFINED_VAR
					);
					break;
			}
		}
	}
	
	/**
	 * Returns the value of the variable with given name in current scope. If it does not exist,
	 * a new one is created (but not stored in scope).
	 * 
	 * @param string $var the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public function get_var($var)
	{
		if($var == 'this')
			return PC_Obj_Variable::create_object($this->scope->get_name_of(T_CLASS_C));
		$scopename = $this->scope->get_name();
		if(!$this->vars->exists($scopename,$var))
			return $this->get_unknown($var);
		return $this->vars->get($scopename,$var);
	}
	
	/**
	 * Sets the given variable in the current scope to given value
	 * 
	 * @param PC_Obj_Variable $var the variable to set
	 * @param PC_Obj_Variable $value the value
	 * @param bool $isref wether to store a reference (default = false)
	 * @return PC_Obj_Variable the variable
	 */
	public function set_var($var,$value,$isref = false)
	{
		$varname = $var->get_name();
		$scopename = $this->scope->get_name();
		assert($value !== null);
		$this->vars->backup($var,$this->scope);
		if($isref)
			$var->set_type($value->get_type());
		else
			$var->set_type(clone $value->get_type());
		if($varname)
		{
			$var->set_function($this->scope->get_name_of(T_FUNC_C));
			$var->set_class($this->scope->get_name_of(T_CLASS_C));
			$this->vars->set($scopename,$var);
		}
		return $value;
	}
	
	/**
	 * Sets the given function-parameter for the current scope
	 * 
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_Variable $hint the type from type-hinting (may be null)
	 * @param PC_Obj_Variable $def the type from the default-value (may be null)
	 */
	public function set_func_param($var,$hint,$def)
	{
		// give type-hinting the highest prio, because I think its the most trustable type
		if($hint !== null)
		{
			$this->set_var($var,$hint);
			return;
		}
		
		// if a variable-type is unknown and we're in a function/class, check if we know the type
		// from the type-scanner
		$func = $this->get_method_object(
			$this->scope->get_name_of(T_CLASS_C),$this->scope->get_name_of(T_FUNC_C)
		);
		// if we have a doc, use it. otherwise use the default-value
		$doc = $this->get_funcparam_type($func,$var->get_name());
		if($doc !== null)
			$this->set_var($var,$doc);
		else
			$this->set_var($var,$def ? $def : new PC_Obj_Variable(''));
	}
	
	/**
	 * Determines the type of the given function-parameter
	 * 
	 * @param PC_Obj_Method $func the function/method
	 * @param string $varname the variable-name
	 * @return PC_Obj_Variable the type
	 */
	private function get_funcparam_type($func,$varname)
	{
		if($func === null)
			return null;
		// TODO remove the '$' in the type-scanner
		$param = $func->get_param('$'.$varname);
		if($param === null)
			return null;
		return new PC_Obj_Variable('',$param->get_mtype());
	}
	
	/**
	 * Sets $first and $sec for the current foreach-loop
	 * 
	 * @param PC_Obj_Variable $array the array over which we loop
	 * @param PC_Obj_Variable $first the first variable (key or value)
	 * @param PC_Obj_Variable $sec the second variable (value, if not null)
	 */
	public function set_foreach_var($array,$first,$sec)
	{
		$arrval = $array->get_type()->get_array();
		// if we don't know the array-values, it is no array or there are no elements, we don't
		// know the types of $first and $sec
		if($arrval === null || $array->get_type()->is_array_unknown() || count($arrval) == 0)
		{
			$this->set_var($first,$this->get_unknown());
			if($sec !== null)
				$this->set_var($sec,$this->get_unknown());
		}
		else
		{
			// check if all array-keys and array-values have the same type
			// we don't need to wait for keys if we don't need to define a key
			$kknown = $sec !== null;
			$vknown = true;
			// set to the types of the first
			reset($arrval);
			list($ktype,$vtype) = each($arrval);
			$ktype = gettype($ktype);
			while(list($nktype,$nvtype) = each($arrval))
			{
				// type different?
				if(gettype($nktype) != $ktype)
					$kknown = false;
				if(!$nvtype->equals($vtype))
					$vknown = false;
				// if we have found a different one for both, stop
				if(!$kknown && !$vknown)
					break;
			}
			
			// determine type for first and second var
			$sectype = null;
			if($sec === null)
			{
				$firsttype = $vknown ? clone $vtype : new PC_Obj_MultiType();
				$firsttype->clear_values();
			}
			else
			{
				$firsttype = $kknown ? PC_Obj_MultiType::get_type_by_name($ktype) : new PC_Obj_MultiType();
				$sectype = $vknown ? clone $vtype : new PC_Obj_MultiType();
				$sectype->clear_values();
			}
			
			// set vars; no clone here since we have a fresh object
			$this->set_var($first,new PC_Obj_Variable('',$firsttype),false);
			if($sectype !== null)
				$this->set_var($sec,new PC_Obj_Variable('',$sectype),false);
		}
	}
	
	/**
	 * Handles the list()-construct
	 * 
	 * @param array $list an array of PC_Variables to assign; may contain sub-arrays; contains null
	 * 	if an element should be ignored
	 * @param PC_Obj_Variable $expr the array to take the elements from
	 * @return PC_Obj_Variable the result ($expr)
	 */
	public function handle_list($list,$expr)
	{
		$this->handle_list_rek($list,$expr);
		return $expr;
	}
	
	/**
	 * The rekursive handler of list
	 * 
	 * @param array $list an array of PC_Variables to assign; may contain sub-arrays; contains null
	 * 	if an element should be ignored
	 * @param PC_Obj_Variable $expr the array to take the elements from
	 */
	private function handle_list_rek($list,$expr)
	{
		$array = $expr->get_type()->get_array();
		$lcount = count($list);
		$ecount = $array === null ? 0 : count($array);
		for($i = 0; $i < $lcount; $i++)
		{
			if($list[$i] === null)
				continue;
			$el = isset($array[$i]) ? new PC_Obj_Variable('',$array[$i]) : new PC_Obj_Variable('');
			if(is_array($list[$i]))
				$this->handle_list_rek($list[$i],$el);
			else
				$this->set_var($list[$i],$el);
		}
	}
	
	/**
	 * Adds the given return-statement
	 * 
	 * @param PC_Obj_Variable $expr the expression; null if its a "return;"
	 */
	public function add_return($expr)
	{
		$this->allrettypes[] = $expr;
	}
	
	/**
	 * Adds the given expression as thrown
	 * 
	 * @param PC_Obj_Variable $expr the expression
	 */
	public function add_throw($expr)
	{
		// TODO
		echo FWS_Printer::to_string(array(__FUNCTION__,$expr));
	}
	
	/**
	 * Puts the variable with given name from global scope into the current one
	 * 
	 * @param string $name the variable-name
	 */
	public function do_global($name)
	{
		if($this->vars->exists(PC_Obj_Variable::SCOPE_GLOBAL,$name))
			$val = $this->vars->get(PC_Obj_Variable::SCOPE_GLOBAL,$name);
		else
			$val = $this->get_unknown();
		$this->set_var($this->get_unknown($name),$val,true);
	}
	
	/**
	 * Starts the given class
	 */
	private function start_class($name)
	{
		$this->scope->enter_class($name);
	}

	/**
	 * Ends the current class
	 */
	public function end_class()
	{
		$this->scope->leave_class();
	}
	
	/**
	 * Starts the given function
	 */
	private function start_function($name)
	{
		$this->allrettypes = array();
		$this->scope->enter_function($name);
	}
	
	/**
	 * Ends the current function
	 */
	public function end_function()
	{
		$this->analyze_rettypes();
		$this->scope->leave_function();
	}
	
	/**
	 * Starts a loop
	 */
	private function start_loop()
	{
		$this->vars->enter_loop();
	}
	
	/**
	 * Ends a loop
	 */
	public function end_loop()
	{
		$this->vars->leave_loop($this->scope);
	}
	
	/**
	 * Starts a condition
	 * 
	 * @param bool $newblock wether a new block is opened in the current layer
	 * @param bool $is_else wether an T_ELSE opened this block
	 */
	private function start_cond($newblock = false,$is_else = false)
	{
		$this->vars->enter_cond($newblock,$is_else);
	}
	
	/**
	 * Ends a condition
	 */
	public function end_cond()
	{
		$this->vars->leave_cond($this->scope);
	}
	
	/**
	 * Analyzes the return-types of the current function and reports errors, if necessary
	 */
	private function analyze_rettypes()
	{
		$funcname = $this->scope->get_name_of(T_FUNC_C);
		$classname = $this->scope->get_name_of(T_CLASS_C);
		$func = $this->get_method_object($classname,$funcname);
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
			
			$name = ($classname ? '#'.$classname.'#::' : '').$funcname;
			// empty return-expression and non-empty?
			if($hasnull && $hasother)
			{
				$this->report_error(
					$func,
					'The function/method "'.$name.'" has return-'
					.'statements without expression and return-statements with expression',
					PC_Obj_Error::E_S_MIXED_RET_AND_NO_RET
				);
			}
			if($func->has_return_doc() && !$hasother)
			{
				$this->report_error(
					$func,
					'The function/method "'.$name.'" has a return-specification in PHPDoc'
					.', but does not return a value',
					PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET
				);
			}
			else if(!$func->has_return_doc() && !$func->is_anonymous() && $hasother)
			{
				$this->report_error(
					$func,
					'The function/method "'.$name.'" has no return-specification in PHPDoc'
					.', but does return a value',
					PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC
				);
			}
			else if($this->has_forbidden($this->allrettypes,$func->get_return_type()))
			{
				$merged = new PC_Obj_MultiType();
				foreach($this->allrettypes as $t)
				{
					if($t !== null)
						$merged->merge($t->get_type(),false);
				}
				$this->report_error(
					$func,
					'The return-specification (PHPDoc) of function/method "'.$name.'" does not match with '
					.'the returned values (spec="'.$func->get_return_type().'", returns="'.$merged.'")',
					PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC
				);
			}
		}
	}
	
	/**
	 * Checks wether $vars contains a variable, that may have a type that is not contained in $mtype.
	 * 
	 * @param array $vars the variables
	 * @param PC_Obj_MultiType $mtype the multitype
	 * @return bool true if so
	 */
	private function has_forbidden($vars,$mtype)
	{
		// if the type is unknown (mixed), its always ok
		if($mtype->is_unknown())
			return false;
		foreach($vars as $v)
		{
			if($v !== null)
			{
				foreach($v->get_type()->get_types() as $t)
				{
					if(!$mtype->contains($t))
						return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Reports the given error
	 * 
	 * @param PC_Obj_Location $locsrc an object from which the location will be copied (null = current)
	 * @param string $msg the error-message
	 * @param int $type the error-type
	 */
	private function report_error($locsrc,$msg,$type)
	{
		if($locsrc === null)
			$locsrc = new PC_Obj_Location($this->get_file(),$this->get_line());
		else
			$locsrc = new PC_Obj_Location($locsrc->get_file(),$locsrc->get_line());
		$this->types->add_errors(array(new PC_Obj_Error($locsrc,$msg,$type)));
	}
	
	/**
	 * Returns the method-object for the given function or method
	 * 
	 * @param string $classname the class-name (may be empty)
	 * @param string $funcname the function-name (may be empty)
	 * @return PC_Obj_Method the method or null
	 */
	private function get_method_object($classname,$funcname)
	{
		if($funcname)
		{
			if($classname)
			{
				$classobj = $this->types->get_class($classname);
				if($classobj === null)
					return null;
				return $classobj->get_method($funcname);
			}
			return $this->types->get_function($funcname);
		}
		return null;
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return PC_Obj_Variable the scope-part as variable
	 */
	public function get_scope_part($part)
	{
		return PC_Obj_Variable::create_string($this->scope->get_name_of($part));
	}
	
	/**
	 * Handles a class-constant
	 * 
	 * @param PC_Obj_Variable $classname the variable with the class-name
	 * @param string $constname the const-name
	 * @return PC_Obj_Variable the type
	 */
	public function handle_classconst_access($classname,$constname)
	{
		$cname = $classname->get_type()->get_string();
		if($cname === null)
			return $this->get_unknown();
		
		$class = $this->types->get_class($cname);
		if($class === null)
			return $this->get_unknown();
		$const = $class->get_constant($constname);
		if($const === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$const->get_type());
	}
	
	/**
	 * Static access the given field of given class
	 * 
	 * @param PC_Obj_Variable $class the variable with the class-name
	 * @param array $accesslist an array of the field and array-accesses
	 * @return PC_Obj_Variable the result
	 */
	public function handle_field_access($class,$accesslist)
	{
		assert(count($accesslist) > 0 && $accesslist[0][0] == 'simple');
		$cname = $class->get_type()->get_string();
		if($cname === null)
			return $this->get_unknown();
		if($cname == 'self')
			$cname = $this->scope->get_name_of(T_CLASS_C);
		$classobj = $this->types->get_class($cname);
		if($classobj === null)
			return $this->get_unknown();
		$fname = $accesslist[0][1]->get_type()->get_string();
		if($fname === null)
			return $this->get_unknown();
		$fieldobj = $classobj->get_field($fname);
		if($fieldobj === null)
			return $this->get_unknown();
		$type = $fieldobj->get_type();
		for($i = 1; $i  < count($accesslist); $i++)
		{
			assert($accesslist[$i][0] == 'array');
			if($type === null || $type->get_array() === null || $type->is_array_unknown())
				return $this->get_unknown();
			$key = $accesslist[$i][1]->get_type()->get_scalar();
			$type = $type->get_first()->get_array_type($key);
		}
		return new PC_Obj_Variable('',$type);
	}
	
	/**
	 * Fetches the element from $var at given offset
	 * 
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_Variable $offset the offset
	 * @return PC_Obj_Variable the result
	 */
	public function handle_array_access($var,$offset)
	{
		$this->check_known($var);
		// if we don't know the variable-type, we can't do anything; additionally, better do nothing
		// if its no array.
		// note that null as value is okay because it might be an empty array
		if($var->get_type()->get_array() === null)
			return $this->get_unknown();
		// if we don't know the offset, we can't do anything, either
		if($offset !== null && $offset->get_type()->is_val_unknown())
			return $this->get_unknown();
		
		// PC_Obj_Variable will do the rest for us; simply access the offset
		return $var->array_offset($offset !== null ? $offset->get_type()->get_first()->get_value() : null);
	}
	
	/**
	 * Handles the given unary-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $e the expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_unary_op($op,$e)
	{
		$this->check_known($e);
		if($this->vars->is_in_loop())
			return $this->get_type_from_op($op,$e->get_type());
		return parent::handle_unary_op($op,$e);
	}
	
	/**
	 * Handles the given binary-assignment-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_Variable $e the expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_bin_assign_op($op,$var,$e)
	{
		$res = $this->handle_bin_op($op,$var,$e);
		$this->set_var($var,$res,true);
		return $res;
	}
	
	/**
	 * Handles the given binary-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $e1 the left expression
	 * @param PC_Obj_Variable $e2 the right expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_bin_op($op,$e1,$e2)
	{
		$this->check_known($e1);
		$this->check_known($e2);
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		// if we're in a loop, don't try to provide the value since we don't know how often it is done
		if($this->vars->is_in_loop())
			return  $this->get_type_from_op($op,$t1,$t2);
		// if we don't know one of the types or values, try to determine the type by the operator
		if($t1->is_val_unknown() || $t2->is_val_unknown())
			return $this->get_type_from_op($op,$t1,$t2);
		// if we have an array-operation, check if we know all elements
		if($t1->get_array() !== null && $t2->get_array() !== null)
		{
			// if not, we know at least, that its an array
			if($t1->is_array_unknown() || $t2->is_array_unknown())
				return PC_Obj_Variable::create_array();
		}
		// type and value is known, therefore calculate the result
		$res = 0;
		$rval = $t2->get_first()->get_value_for_eval();
		// prevent division-by-zero error
		if($op == '/' && $rval == 0)
			return PC_Obj_Variable::create_int();
		eval('$res = '.$t1->get_first()->get_value_for_eval().' '.$op.' '.$rval.';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles the ternary operator ?:
	 * 
	 * @param PC_Obj_Variable $e1 the first expression
	 * @param PC_Obj_Variable $e2 the second expression
	 * @param PC_Obj_Variable $e3 the third expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_tri_op($e1,$e2,$e3)
	{
		$this->check_known($e1);
		$this->check_known($e2);
		$this->check_known($e3);
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		$t3 = $e3->get_type();
		// don't try to evalulate $e1 in loops or if its unknown
		if($this->vars->is_in_loop() || $t1->is_val_unknown())
		{
			// merge the types, because the result can be of both types
			$res = clone $t2;
			$res->merge($t3);
			return new PC_Obj_Variable('',$res);
		}
		// type and value is known, so we can evalulate the result
		$res = false;
		eval('$res = '.$t1->get_first()->get_value_for_eval().';');
		if($res)
			return new PC_Obj_Variable('',clone $t2);
		return new PC_Obj_Variable('',clone $t3);
	}
	
	/**
	 * Handles the given compare-operator
	 * 
	 * @param string $op the operator (==, !=, ===, ...)
	 * @param PC_Obj_Variable $e1 the first operand
	 * @param PC_Obj_Variable $e2 the second operand
	 * @return PC_Obj_Variable the result
	 */
	public function handle_cmp($op,$e1,$e2)
	{
		$this->check_known($e1);
		$this->check_known($e2);
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		// if we don't know one of the types or values, try to determine the type by the operator
		// if we're in a loop, do that, too.
		if($this->vars->is_in_loop() || $t1->is_val_unknown() || $t2->is_val_unknown())
			return $this->get_type_from_op($op,$t1,$t2);
		// if we have an array-operation, just return bool, because we would have to do it ourself
		// and I think its not worth the effort.
		$t1arr = $t1->get_first()->get_type() == PC_Obj_Type::TARRAY;
		$t2arr = $t2->get_first()->get_type() == PC_Obj_Type::TARRAY;
		if(($t1arr && $t2arr) || ($t1arr && $t1->is_array_unknown()) || ($t2arr && $t2->is_array_unknown()))
			return PC_Obj_Variable::create_bool();
		
		$val = false;
		$f1 = $t1->get_first();
		$f2 = $t2->get_first();
		switch($op)
		{
			// its not a good idea to use eval in this case because it might change the type
			case '===':
				$val = $f1->get_value_for_use() === $f2->get_value_for_use();
				break;
			case '!==':
				$val = $f1->get_value_for_use() !== $f2->get_value_for_use();
				break;
			
			case '==':
			case '!=':
			case '<':
			case '>':
			case '<=':
			case '>=':
				eval('$val = '.$f1->get_value_for_eval().' '.$op.' '.$f2->get_value_for_eval().';');
				break;
		}
		return PC_Obj_Variable::create_bool($val);
	}
	
	/**
	 * Handles pre-increments and -decrements
	 * 
	 * @param string $op the operator (+,-)expression
	 * @param PC_Obj_Variable $var the variable
	 * @return PC_Obj_Variable the variable
	 */
	public function handle_pre_op($op,$var)
	{
		$this->check_known($var);
		$type = $var->get_type();
		// in loops always by op
		if($this->vars->is_in_loop() || $type->is_val_unknown())
			$res = $this->get_type_from_op($op,$type);
		else
		{
			$res = 0;
			eval('$res = '.$type->get_first()->get_value_for_eval().$op.'1;');
			$res = $this->get_type_from_php($res);
		}
		return $this->set_var($var,$res,true);
	}
	
	/**
	 * Handles post-increments and -decrements
	 * 
	 * @param string $op the operator (+,-)expression
	 * @param PC_Obj_Variable $var the variable
	 * @return PC_Obj_Variable the variable
	 */
	public function handle_post_op($op,$var)
	{
		$this->check_known($var);
		$clone = clone $var;
		$type = $var->get_type();
		// in loops always by op
		if($this->vars->is_in_loop() || $type->is_val_unknown())
			$res = $this->get_type_from_op($op,$type);
		else
		{
			$res = 0;
			eval('$res = '.$type->get_first()->get_value_for_eval().$op.'1;');
			$res = $this->get_type_from_php($res);
		}
		$this->set_var($var,$res,true);
		return $clone;
	}
	
	/**
	 * Handles a cast
	 * 
	 * @param string $cast the cast-type: 'int','float','string','array','object','bool' or 'unset'
	 * @param PC_Obj_Variable $e the expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_cast($cast,$e)
	{
		$this->check_known($e);
		// unset casts to null. in this case we don't really know the value
		// object-cast make no sense here, I think
		if($cast == 'unset' || $cast == 'object')
			return $this->get_unknown();
		
		$t = $e->get_type();
		// if we don't know the type or value, just provide the type; in loops as well
		if($this->vars->is_in_loop() || $t->is_val_unknown())
			return new PC_Obj_Variable('',PC_Obj_MultiType::get_type_by_name($cast));
		
		// we know the value, so perform a cast
		$res = 0;
		eval('$res = ('.$cast.')'.$t->get_first()->get_value_for_eval().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles the instanceof-operator
	 * 
	 * @param PC_Obj_Variable $e the expression
	 * @param PC_Obj_Variable $name the name of the class
	 * @return PC_Obj_Variable the result
	 */
	public function handle_instanceof($e,$name)
	{
		$this->check_known($e);
		$name = $name->get_type()->get_string();
		// if we're in a loop or the name is not a string, give up
		if($this->vars->is_in_loop() || $name === null)
			return PC_Obj_Variable::create_bool();
		
		// if we don't know the type or its class we can't say wether its a superclass
		$classname = $e->get_type()->get_classname();
		if($classname === null)
			return PC_Obj_Variable::create_bool();
		
		// class-name equal?
		if(strcasecmp($classname,$name) == 0)
			return PC_Obj_Variable::create_bool(true);
		
		// if the class is unknown we can't say more
		$class = $this->types->get_class($classname);
		if($class === null)
			return PC_Obj_Variable::create_bool();
		
		// check super-classes
		$super = $class->get_super_class();
		while($super != '')
		{
			if(strcasecmp($super,$name) == 0)
				return PC_Obj_Variable::create_bool(true);
			$superobj = $this->types->get_class($super);
			if($superobj === null)
				break;
			$super = $superobj->get_super_class();
		}
		
		// check interfaces
		if($this->is_instof_interface($class->get_interfaces(),$name))
			return PC_Obj_Variable::create_bool(true);
		return PC_Obj_Variable::create_bool(false);
	}
	
	/**
	 * Checks wether $name is an interface in the interface-hierarchie given by $ifs
	 * 
	 * @param array $ifs the intefaces to check
	 * @param string $name the interface-name
	 * @return bool true if so
	 */
	private function is_instof_interface($ifs,$name)
	{
		foreach($ifs as $if)
		{
			if(strcasecmp($if,$name) == 0)
				return true;
			// check super-interfaces
			$ifobj = $this->types->get_class($if);
			if($ifobj !== null && $this->is_instof_interface($ifobj->get_interfaces(),$name))
				return true;
		}
		return false;
	}
	
	public function advance($parser)
	{
		if($this->pos >= 0)
		{
			$type = $this->tokens[$this->pos][0];
			switch($type)
			{
				case T_COMMENT:
				case self::$T_DOC_COMMENT:
					$wascomment = true;
					break;
				
				case T_FUNCTION:
					$this->start_function($this->get_type_name());
					break;
				case T_INTERFACE:
				case T_CLASS:
					$this->start_class($this->get_type_name());
					break;
				case T_FOR:
				case T_FOREACH:
					$this->start_loop();
					break;
				case T_DO:
					$this->ignoreNextWhile = true;
					$this->start_loop();
					break;
				case T_WHILE:
					if(!$this->ignoreNextWhile)
						$this->start_loop();
					$this->ignoreNextWhile = false;
					break;
				case T_SWITCH:
				case T_TRY:
					$this->start_cond();
					break;
				
				case T_IF:
					if(!$this->lastWasElse)
						$this->start_cond();
					else
					{
						// count the number of "T_ELSE T_IF" because for each of those we get another call
						// to end_cond()
						$this->vars->set_elseif();
					}
					break;
				case T_ELSEIF:
					$this->start_cond(true,false);
					break;
				case T_ELSE:
					$this->start_cond(true,true);
					break;
			}
			$this->lastWasElse = $type == T_ELSE;
		}
		
		$res = parent::advance($parser);
		if($this->lastComment && $this->lastComment != $this->lastCheckComment)
		{
			// it was a comment, so lets see if it contains a "@var $<name> <type>" that gives us a
			// hint what type a variable has.
			$matches = array();
			if(preg_match('/\@var\s+\$([a-z0-9_]+)\s+(\S+)/i',$this->lastComment,$matches))
			{
				// do we know that variable?
				$scopename = $this->scope->get_name();
				if($this->vars->exists($scopename,$matches[1]))
				{
					// ok, determine type and set it
					$type = PC_Obj_MultiType::get_type_by_name($matches[2]);
					$var = $this->vars->get($scopename,$matches[1]);
					$var->set_type($type);
				}
			}
			$this->lastCheckComment = $this->lastComment;
		}
		return $res;
	}
	
	/**
	 * @param string $name the var-name
	 * @return PC_Obj_Variable a variable with given name and unknown type
	 */
	private function get_unknown($name = '')
	{
		return new PC_Obj_Variable($name,new PC_Obj_MultiType());
	}
	
	/**
	 * @return string the value of the next following T_STRING-token
	 */
	private function get_type_name()
	{
		for($i = $this->pos + 1; $i < $this->tokCount; $i++)
		{
			if($this->tokens[$i][0] == T_STRING)
				return $this->tokens[$i][1];
			if($this->tokens[$i][1] == '(')
				return PC_Obj_Method::ANON_PREFIX.($this->anon_id++);
		}
		return null;
	}
}
