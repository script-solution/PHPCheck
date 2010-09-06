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
				$cname = $this->get_scope_part_name(T_CLASS_C);
				$classobj = $this->types->get_class($cname);
				if($classobj === null || $classobj->get_super_class() == '')
					return $this->get_unknown();
				$cname = $classobj->get_super_class();
				// if we're in a static method, the call is always static
				$curfname = $this->get_scope_part_name(T_FUNC_C);
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
				$cname = $this->get_scope_part_name(T_CLASS_C);
				// self is static if its not a constructor-call
				$static = strcasecmp($fname,'__construct') != 0 && strcasecmp($fname,$cname) != 0;
			}
			$call->set_class($cname);
		}
		
		// clone because it might be a variable
		foreach($args as $arg)
			$call->add_argument(clone $arg->get_type());
		$call->set_static($static);
		$this->calls[] = $call;
		
		// if its a constructor we know the type directly
		if(strcasecmp($fname,'__construct') == 0 || strcasecmp($fname,$cname) == 0)
			return PC_Obj_Variable::create_object($cname);
		// get function-object and determine return-type
		$funcobj = null;
		if($class !== null)
		{
			$classobj = $this->types->get_class($cname);
			if($classobj === null)
			{
				// TODO we should do that in the type-container!
				$classobj = PC_DAO::get_classes()->get_by_name($cname,PC_Project::PHPREF_ID);
				if($classobj === null)
					return $this->get_unknown();
			}
			$funcobj = $classobj->get_method($fname);
		}
		else
		{
			$funcobj = $this->types->get_function($fname);
			// TODO we should do that in the type-container!
			if($funcobj === null)
				$funcobj = PC_DAO::get_functions()->get_by_name($fname,PC_Project::PHPREF_ID);
		}
		
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
				$objt = PC_Obj_MultiType::create_object($classname);
		}
		else
			$objt = $obj->get_type();
		foreach($chain as $access)
		{
			// if we don't know the class-name or its no object, stop here
			$classname = $objt->get_classname();
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
					return $this->get_unknown();
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
			return PC_Obj_Variable::create_object($this->get_scope_part_name(T_CLASS_C));
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
		if(count($this->layers) > 0)
		{
			if($varname)
			{
				$layer = &$this->layers[count($this->layers) - 1];
				$blockno = $layer['blockno'];
				if(!isset($layer['vars'][$blockno][$varname]))
				{
					// don't use null because isset() is false if the value is null
					$clone = isset($this->vars[$this->scope][$varname]) ? clone $var : 0;
					$layer['vars'][$blockno][$varname] = $clone;
				}
			}
			else
			{
				// if its an array-element, simply set it to unknown
				$var->set_type(new PC_Obj_MultiType());
				return $value;
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
		return new PC_Obj_Variable('',$param->get_mtype());
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
		array_push($this->layers,array(
			'blockno' => 0,
			'haseelse' => false,
			'elseifs' => 0,
			'vars' => array(array())
		));
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
	 * 
	 * @param bool $newblock wether a new block is opened in the current layer
	 * @param bool $is_else wether an T_ELSE opened this block
	 */
	private function start_cond($newblock = false,$is_else = false)
	{
		if($newblock)
		{
			$layer = &$this->layers[count($this->layers) - 1];
			$layer['haselse'] = $is_else;
			$layer['blockno']++;
			$layer['vars'][] = array();
		}
		else
		{
			array_push($this->layers,array(
				'blockno' => 0,
				'elseifs' => 0,
				'haselse' => false,
				'vars' => array(array())
			));
			$this->conddepth++;
		}
	}
	
	/**
	 * Ends a condition
	 */
	public function end_cond()
	{
		assert($this->conddepth > 0);
		if($this->layers[count($this->layers) - 1]['elseifs']-- == 0)
		{
			$this->conddepth--;
			$this->perform_pending_changes();
		}
	}
	
	/**
	 * Performs the required actions when leaving a loop/condition
	 */
	private function perform_pending_changes()
	{
		$layer = array_pop($this->layers);
		// if there is only one block (loops, if without else)
		if(count($layer['vars']) == 1)
		{
			// its never present in all blocks here since we never have an else-block
			foreach($layer['vars'][0] as $name => $var)
				$this->change_var($layer,$name,$var,false);
		}
		else
		{
			// otherwise there were multiple blocks (if-elseif-else, ...)
			// we start with the variables in the first block; vars that are not present there, will
			// be added later
			$changed = array();
			foreach($layer['vars'] as $blockno => $vars)
			{
				foreach($vars as $name => $var)
				{
					if(!isset($changed[$name]))
					{
						// check if the variable is present in all blocks. this is not the case if we have no
						// else-block or if has not been assigned in at least one block
						$present = false;
						// we need to check this only in the first block, since if we're in the second block
						// and don't have changed this var yet (see isset above), it is at least not present
						// in the first block.
						if($blockno == 0 && $layer['haselse'])
						{
							$present = true;
							for($i = 1; $i <= $layer['blockno']; $i++)
							{
								if(!isset($layer['vars'][$i][$name]))
								{
									$present = false;
									break;
								}
							}
						}
						$this->change_var($layer,$name,$var,$present);
						$changed[$name] = true;
					}
				}
			}
		}
	}
	
	/**
	 * Changes the variable with given name in the current scope
	 * 
	 * @param array $layer the current layer
	 * @param string $name the var-name
	 * @param PC_Obj_Variable $backup the backup (0 if not present before the layer)
	 * @param bool $present wether its present in all blocks in this layer
	 */
	private function change_var($layer,$name,$backup,$present)
	{
		// if its present in all blocks, merge the types
		if($present)
		{
			// start with the type in scope; thats the one from the last block
			$mtype = $this->vars[$this->scope][$name]->get_type();
			// don't include the first block since thats the backup from the previous layer
			for($i = 1; $i <= $layer['blockno']; $i++)
				$mtype->merge($layer['vars'][$i][$name]->get_type());
			// note that this may discard the old value, if the variable was present
			$this->vars[$this->scope][$name] = new PC_Obj_Variable(
				$name,$mtype,$this->get_func(),$this->get_class()
			);
		}
		// if it was present before, we know that it is either the old or one of the new ones
		else if($backup !== 0)
		{
			$mtype = $this->vars[$this->scope][$name]->get_type();
			for($i = 0; $i <= $layer['blockno']; $i++)
			{
				if(isset($layer['vars'][$i][$name]))
					$mtype->merge($layer['vars'][$i][$name]->get_type());
			}
		}
		// otherwise the type is unknown
		else
			$this->vars[$this->scope][$name]->set_type(new PC_Obj_MultiType());
		
		// if there is a previous layer and the var is not known there in the last block, put
		// the first backup from this block in it. because this is the previous value for the previous
		// block, if it hasn't been assigned there
		if(count($this->layers) > 0)
		{
			$prevlayer = &$this->layers[count($this->layers) - 1];
			if(!isset($prevlayer['vars'][$prevlayer['blockno']][$name]))
				$prevlayer['vars'][$prevlayer['blockno']][$name] = $layer['vars'][0][$name];
		}
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return PC_Obj_Variable the scope-part as variable
	 */
	public function get_scope_part($part)
	{
		return PC_Obj_Variable::create_string($this->get_scope_part_name($part));
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return string the scope-part-name
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
	 * Access the given field of given class
	 * 
	 * @param PC_Obj_Variable $class the class-name
	 * @param PC_Obj_Variable $field the field (as variable)
	 * @return PC_Obj_Variable the result
	 */
	public function handle_field_access($class,$field)
	{
		$cname = $class->get_type()->get_string();
		$fname = $field->get_name();
		if($cname === null)
			return $this->get_unknown();
		$classobj = $this->types->get_class($cname);
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
		if($this->loopdepth > 0)
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
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		$t3 = $e3->get_type();
		// don't try to evalulate $e1 in loops or if its unknown
		if($this->loopdepth > 0 || $t1->is_val_unknown())
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
		$t1 = $e1->get_type();
		$t2 = $e2->get_type();
		// if we don't know one of the types or values, try to determine the type by the operator
		// if we're in a loop, do that, too.
		if($this->loopdepth > 0 || $t1->is_val_unknown() || $t2->is_val_unknown())
			return $this->get_type_from_op($op,$t1,$t2);
		// if we have an array-operation, just return bool, because we would have to do it ourself
		// and I think its not worth the effort.
		if($t1->get_first()->get_type() == PC_Obj_Type::TARRAY &&
				$t2->get_first()->get_type() == PC_Obj_Type::TARRAY)
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
		$type = $var->get_type();
		// in loops always by op
		if($this->loopdepth > 0 || $type->is_val_unknown())
			$res = $this->get_type_from_op($op,$type);
		else
		{
			$res = 0;
			eval('$res = '.$type->get_first()->get_value_for_eval().$op.'1;');
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
		if($this->loopdepth > 0 || $type->is_val_unknown())
			$res = $this->get_type_from_op($op,$type);
		else
		{
			$res = 0;
			eval('$res = '.$type->get_first()->get_value_for_eval().$op.'1;');
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
		if($this->loopdepth > 0 || $t->is_val_unknown())
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
	 */
	public function handle_instanceof($e,$name)
	{
		$name = $name->get_type()->get_string();
		// if we're in a loop or the name is not a string, give up
		if($this->loopdepth > 0 || $name === null)
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
			//$this->debug(array($this->vars,$this->conddepth,$this->lastWasElse,$this->layers));
			switch($type)
			{
				case T_COMMENT:
				case self::$T_DOC_COMMENT:
					$wascomment = true;
					break;
				
				case T_FUNCTION:
					$this->start_function($this->get_type_name());
					break;
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
						$this->layers[count($this->layers) - 1]['elseifs']++;
						$this->layers[count($this->layers) - 1]['haselse'] = false;
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
				if(isset($this->vars[$this->scope][$matches[1]]))
				{
					// ok, determine type and set it
					$type = PC_Obj_MultiType::get_type_by_name($matches[2]);
					$this->vars[$this->scope][$matches[1]]->set_type($type);
				}
			}
			$this->lastCheckComment = $this->lastComment;
		}
		return $res;
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
		return new PC_Obj_Variable($name,new PC_Obj_MultiType());
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