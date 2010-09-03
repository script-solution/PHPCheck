<?php
/**
 * Contains the statement-lexer
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Uses the tokens from token_get_all() and walks over the tokens. It stores various information
 * about the current state
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Compile_StmtLexer extends PC_Compile_BaseLexer
{
	/**
	 * @param string $file the filename
	 * @param PC_Compile_TypeContainer $types the type-container
	 * @return PC_Compile_StmtLexer the instance for lexing a file
	 */
	public static function get_for_file($file,$types)
	{
		return new self($file,true,$types);
	}
	
	/**
	 * @param string $string the string
	 * @param PC_Compile_TypeContainer $types the type-container
	 * @return PC_Compile_StmtLexer the instance for lexing a string
	 */
	public static function get_for_string($string,$types)
	{
		return new self($string,false,$types);
	}
	
	/**
	 * The current scope
	 * 
	 * @var string
	 */
	private $scope = PC_Obj_Variable::SCOPE_GLOBAL;
	/**
	 * Will be > 0 if we're in a loop
	 * 
	 * @var int
	 */
	private $loopdepth = 0;
	/**
	 * Will be > 0 if we're in a condition
	 * 
	 * @var int
	 */
	private $conddepth = 0;
	/**
	 * For each condition and loop a list of variables we should mark as unknown as soon
	 * as we leave the condition.
	 * 
	 * @var array
	 */
	private $layers = array();
	/**
	 * The variables
	 * 
	 * @var array
	 */
	private $vars = array(
		PC_Obj_Variable::SCOPE_GLOBAL => array()
	);
	/**
	 * The found function-calls
	 * 
	 * @var array
	 */
	private $calls = array();
	
	/**
	 * The known types
	 * 
	 * @var PC_Compile_TypeContainer
	 */
	private $types;
	
	/**
	 * Constructor
	 * 
	 * @param string $str the file or string
	 * @param bool $is_file wether $str is a file
	 * @param PC_Compile_TypeContainer $types the type-container
	 */
	protected function __construct($str,$is_file,$types)
	{
		parent::__construct($str,$is_file);
		
		$this->types = $types;
	}
	
	/**
	 * @return array the found variables
	 */
	public function get_vars()
	{
		return $this->vars;
	}
	
	/**
	 * @return array the found function-calls
	 */
	public function get_calls()
	{
		return $this->calls;
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
		if($func->get_type()->is_unknown() || $func->get_type()->get_value() === null)
			return $this->get_unknown();
		if($class !== null && ($class->get_type()->is_unknown() || $class->get_type()->get_value() === null))
			return $this->get_unknown();
		
		// create call
		$call = new PC_Obj_Call($this->get_file(),$this->get_line());
		
		// determine class- and function-name
		$fname = $func->get_type()->get_value();
		$call->set_function($fname);
		$call->set_object_creation($fname == '__construct');
		if($class !== null)
		{
			$cname = $class->get_type()->get_value();
			if($cname == 'parent')
			{
				$cname = $this->get_scope_part_name(T_CLASS_C);
				$classobj = $this->types->get_class($cname);
				if($classobj === null || $classobj->get_super_class() == '')
					return $this->get_unknown();
				$cname = $classobj->get_super_class();
				// parent-calls are never static
				$static = false;
			}
			else if($cname == 'self')
			{
				$cname = $this->get_scope_part_name(T_CLASS_C);
				// self is always static
				$static = true;
			}
			$call->set_class($cname);
		}
		
		// clone because it might be a variable
		foreach($args as $arg)
			$call->add_argument(clone $arg->get_type());
		$call->set_static($static);
		$this->calls[] = $call;
		
		// if its a constructor we know the type directly
		if($fname == '__construct')
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$cname));
		// get function-object and determine return-type
		$funcobj = null;
		if($class !== null)
		{
			$classobj = $this->types->get_class($cname);
			if($classobj === null)
				return $this->get_unknown();
			$funcobj = $classobj->get_method($fname);
		}
		else
			$funcobj = $this->types->get_function($fname);
		
		if($funcobj === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$funcobj->get_return_type());
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
			$classname = $this->get_scope_part_name(T_CLASS_C);
			if($classname)
				$objt = new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$classname);
		}
		else
			$objt = $obj->get_type();
		foreach($chain as $access)
		{
			// if we don't know the class-name or its no object, stop here
			if($objt === null || $objt->get_type() != PC_Obj_Type::OBJECT || !$objt->get_class())
				return $this->get_unknown();
			// if we don't know the class, give up, as well
			$class = $this->types->get_class($objt->get_class());
			if($class === null)
				return $this->get_unknown();
			
			$prop = $access['prop'];
			$args = $access['args'];
			assert(count($prop) > 0 && $prop[0]['type'] == 'name');
			if(count($prop) > 1 || $args === null)
			{
				$fieldname = $prop[0]['data'];
				if($fieldname->get_type()->is_unknown() || $fieldname->get_type()->get_value() === null)
					return $this->get_unknown();
				$field = $class->get_field($fieldname->get_type()->get_value());
				if($field === null)
					return $this->get_unknown();
				$res = $field->get_type();
				for($i = 1; $i < count($prop); $i++)
				{
					assert($prop[$i]['type'] == 'array');
					$offset = $prop[$i]['data'];
					// if the offset is null it means that we should append to the array. this is not supported
					// for class-fields. therefore stop here and return unknown
					if($offset === null || $res === null || $res->get_type() != PC_Obj_Type::TARRAY)
						return $this->get_unknown();
					if($offset->get_type()->is_unknown() || $offset->get_type()->get_value() === null)
						return $this->get_unknown();
					$res = $res->get_array_type($offset->get_type()->get_value());
				}
			}
			else
			{
				$mname = $prop[0]['data'];
				if($mname->get_type()->is_unknown() || $mname->get_type()->get_value() === null)
					return $this->get_unknown();
				$method = $class->get_method($mname->get_type()->get_value());
				if($method === null)
					return $this->get_unknown();
				$this->add_call(
					new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::STRING,$class->get_name())),
					$mname,$args,false
				);
				$res = $method->get_return_type();
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
	 * Returns the value of the variable with given name in current scope. If it does not exist,
	 * a new one is created (but not stored in scope).
	 * 
	 * @param string $var the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public function get_var($var)
	{
		if($var == 'this')
		{
			return new PC_Obj_Variable(
				'',new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$this->get_scope_part_name(T_CLASS_C))
			);
		}
		if(!isset($this->vars[$this->scope][$var]))
			return $this->get_unknown($var);
		return $this->vars[$this->scope][$var];
	}
	
	/**
	 * Sets the given variable in the current scope to given value
	 * 
	 * @param PC_Obj_Variable $var the variable to set
	 * @param PC_Obj_Variable $value the value
	 * @return PC_Obj_Variable the variable
	 */
	public function set_var($var,$value)
	{
		$varname = $var->get_name();
		// if we're in a condition save a backup of the current var for later comparisons
		if($varname && count($this->layers) > 0)
		{
			$layer = &$this->layers[count($this->layers) - 1];
			if(!isset($layer[$varname]))
			{
				$clone = isset($this->vars[$this->scope][$varname]) ? clone $var : null;
				$layer[$varname] = $clone;
			}
		}
		if($value === null)
		{
			// if a variable-type is unknown and we're in a function/class, check if we know the type
			// from the type-scanner
			if(($pos = strpos($this->scope,'::')) !== false)
			{
				$class = $this->types->get_class(substr($this->scope,0,$pos));
				if($class === null)
					$value = $this->get_unknown();
				else
				{
					$func = $class->get_method(substr($this->scope,$pos + 2));
					$value = $this->get_funcparam_type($func,$varname);
				}
			}
			else if($this->scope != PC_Obj_Variable::SCOPE_GLOBAL)
			{
				$func = $this->types->get_function($this->scope);
				$value = $this->get_funcparam_type($func,$varname);
			}
			else
				$value = $this->get_unknown();
		}
		$var->set_type($value->get_type());
		if($varname)
		{
			$var->set_function($this->get_func());
			$var->set_class($this->get_class());
			$this->vars[$this->scope][$varname] = $var;
		}
		return $value;
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
			return $this->get_unknown();
		// TODO remove the '$' in the type-scanner
		$param = $func->get_param('$'.$varname);
		if($param === null)
			return $this->get_unknown();
		$mtype = $param->get_mtype();
		if($mtype->is_unknown() || $mtype->is_multiple())
			return $this->get_unknown();
		$types = $mtype->get_types();
		return new PC_Obj_Variable('',$types[0]);
	}
	
	/**
	 * Puts the variable with given name from global scope into the current one
	 * 
	 * @param string $name the variable-name
	 */
	public function do_global($name)
	{
		if(isset($this->vars[PC_Obj_Variable::SCOPE_GLOBAL][$name]))
			$val = clone $this->vars[PC_Obj_Variable::SCOPE_GLOBAL][$name];
		else
			$val = $this->get_unknown();
		$this->set_var($this->get_unknown($name),$val);
	}
	
	/**
	 * Starts the given class
	 */
	private function start_class($name)
	{
		$this->scope = $name;
	}

	/**
	 * Ends the current class
	 */
	public function end_class()
	{
		$this->scope = PC_Obj_Variable::SCOPE_GLOBAL;
	}
	
	/**
	 * Starts the given function
	 */
	private function start_function($name)
	{
		if($this->scope != PC_Obj_Variable::SCOPE_GLOBAL)
			$this->scope .= '::'.$name;
		else
			$this->scope = $name;
	}
	
	/**
	 * Ends the current function
	 */
	public function end_function()
	{
		if(($pos = strpos($this->scope,'::')) !== false)
			$this->scope = substr($this->scope,0,$pos);
		else
			$this->scope = PC_Obj_Variable::SCOPE_GLOBAL;
	}
	
	/**
	 * Starts a loop
	 */
	private function start_loop()
	{
		array_push($this->layers,array());
		$this->loopdepth++;
	}
	
	/**
	 * Ends a loop
	 */
	public function end_loop()
	{
		assert($this->loopdepth > 0);
		$this->loopdepth--;
		$this->perform_pending_changes();
	}
	
	/**
	 * Starts a condition
	 */
	private function start_cond()
	{
		array_push($this->layers,array());
		$this->conddepth++;
	}
	
	/**
	 * Ends a condition
	 */
	public function end_cond()
	{
		assert($this->conddepth > 0);
		$this->conddepth--;
		$this->perform_pending_changes();
	}
	
	/**
	 * Performs the required actions when leaving a loop/condition
	 */
	private function perform_pending_changes()
	{
		$changes = array_pop($this->layers);
		foreach($changes as $name => $var)
		{
			$curvar = $this->vars[$this->scope][$name];
			/* @var $var PC_Obj_Variable */
			// if the variable was created in the condition/loop or the type has changed in it, we don't
			// know the type behind the condition/loop. since we don't know wether the cond/loop is executed
			if($var === null || !$curvar->get_type()->equals($var->get_type()))
				$curvar->set_type(new PC_Obj_Type(PC_Obj_Type::UNKNOWN));
			// if the variable was known before and the value changed, clear the value
			else if($var !== null && $curvar->get_type()->get_value() !== $var->get_type()->get_value())
				$curvar->get_type()->set_value(null);
		}
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return
	 */
	public function get_scope_part($part)
	{
		return new PC_Obj_Variable(
			'',new PC_Obj_Type(PC_Obj_Type::STRING,$this->get_scope_part_name($part))
		);
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return
	 */
	private function get_scope_part_name($part)
	{
		$str = '';
		switch($part)
		{
			case T_METHOD_C:
			case T_FUNC_C:
				if($this->scope != PC_Obj_Variable::SCOPE_GLOBAL)
				{
					if(($pos = strpos($this->scope,'::')) !== false)
						$str = substr($this->scope,$pos + 2);
					else
						$str = $this->scope;
				}
				break;
			case T_CLASS_C:
				if(($pos = strpos($this->scope,'::')) !== false)
					$str = substr($this->scope,0,$pos);
				break;
		}
		return $str;
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
		$ctype = $classname->get_type();
		if($ctype->get_type() != PC_Obj_Type::STRING || $ctype->get_value() === null)
			return $this->get_unknown();
		
		$cname = $ctype->get_value();
		$class = $this->types->get_class($cname);
		if($class === null)
			return $this->get_unknown();
		$const = $class->get_constant($constname);
		if($const === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$const->get_type());
	}
	
	/**
	 * Access the given field of given class
	 * 
	 * @param PC_Obj_Variable $class the class-name
	 * @param PC_Obj_Variable $field the field (as variable)
	 * @return PC_Obj_Variable the result
	 */
	public function handle_field_access($class,$field)
	{
		$ctype = $class->get_type();
		$fname = $field->get_name();
		if($ctype->get_type() != PC_Obj_Type::STRING || $ctype->get_value() === null)
			return $this->get_unknown();
		$classobj = $this->types->get_class($ctype->get_value());
		if($classobj === null)
			return $this->get_unknown();
		$fieldobj = $classobj->get_field($fname);
		if($fieldobj === null)
			return $this->get_unknown();
		return new PC_Obj_Variable('',$fieldobj->get_type());
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
		// if we don't know the variable-type, we can't do anything; additionally, better do nothing
		// if its no array.
		// note that null as value is okay because it might be an empty array
		$t = $var->get_type();
		if($t->is_unknown() || $t->get_type() != PC_Obj_Type::TARRAY)
			return $this->get_unknown();
		// if we don't know the offset, we can't do anything, either
		if($offset !== null && ($offset->get_type()->is_unknown() || $offset->get_type()->get_value() === null))
			return $this->get_unknown();
		
		// PC_Obj_Variable will do the rest for us; simply access the offset
		return $var->array_offset($offset !== null ? $offset->get_type()->get_value() : null);
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
		if($this->loopdepth > 0)
			return new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$e->get_type())));
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
		$this->set_var($var,$res);
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
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		// if we're in a loop, don't try to provide the value since we don't know how often it is done
		if($this->loopdepth > 0)
			return new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$t1,$t2)));
		// if we don't know one of the types or values, try to determine the type by the operator
		if($t1->is_unknown() || $t2->is_unknown() || $t1->get_value() === null || $t2->get_value() === null)
			return new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$t1,$t2)));
		// if we have an array-operation, check if we know all elements
		if($t1->get_type() == PC_Obj_Type::TARRAY && $t2->get_type() == PC_Obj_Type::TARRAY)
		{
			// if not, we know at least, that its an array
			if($t1->is_array_unknown() || $t2->is_array_unknown())
				return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::TARRAY));
		}
		// type and value is known, therefore calculate the result
		$res = 0;
		$rval = $t2->get_value_for_eval();
		// prevent division-by-zero error
		if($op == '/' && $rval == 0)
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::INT));
		eval('$res = '.$t1->get_value_for_eval().' '.$op.' '.$rval.';');
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
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		$t3 = $e3->get_type();
		// don't try to evalulate $e1 in loops or if its unknown
		if($this->loopdepth > 0 || $t1->is_unknown() || $t1->get_value() === null)
		{
			// if the type is the same, we know the result-type
			if($t2->get_type() == $t3->get_type())
				return new PC_Obj_Variable('',new PC_Obj_Type($t2->get_type()));
			return $this->get_unknown();
		}
		// type and value is known, so we can evalulate the result
		$res = false;
		eval('$res = '.$t1->get_value_for_eval().';');
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
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		// if we don't know one of the types or values, try to determine the type by the operator
		// if we're in a loop, do that, too.
		if($this->loopdepth > 0 || $t1->is_unknown() || $t2->is_unknown() || $t1->get_value() === null
				|| $t2->get_value() === null)
			return new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$t1,$t2)));
		// if we have an array-operation, just return bool, because we would have to do it ourself
		// and I think its not worth the effort.
		if($t1->get_type() == PC_Obj_Type::TARRAY && $t2->get_type() == PC_Obj_Type::TARRAY)
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL));
		
		$val = false;
		switch($op)
		{
			// its not a good idea to use eval in this case because it might change the type
			case '===':
				$val = $t1->get_value_for_use() === $t2->get_value_for_use();
				break;
			case '!==':
				$val = $t1->get_value_for_use() !== $t2->get_value_for_use();
				break;
			
			case '==':
			case '!=':
			case '<':
			case '>':
			case '<=':
			case '>=':
				eval('$val = '.$t1->get_value_for_eval().' '.$op.' '.$t2->get_value_for_eval().';');
				break;
		}
		return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL,$val));
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
		$type = $var->get_type();
		// in loops always by op
		if($this->loopdepth > 0 || $type->is_unknown() || $type->get_value() === null)
			$res = new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$type)));
		else
		{
			$res = 0;
			eval('$res = '.$type->get_value_for_eval().$op.'1;');
			$res = $this->get_type_from_php($res);
		}
		return $this->set_var($var,$res);
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
		$clone = clone $var;
		$type = $var->get_type();
		// in loops always by op
		if($this->loopdepth > 0 || $type->is_unknown() || $type->get_value() === null)
			$res = new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$type)));
		else
		{
			$res = 0;
			eval('$res = '.$type->get_value_for_eval().$op.'1;');
			$res = $this->get_type_from_php($res);
		}
		$this->set_var($var,$res);
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
		// unset casts to null. in this case we don't really know the value
		// object-cast make no sense here, I think
		if($cast == 'unset' || $cast == 'object')
			return $this->get_unknown();
		
		$t = $e->get_type();
		// if we don't know the type or value, just provide the type; in loops as well
		if($this->loopdepth > 0 || $t->is_unknown() || $t->get_value() === null)
			return new PC_Obj_Variable('',PC_Obj_Type::get_type_by_name($cast));
		
		// we know the value, so perform a cast
		$res = 0;
		eval('$res = ('.$cast.')'.$t->get_value_for_eval().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles the instanceof-operator
	 * 
	 * @param PC_Obj_Variable $e the expression
	 * @param PC_Obj_Variable $name the name of the class
	 */
	public function handle_instanceof($e,$name)
	{
		// if we're in a loop or the name is not a string, give up
		if($this->loopdepth > 0 || $name->get_type()->get_type() != PC_Obj_Type::STRING)
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL));
		
		// if we don't know the type or its class we can't say wether its a superclass
		$name = $name->get_type()->get_value();
		$type = $e->get_type();
		if($type->is_unknown() || $type->get_type() != PC_Obj_Type::OBJECT || $type->get_class() == '')
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL));
		
		// class-name equal?
		if(strcasecmp($type->get_class(),$name) == 0)
				return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL,true));
		
		// if the class is unknown we can't say more
		$class = $this->types->get_class($type->get_class());
		if($class === null)
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL));
		
		// check super-classes
		$super = $class->get_super_class();
		while($super != '')
		{
			if(strcasecmp($super,$name) == 0)
				return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL,true));
			$superobj = $this->types->get_class($super);
			if($superobj === null)
				break;
			$super = $superobj->get_super_class();
		}
		
		// check interfaces
		if($this->is_instof_interface($class->get_interfaces(),$name))
			return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL,true));
		return new PC_Obj_Variable('',new PC_Obj_Type(PC_Obj_Type::BOOL,false));
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
				case T_FUNCTION:
					$this->start_function($this->get_type_name());
					break;
				case T_CLASS:
					$this->start_class($this->get_type_name());
					break;
				case T_FOR:
				case T_FOREACH:
				case T_WHILE:
				case T_DO:
					$this->start_loop();
					break;
				case T_IF:
				case T_SWITCH:
				case T_TRY:
					$this->start_cond();
					break;
			}
		}
		
		return parent::advance($parser);
	}
	
	/**
	 * @return string the classname of the current scope
	 */
	private function get_class()
	{
		if(($pos = strpos($this->scope,'::')) !== false)
			return substr($this->scope,0,$pos);
		return '';
	}
	
	/**
	 * @return string the function-name of the current scope
	 */
	private function get_func()
	{
		if(($pos = strpos($this->scope,'::')) !== false)
			return substr($this->scope,$pos + 2);
		return $this->scope;
	}
	
	/**
	 * @param string $name the var-name
	 * @return PC_Obj_Variable a variable with given name and unknown type
	 */
	private function get_unknown($name = '')
	{
		return new PC_Obj_Variable($name,new PC_Obj_Type(PC_Obj_Type::UNKNOWN));
	}
	
	private function get_type_name()
	{
		for($i = $this->pos + 1; $i < $this->N; $i++)
		{
			if($this->tokens[$i][0] == T_STRING)
				return $this->tokens[$i][1];
		}
		return null;
	}
}
?>