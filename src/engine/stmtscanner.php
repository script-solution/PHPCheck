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
	 * @param PC_Engine_Options $options the options
	 * @return PC_Engine_StmtScanner the instance for lexing a file
	 */
	public static function get_for_file($file,$types,$options)
	{
		return new self($file,true,$types,$options);
	}
	
	/**
	 * @param string $string the string
	 * @param PC_Engine_TypeContainer $types the type-container
	 * @param PC_Engine_Options $options the options
	 * @return PC_Engine_StmtScanner the instance for lexing a string
	 */
	public static function get_for_string($string,$types,$options)
	{
		return new self($string,false,$types,$options);
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
	 * An array of all throw types of the current function/method
	 *
	 * @var array
	 */
	private $allthrows = array();
	
	/**
	 * The options
	 *
	 * @var PC_Engine_Options
	 */
	private $options;
	
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
	 * @param PC_Engine_Options $options the options
	 */
	protected function __construct($str,$is_file,$types,$options)
	{
		parent::__construct($str,$is_file);
		
		if(!($types instanceof PC_Engine_TypeContainer))
			FWS_Helper::def_error('instance','types',PC_Engine_TypeContainer,$types);
		if(!($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		
		$this->types = $types;
		$this->scope = new PC_Engine_Scope();
		$this->vars = new PC_Engine_VarContainer();
		$this->options = $options;
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
	 * @param PC_Obj_MultiType $class the class-name (or null)
	 * @param PC_Obj_MultiType $func the function-name
	 * @param array $args the function-arguments
	 * @param bool $static wether its a static call
	 * @return PC_Obj_MultiType the result
	 */
	public function add_call($class,$func,$args,$static = false)
	{
		if($class !== null && !($class instanceof PC_Obj_MultiType))
			return $this->handle_error('$class is invalid');
		if(!($func instanceof PC_Obj_MultiType))
			return $this->handle_error('$func is invalid');
		if(!is_array($args))
			return $this->handle_error('$args is invalid');
		
		// if we don't know the function- or class-name, we can't do anything here
		$cname = $class !== null ? $class->get_string() : null;
		$fname = $func->get_string();
		if($fname === null || ($class !== null && $cname === null))
			return $this->create_unknown();
		
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
					return $this->create_unknown();
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
			if(!($arg instanceof PC_Obj_MultiType))
			{
				$this->handle_error('$arg is invalid');
				continue;
			}
			$call->add_argument(clone $arg);
		}
		$call->set_static($static);
		$this->types->add_call($call);
		
		// if its a constructor we know the type directly
		if(strcasecmp($fname,'__construct') == 0 || strcasecmp($fname,$cname) == 0)
			return PC_Obj_MultiType::create_object($cname);
		
		// get function-object and determine return-type
		$funcobj = $this->get_method_object($cname,$fname);
		
		$this->analyze_modifiers($call,$funcobj);
		
		if($funcobj === null)
			return $this->create_unknown();
		
		// add the throws of the method to our throws
		foreach($funcobj->get_throws() as $tclass => $ttype)
		{
			$this->allthrows[] = array(
				PC_Obj_Method::THROW_FUNC,
				PC_Obj_MultiType::create_object($tclass)
			);
		}
		
		return clone $funcobj->get_return_type();
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
		if(!($obj instanceof PC_Obj_Variable))
			return $this->create_var('',$this->handle_error('$obj is invalid'));
		if(!is_array($chain))
			return $this->create_var('',$this->handle_error('$chain is invalid'));
		
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
				return $this->create_var();
			// if we don't know the class, give up, as well
			$class = $this->types->get_class($classname);
			if($class === null)
				return $this->create_var();
			
			$prop = $access['prop'];
			$args = $access['args'];
			assert(count($prop) > 0 && $prop[0]['type'] == 'name');
			if(count($prop) > 1 || $args === null)
			{
				$fieldvar = $prop[0]['data'];
				$fieldname = $fieldvar->get_string();
				if($fieldname === null)
					return $this->create_var();
				$field = $class->get_field($fieldname);
				if($field === null)
				{
					$this->report_error(
						null,
						'Access of not-existing field "'.$fieldname.'" of class "#'.$classname.'#"',
						PC_Obj_Error::E_S_NOT_EXISTING_FIELD
					);
					return $this->create_var();
				}
				$res = $field->get_type();
				for($i = 1; $i < count($prop); $i++)
				{
					assert($prop[$i]['type'] == 'array');
					$offset = $prop[$i]['data'];
					// if the offset is null it means that we should append to the array. this is not supported
					// for class-fields. therefore stop here and return unknown
					if($offset === null || $res === null || $res->get_array() === null)
						return $this->create_var();
					$off = $offset->get_scalar();
					if($off === null)
						return $this->create_var();
					$res = $res->get_first()->get_array_type($off);
				}
			}
			else
			{
				$mnamevar = $prop[0]['data'];
				$mname = $mnamevar->get_string();
				if($mname === null)
					return $this->create_var();
				$this->add_call(PC_Obj_MultiType::create_string($class->get_name()),$mnamevar,$args,false);
				$method = $class->get_method($mname);
				if($method === null)
					return $this->create_var();
				$res = clone $method->get_return_type();
			}
			$objt = $res;
		}
		if($res === null)
			return $this->create_var();
		return $this->create_var('',$res);
	}
	
	/**
	 * Returns the value of the given constant
	 * 
	 * @param string $name the constant-name
	 * @return PC_Obj_MultiType the type
	 */
	public function get_constant_type($name)
	{
		$const = $this->types->get_constant($name);
		if($const === null)
			return $this->create_unknown();
		return $const->get_type();
	}
	
	/**
	 * Checks wether the given variable is known. If not, it reports an error
	 * 
	 * @param PC_Obj_Variable $var the variable
	 */
	private function check_known($var)
	{
		if(!($var instanceof PC_Obj_Variable))
		{
			$this->handle_error('$var is invalid');
			return;
		}
		
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
	 * Returns the value of the variable with given name in current/parent scope. If it does not
	 * exist, a new one is created (but not stored in scope).
	 * 
	 * @param PC_Obj_MultiType $var the variable-name
	 * @param bool $parent whether to search in the parent scope
	 * @return PC_Obj_Variable the variable
	 */
	public function get_var($var,$parent = false)
	{
		if(!($var instanceof PC_Obj_MultiType))
			return $this->create_var('',$this->handle_error('$var is invalid'));
		
		$name = $var->get_string();
		if($name == null)
			return $this->create_var();
		if($name == 'this')
		{
			$res = PC_Obj_Variable::create_object(
				$this->get_file(),$this->get_line(),$this->scope->get_name_of(T_CLASS_C,$parent)
			);
			return $res;
		}
		$scopename = $this->scope->get_name($parent);
		if(!$this->vars->exists($scopename,$name))
			return $this->create_var($name,$this->create_unknown());
		return $this->vars->get($scopename,$name);
	}
	
	/**
	 * Sets the given variable in the current scope to given value
	 * 
	 * @param PC_Obj_Variable $var the variable to set
	 * @param PC_Obj_MultiType $value the value
	 * @param bool $isref wether to store a reference (default = false)
	 * @return PC_Obj_MultiType the value
	 */
	public function set_var($var,$value,$isref = false)
	{
		if(!($var instanceof PC_Obj_Variable))
			return $this->handle_error('$var is invalid');
		if(!($value instanceof PC_Obj_MultiType))
			return $this->handle_error('$value is invalid');
		
		$varname = $var->get_name();
		$scopename = $this->scope->get_name();
		
		// generate error for assignments of void
		if($value->contains(new PC_Obj_Type(PC_Obj_Type::VOID)))
		{
			$this->report_error(
				new PC_Obj_Location($this->get_file(),$this->get_line()),
				'Assignment of void to $'.$varname,
				PC_Obj_Error::E_S_VOID_ASSIGN
			);
		}
		
		$this->vars->backup($var,$this->scope);
		if($isref)
			$var->set_type($value);
		else
			$var->set_type(clone $value);
		if($varname)
		{
			$var->set_function($this->scope->get_name_of(T_FUNC_C));
			$var->set_class($this->scope->get_name_of(T_CLASS_C));
			$this->vars->set($scopename,$var);
		}
		return $value;
	}
	
	/**
	 * Unsets the given variable
	 *
	 * @param PC_Obj_Variable $var the variable
	 */
	public function unset_var($var)
	{
		$scopename = $this->scope->get_name();
		$this->vars->unset($scopename,$var);
	}
	
	/**
	 * Sets the given function-parameter for the current scope
	 * 
	 * @param PC_Obj_Parameter $p the parameter
	 */
	public function set_func_param($p)
	{
		if(!($p instanceof PC_Obj_Parameter))
		{
			$this->handle_error('$p is invalid');
			return;
		}
		
		$var = $this->get_var(PC_Obj_MultiType::create_string($p->get_name()));
		
		// give type-hinting the highest prio, because I think its the most trustable type
		if(!$p->get_mtype()->is_unknown())
		{
			$this->set_var($var,$p->get_mtype());
			return;
		}
		
		// if a variable-type is unknown and we're in a function/class, check if we know the type
		// from the type-scanner
		$func = $this->get_method_object(
			$this->scope->get_name_of(T_CLASS_C),$this->scope->get_name_of(T_FUNC_C)
		);
		// if we have a doc, use it. otherwise use the default-value
		$doc = $this->get_funcparam_type($func,$p->get_name());
		if($doc !== null)
			$this->set_var($var,$doc);
		else
			$this->set_var($var,$p->get_mtype());
	}
	
	/**
	 * Determines the type of the given function-parameter
	 * 
	 * @param PC_Obj_Method $func the function/method
	 * @param string $varname the variable-name
	 * @return PC_Obj_MultiType the type
	 */
	private function get_funcparam_type($func,$varname)
	{
		if($func === null)
			return null;
		$param = $func->get_param($varname);
		if($param === null)
			return null;
		return $param->get_mtype();
	}
	
	/**
	 * Sets $first and $sec for the current foreach-loop
	 * 
	 * @param PC_Obj_MultiType $array the array over which we loop
	 * @param array $first an array of variables (key or value)
	 * @param array $sec an array of variables (value, if not null)
	 */
	public function set_foreach_var($array,$first,$sec)
	{
		if(!($array instanceof PC_Obj_MultiType))
		{
			$this->handle_error('$array is invalid');
			return;
		}
		if(!is_array($first))
		{
			$this->handle_error('$first is invalid');
			return;
		}
		if($sec !== null && !is_array($sec))
		{
			$this->handle_error('$sec is invalid');
			return;
		}
		
		$arrval = $array->get_array();
		// if we don't know the array-values, it is no array or there are no elements, we don't
		// know the types of $first and $sec
		if($arrval === null || $array->is_array_unknown() || count($arrval) == 0)
		{
			foreach($first as $f)
				$this->set_var($f,$this->create_unknown());
			if($sec !== null)
			{
				foreach($sec as $s)
					$this->set_var($s,$this->create_unknown());
			}
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
			if(count($first) > 1)
			{
				// don't look at the array elements; just pretend we don't know
				foreach($first as $f)
					$this->set_var($f,new PC_Obj_MultiType(),false);
			}
			else
				$this->set_var($first[0],$firsttype,false);
			
			if($sectype !== null)
			{
				if(count($sec) > 1)
				{
					// don't look at the array elements; just pretend we don't know
					foreach($sec as $s)
						$this->set_var($s,new PC_Obj_MultiType(),false);
				}
				else
					$this->set_var($sec[0],$sectype,false);
			}
		}
	}
	
	/**
	 * Creates the type for given constant (true, false, defines).
	 *
	 * @param string $name the name
	 * @return PC_Obj_MultiType the type
	 */
	public function handle_constant($name)
	{
    if(strcasecmp($name,"true") == 0)
        return PC_Obj_MultiType::create_bool(true);
    else if(strcasecmp($name,"false") == 0)
        return PC_Obj_MultiType::create_bool(false);
    else
        return $this->get_constant_type($name);
	}
	
	/**
	 * Handles the list()-construct
	 * 
	 * @param array $list an array of PC_Variables to assign; may contain sub-arrays; contains null
	 * 	if an element should be ignored
	 * @param PC_Obj_MultiType $expr the array to take the elements from
	 * @return PC_Obj_MultiType the result ($expr)
	 */
	public function handle_list($list,$expr)
	{
		if(!is_array($list))
			return $this->handle_error('$list is invalid');
		if(!($expr instanceof PC_Obj_MultiType))
			return $this->handle_error('$expr is invalid');
		
		$this->handle_list_rek($list,$expr);
		return $expr;
	}
	
	/**
	 * The rekursive handler of list
	 * 
	 * @param array $list an array of PC_Variables to assign; may contain sub-arrays; contains null
	 * 	if an element should be ignored
	 * @param PC_Obj_MultiType $expr the array to take the elements from
	 */
	private function handle_list_rek($list,$expr)
	{
		$array = $expr->get_array();
		$lcount = count($list);
		$ecount = $array === null ? 0 : count($array);
		for($i = 0; $i < $lcount; $i++)
		{
			if($list[$i] === null)
				continue;
			$el = isset($array[$i]) ? $array[$i] : $this->create_unknown();
			if(is_array($list[$i]))
				$this->handle_list_rek($list[$i],$el);
			else
				$this->set_var($list[$i],$el);
		}
	}
	
	/**
	 * Adds the given return-statement
	 * 
	 * @param PC_Obj_MultiType $expr the expression; null if its a "return;"
	 */
	public function add_return($expr)
	{
		if($expr !== null && !($expr instanceof PC_Obj_MultiType))
		{
			$this->handle_error('$expr is invalid');
			return;
		}
		
		$this->allrettypes[] = $expr;
	}
	
	/**
	 * Adds the given expression as thrown
	 * 
	 * @param PC_Obj_MultiType $expr the expression
	 */
	public function add_throw($expr)
	{
		if(!($expr instanceof PC_Obj_MultiType))
		{
			$this->handle_error('$expr is invalid');
			return;
		}
		
		// don't even collect unknown types here
		if(!$expr->is_unknown())
			$this->allthrows[] = array(PC_Obj_Method::THROW_SELF,$expr);
	}
	
	/**
	 * Puts the variable from global scope into the current one
	 * 
	 * @param PC_Obj_Variable $var the variable
	 */
	public function do_global($var)
	{
		if(!($var instanceof PC_Obj_Variable))
		{
			$this->handle_error('$var is invalid');
			return;
		}
		
		if($this->vars->exists(PC_Obj_Variable::SCOPE_GLOBAL,$var->get_name()))
			$val = $this->vars->get(PC_Obj_Variable::SCOPE_GLOBAL,$var->get_name())->get_type();
		else
			$val = $this->create_unknown();
		$this->set_var($this->create_var($var->get_name(),$this->create_unknown()),$val,true);
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
		$this->allthrows = array();
		$this->scope->enter_function($name);
	}
	
	/**
	 * Ends the current function
	 */
	public function end_function()
	{
		$this->analyze_rettypes();
		$this->analyze_throws();
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
		$this->vars->leave_loop($this->get_file(),$this->get_line(),$this->scope);
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
		$this->vars->leave_cond($this->get_file(),$this->get_line(),$this->scope);
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return PC_Obj_MultiType the scope-part as variable
	 */
	public function get_scope_part($part)
	{
		return PC_Obj_MultiType::create_string($this->scope->get_name_of($part));
	}
	
	/**
	 * Handles a class-constant
	 * 
	 * @param PC_Obj_MultiType $classname the class-name
	 * @param string $constname the const-name
	 * @return PC_Obj_MultiType the type
	 */
	public function handle_classconst_access($classname,$constname)
	{
		if(!($classname instanceof PC_Obj_MultiType))
			return $this->handle_error('$classname is invalid');
		
		$cname = $classname->get_string();
		if($cname === null)
			return $this->create_unknown();
		
		$class = $this->types->get_class($cname);
		if($class === null)
			return $this->create_unknown();
		$const = $class->get_constant($constname);
		if($const === null)
			return $this->create_unknown();
		return $const->get_type();
	}
	
	/**
	 * Static access the given field of given class
	 * 
	 * @param PC_Obj_MultiType $class the class-name
	 * @param string $field the field-name
	 * @return PC_Obj_Variable the result
	 */
	public function handle_field_access($class,$field)
	{
		if(!($class instanceof PC_Obj_MultiType))
			return $this->create_var('',$this->handle_error('$class is invalid'));
		
		$cname = $class->get_string();
		if($cname == 'self')
			$cname = $this->scope->get_name_of(T_CLASS_C);
		$classobj = $this->types->get_class($cname);
		if($classobj === null)
			return $this->create_var();
		$fieldobj = $classobj->get_field($field);
		if($fieldobj === null)
			return $this->create_var();
		return $this->create_var('',$fieldobj->get_type());
	}
	
	/**
	 * Fetches the element from $var at given offset
	 * 
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_MultiType $offset the offset
	 * @return PC_Obj_Variable the result
	 */
	public function handle_array_access($var,$offset)
	{
		if(!($var instanceof PC_Obj_Variable))
			return $this->create_var('',$this->handle_error('$var is invalid'));
		if($offset !== null && !($offset instanceof PC_Obj_MultiType))
			return $this->create_var('',$this->handle_error('$offset is invalid'));
		
		$this->check_known($var);
		// if we don't know the variable-type, we can't do anything; additionally, better do nothing
		// if its no array.
		// note that null as value is okay because it might be an empty array
		if($var->get_type()->get_array() === null)
			return $this->create_var();
		
		// PC_Obj_Variable will do the rest for us; simply access the offset
		return $var->array_offset($offset);
	}
	
	/**
	 * Handles the given unary-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_MultiType $e the expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_unary_op($op,$e)
	{
		if(!($e instanceof PC_Obj_MultiType))
			return $this->handle_error('$e is invalid');
		
		if($this->vars->is_in_loop())
			return $this->get_type_from_op($op,$e);
		return parent::handle_unary_op($op,$e);
	}
	
	/**
	 * Handles the given binary-assignment-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_MultiType $e the expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_bin_assign_op($op,$var,$e)
	{
		if(!($var instanceof PC_Obj_Variable))
			return $this->handle_error('$var is invalid');
		if(!($e instanceof PC_Obj_MultiType))
			return $this->handle_error('$e is invalid');
		
		$res = $this->handle_bin_op($op,$var->get_type(),$e);
		$this->set_var($var,$res,true);
		return $res;
	}
	
	/**
	 * Handles the given binary-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_MultiType $e1 the left expression
	 * @param PC_Obj_MultiType $e2 the right expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_bin_op($op,$e1,$e2)
	{
		if(!($e1 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e1 is invalid');
		if(!($e2 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e2 is invalid');
		
		// if we're in a loop, don't try to provide the value since we don't know how often it is done
		if($this->vars->is_in_loop())
			return  $this->get_type_from_op($op,$e1,$e2);
		// if we don't know one of the types or values, try to determine the type by the operator
		if($e1->is_val_unknown() || $e2->is_val_unknown())
			return $this->get_type_from_op($op,$e1,$e2);
		// if we have an array-operation, check if we know all elements
		if($e1->get_array() !== null && $e2->get_array() !== null)
		{
			// if not, we know at least, that its an array
			if($e1->is_array_unknown() || $e2->is_array_unknown())
				return PC_Obj_MultiType::create_array();
		}
		// type and value is known, therefore calculate the result
		$res = 0;
		$rval = $e2->get_first()->get_value_for_eval();
		// prevent division-by-zero error
		if($op == '/' && $rval == 0)
			return PC_Obj_MultiType::create_int();
		eval('$res = '.$e1->get_first()->get_value_for_eval().' '.$op.' '.$rval.';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles the ternary operator ?:
	 * 
	 * @param PC_Obj_MultiType $e1 the first expression
	 * @param PC_Obj_MultiType $e2 the second expression
	 * @param PC_Obj_MultiType $e3 the third expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_tri_op($e1,$e2,$e3)
	{
		if(!($e1 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e1 is invalid');
		if(!($e2 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e2 is invalid');
		if(!($e3 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e3 is invalid');
		
		// don't try to evalulate $e1 in loops or if its unknown
		if($this->vars->is_in_loop() || $e1->is_array_unknown())
		{
			// merge the types, because the result can be of both types
			$res = clone $e2;
			$res->merge($e3);
			return $res;
		}
		// type and value is known, so we can evalulate the result
		$res = false;
		eval('$res = '.$e1->get_first()->get_value_for_eval().';');
		if($res)
			return clone $e2;
		return clone $e3;
	}
	
	/**
	 * Handles the given compare-operator
	 * 
	 * @param string $op the operator (==, !=, ===, ...)
	 * @param PC_Obj_MultiType $e1 the first operand
	 * @param PC_Obj_MultiType $e2 the second operand
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_cmp($op,$e1,$e2)
	{
		if(!($e1 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e1 is invalid');
		if(!($e2 instanceof PC_Obj_MultiType))
			return $this->handle_error('$e2 is invalid');
		
		// if we don't know one of the types or values, try to determine the type by the operator
		// if we're in a loop, do that, too.
		if($this->vars->is_in_loop() || $e1->is_val_unknown() || $e2->is_val_unknown())
			return $this->get_type_from_op($op,$e1,$e2);
		// if we have an array-operation, just return bool, because we would have to do it ourself
		// and I think its not worth the effort.
		$e1arr = $e1->get_first()->get_type() == PC_Obj_Type::TARRAY;
		$e2arr = $e2->get_first()->get_type() == PC_Obj_Type::TARRAY;
		if(($e1arr && $e2arr) || ($e1arr && $e1->is_array_unknown()) || ($e2arr && $e2->is_array_unknown()))
			return PC_Obj_MultiType::create_bool();
		
		$val = false;
		$f1 = $e1->get_first();
		$f2 = $e2->get_first();
		switch($op)
		{
			// its not a good idea to use eval in this case because it might change the type
			case '===':
				$val = $f1->get_value_for_use() === $f2->get_value_for_use();
				break;
			case '!==':
				$val = $f1->get_value_for_use() !== $f2->get_value_for_use();
				break;
			
			case '?:':
				if($f1->get_value_for_use() < $f2->get_value_for_use())
					$val = -1;
				else if($f1->get_value_for_use() > $f2->get_value_for_use())
					$val = 1;
				else
					$val = 0;
				return PC_Obj_MultiType::create_int($val);
			
			case '==':
			case '!=':
			case '<':
			case '>':
			case '<=':
			case '>=':
			case '?:':
				eval('$val = '.$f1->get_value_for_eval().' '.$op.' '.$f2->get_value_for_eval().';');
				break;
		}
		return PC_Obj_MultiType::create_bool($val);
	}
	
	/**
	 * Handles pre-increments and -decrements
	 * 
	 * @param string $op the operator (+,-)expression
	 * @param PC_Obj_Variable $var the variable
	 * @return PC_Obj_MultiType the variable
	 */
	public function handle_pre_op($op,$var)
	{
		if(!($var instanceof PC_Obj_Variable))
			return $this->handle_error('$var is invalid');
		
		$this->check_known($var);
		$type = $var->get_type();
		// in loops always by op
		if($this->vars->is_in_loop() || $type->is_array_unknown())
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
	 * @return PC_Obj_MultiType the variable
	 */
	public function handle_post_op($op,$var)
	{
		if(!($var instanceof PC_Obj_Variable))
			return $this->handle_error('$var is invalid');
		
		$this->check_known($var);
		$type = $var->get_type();
		$clone = clone $type;
		// in loops always by op
		if($this->vars->is_in_loop() || $type->is_array_unknown())
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
	 * @param PC_Obj_MultiType $e the expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_cast($cast,$e)
	{
		if(!($e instanceof PC_Obj_MultiType))
			return $this->handle_error('$e is invalid');
		
		// unset casts to null. in this case we don't really know the value
		// object-cast make no sense here, I think
		if($cast == 'unset' || $cast == 'object')
			return $this->create_unknown();
		
		// if we don't know the type or value, just provide the type; in loops as well
		if($this->vars->is_in_loop() || $e->is_array_unknown())
			return PC_Obj_MultiType::get_type_by_name($cast);
		
		// we know the value, so perform a cast
		$res = 0;
		eval('$res = ('.$cast.')'.$e->get_first()->get_value_for_eval().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles the instanceof-operator
	 * 
	 * @param PC_Obj_MultiType $e the expression
	 * @param string $name the name of the class
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_instanceof($e,$name)
	{
		if(!($e instanceof PC_Obj_MultiType))
			return $this->handle_error('$e is invalid');
		
		// if we're in a loop or the name is not a string, give up
		if($this->vars->is_in_loop() || $name === null)
			return PC_Obj_MultiType::create_bool();
		
		// if we don't know the type or its class we can't say wether its a superclass
		$classname = $e->get_classname();
		if($classname === null)
			return PC_Obj_MultiType::create_bool();
		
		// class-name equal?
		if(strcasecmp($classname,$name) == 0)
			return PC_Obj_MultiType::create_bool(true);
		
		// if the class is unknown we can't say more
		$class = $this->types->get_class($classname);
		if($class === null)
			return PC_Obj_MultiType::create_bool();
		
		// check super-classes
		$super = $class->get_super_class();
		while($super != '')
		{
			if(strcasecmp($super,$name) == 0)
				return PC_Obj_MultiType::create_bool(true);
			$superobj = $this->types->get_class($super);
			if($superobj === null)
				break;
			$super = $superobj->get_super_class();
		}
		
		// check interfaces
		if($this->is_instof_interface($class->get_interfaces(),$name))
			return PC_Obj_MultiType::create_bool(true);
		return PC_Obj_MultiType::create_bool(false);
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
					$this->report_error(
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
					$this->report_error(
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
					$this->report_error(
						$func,
						'The function/method "'.$name.'" has a return-specification in PHPDoc'
						.', but does not return a value',
						PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET
					);
				}
				else if(!$docreturn && !$func->is_anonymous() && $hasother)
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
					$this->report_error(
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
	}
	
	/**
	 * Analyzes all throw statements that have been found in the current function and checks their
	 * validity against the PHPDoc throw specifications.
	 */
	private function analyze_throws()
	{
		$funcname = $this->scope->get_name_of(T_FUNC_C);
		$classname = $this->scope->get_name_of(T_CLASS_C);
		$func = $this->get_method_object($classname,$funcname);
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
						if($type->get_class() == $tclass)
						{
							$found = true;
							break;
						}
					}
				}
				
				if(!$found)
				{
					$this->report_error(
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
							$this->report_error(
								$func,
								'The function/method "'.$name.'" does not throw "'.$type.'" according to PHPDoc'
								.', but throws it',
								PC_Obj_Error::E_S_THROW_NOT_IN_DOC
							);
						}
					}
					else
					{
						$this->report_error(
							$func,
							'The function/method "'.$name.'" throws a non-object ('.$type.')',
							PC_Obj_Error::E_S_THROW_INVALID
						);
					}
				}
			}
		}
	}
	
	/**
	 * Analyzes whether the given method is visible at the given call.
	 *
	 * @param PC_Obj_Call $call the method call
	 * @param PC_Obj_Method $func the method object
	 */
	private function analyze_modifiers($call,$method)
	{
		if($method && $method->get_visibility() == PC_Obj_Method::V_PUBLIC)
			return;
		
		// if we don't know it yet, search in the superclasses. in this case, it's always private
		if(!$method && $call->get_class())
			$method = $this->get_method_of_super($call->get_class(),$call->get_function());
		else if($method)
		{
			$cur_class = $this->scope->get_name_of(T_CLASS_C);
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
			$this->report_error(
				$method,
				'The function/method "'.$name.'" is '.$method->get_visibility().' at this location',
				PC_Obj_Error::E_S_METHOD_VISIBILITY
			);
		}
	}
	
	/**
	 * Searches for the given method in any superclass.
	 *
	 * @param string $class the class name
	 * @param string $name the method name
	 * @return PC_Obj_Method the method or null
	 */
	private function get_method_of_super($class,$name)
	{
		$cobj = $this->types->get_class($class);
		if(!$cobj)
			return null;
		if($cobj->contains_method($name))
			return $cobj->get_method($name);
		return $this->get_method_of_super($cobj->get_super_class(),$name);
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
		$cobj = $this->types->get_class($class);
		if(!$cobj)
			return false;
		if($cobj->get_super_class() == $super)
			return true;
		return $this->is_subclass_of($cobj->get_super_class(),$super);
	}
	
	/**
	 * Checks wether $types contains a type, that is not contained in $mtype.
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
