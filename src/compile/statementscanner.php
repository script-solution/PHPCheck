<?php
/**
 * Contains the statement-scanner
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Scans for statements in a given string or file. That means depending on the given type-container
 * it collects variable-assignments and function-calls.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Compile_StatementScanner extends FWS_Object
{
	// the states
	const ST_WAIT_FOR_VAR = 0;
	const ST_WAIT_FOR_CLASS = 1;
	const ST_WAIT_FOR_METHOD = 2;
	const ST_WAIT_FOR_METHOD_NAME = 3;
	const ST_WAIT_FOR_METHOD_BODY = 4;
	const ST_WAIT_FOR_FUNC_NAME = 5;

	/**
	 * The tokens of the file
	 *
	 * @var array
	 */
	private $tokens = array();
	
	/**
	 * The current position in the token-array
	 *
	 * @var int
	 */
	private $pos = 0;
	
	/**
	 * The token count
	 *
	 * @var int
	 */
	private $end = 0;
	
	/**
	 * The file we're scanning
	 *
	 * @var string
	 */
	private $file = null;
	
	/**
	 * The collected function-calls
	 *
	 * @var array
	 */
	private $calls = array();
	
	/**
	 * The collected variables
	 *
	 * @var array
	 */
	private $vars = array();
	
	/**
	 * The type-container
	 *
	 * @var PC_Compile_TypeContainer
	 */
	private $types;
	
	/**
	 * The current scope
	 *
	 * @var string
	 */
	private $scope = PC_Variable::SCOPE_GLOBAL;
	
	/**
	 * The scope-stack
	 *
	 * @var array
	 */
	private $scopestack = array();
	
	/**
	 * @return array all found function-calls
	 */
	public function get_calls()
	{
		return $this->calls;
	}
	
	/**
	 * @return array all found variables:
	 * 	<code>array(<scope> => array(<varname1> => <var1>,...),...)</code>
	 */
	public function get_vars()
	{
		return $this->vars;
	}
	
	/**
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 * @param PC_Compile_TypeContainer $types the type-container
	 */
	public function scan_file($file,$types)
	{
		$this->file = $file;
		$this->scan(FWS_FileUtils::read($file),$types);
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 * @param PC_Compile_TypeContainer $types the type-container
	 */
	public function scan($source,$types)
	{
		$this->types = $types;
		
		$state = self::ST_WAIT_FOR_VAR;
		$curlystack = array();
		$curlycount = 0;
		
		$this->tokens = PC_Utils::get_tokens($source);
		$this->end = count($this->tokens);
		for($this->pos = 0;$this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			
			switch($state)
			{
				case self::ST_WAIT_FOR_VAR:
					// count curlies, so we can find the end of the section
					if($curlycount > 0)
					{
						if($t === '{')
							$curlycount++;
						else if($t === '}')
						{
							$curlycount--;
							if(($dp = FWS_String::strpos($this->scope,'::')) !== false)
							{
								// the end of methods means: 1 bracket left
								if($curlycount == 1)
								{
									// restore class scope
									$this->scope = FWS_String::substr($this->scope,0,$dp);
									$state = self::ST_WAIT_FOR_METHOD;
								}
							}
							// the end of functions means: 0 brackets left
							else if($curlycount == 0)
							{
								// change scope
								$this->scope = array_pop($this->scopestack);
								$curlycount = array_pop($curlystack);
								$state = self::ST_WAIT_FOR_VAR;
							}
						}
					}
					
					// handle stuff in global scope, function scope or method scope
					
					// detect variable-definitions and store their type
					/*if($t == T_VARIABLE)
					{
						$res = $this->_handle_variable();
						if($res !== null)
							$this->_set_local_var($str,$res);
					}
					// handle 'global $v1,$v2,...,$vn;'
					else if($t == T_GLOBAL)
						$this->_handle_global();
					// handle instantiations (for "return new ..." or simply "new ...")
					else if($t == T_NEW)
						$this->_handle_new();
					// detect function-calls
					else if($t == T_STRING)
						$res = $this->_handle_func_call();
					// detect classes and functions (may be nested)
					else if($t == T_CLASS)
						$state = self::ST_WAIT_FOR_CLASS;
					else if($t == T_FUNCTION)
						$state = self::ST_WAIT_FOR_FUNC_NAME;*/
					
					if($t == T_CLASS)
						$state = self::ST_WAIT_FOR_CLASS;
					else if($t == T_FUNCTION)
						$state = self::ST_WAIT_FOR_FUNC_NAME;
					else
						$this->_get_expr_value();
					break;
				
				// we have found a function-def, so wait for func-name
				case self::ST_WAIT_FOR_FUNC_NAME:
					if($t == T_STRING)
					{
						// change scope
						array_push($curlystack,$curlycount);
						$curlycount = 0;
						array_push($this->scopestack,$this->scope);
						$this->scope = $str;
						
						// add function parameters, if known
						$func = $this->types->get_function($str);
						if($func)
							$this->_add_parameters_to_local($func->get_params());
						
						$state = self::ST_WAIT_FOR_METHOD_BODY;
					}
					break;
				
				// we have found a class-def, so wait for class-name
				case self::ST_WAIT_FOR_CLASS:
					if($t == T_STRING)
					{
						// change scope
						array_push($curlystack,$curlycount);
						$curlycount = 1;
						array_push($this->scopestack,$this->scope);
						$this->scope = $str;
						$state = self::ST_WAIT_FOR_METHOD;
					}
					break;
				
				// we are in a class and wait for a method
				case self::ST_WAIT_FOR_METHOD:
					if($t == T_FUNCTION)
						$state = self::ST_WAIT_FOR_METHOD_NAME;
					// end of class?
					else if($t === '}')
					{
						$curlycount--;
						if($curlycount == 0)
						{
							// change scope
							$state = self::ST_WAIT_FOR_VAR;
							$this->scope = array_pop($this->scopestack);
							$curlycount = array_pop($curlystack);
						}
					}
					break;
				
				// we have found a class, look for the name
				case self::ST_WAIT_FOR_METHOD_NAME:
					if($t == T_STRING)
					{
						$state = self::ST_WAIT_FOR_METHOD_BODY;
						$classname = $this->scope;
						$this->scope .= '::'.$str;
						
						// add method parameters, if known
						$class = $this->types->get_class($classname);
						if($class !== null)
						{
							$method = $class->get_method($str);
							if($method !== null)
								$this->_add_parameters_to_local($method->get_params());
						}
					}
					break;
				
				// we have found the name, skip arguments and wait for body
				case self::ST_WAIT_FOR_METHOD_BODY:
					// watch for abstract methods / interface methods
					if($t == ';')
					{
						// change scope
						$state = self::ST_WAIT_FOR_VAR;
						$this->scope = array_pop($this->scopestack);
						$curlycount = array_pop($curlystack);
					}
					else if($t === '{')
					{
						$curlycount++;
						$state = self::ST_WAIT_FOR_VAR;
					}
					break;
			}
		}
	}
	
	/**
	 * Adds the given parameters to local variables
	 *
	 * @param array $params the parameters
	 */
	private function _add_parameters_to_local($params)
	{
		foreach($params as $param)
		{
			$mtype = $param->get_mtype();
			if(!$mtype->is_multiple() && !$mtype->is_unknown())
			{
				$ts = $mtype->get_types();
				$this->_set_local_var($param->get_name(),$ts[0]);
			}
			else
				$this->_set_local_var($param->get_name(),new PC_Type(PC_Type::UNKNOWN));
		}
	}
	
	/**
	 * Sets the local variable with given name to given type
	 *
	 * @param string $name the variable-name
	 * @param PC_Type $type the type
	 */
	private function _set_local_var($name,$type)
	{
		if(!($type instanceof PC_Type))
			FWS_Helper::def_error('instance','type','PC_Type',$type);
		
		$class = '';
		if(($dp = FWS_String::strpos($this->scope,'::')) !== false)
		{
			$class = FWS_String::substr($this->scope,0,$dp);
			$func = FWS_String::substr($this->scope,$dp + 2);
		}
		else
			$func = $this->scope;
		
		$this->vars[$this->scope][$name] = new PC_Variable($name,clone $type,$func,$class);
	}
	
	/**
	 * Determines the type of the given variable in the current scope
	 *
	 * @param string $name the variable-name
	 * @param string $scope optional the scope (by default the current one)
	 * @return PC_Type the type or null if not found
	 */
	private function _get_var_type($name,$scope = null)
	{
		$scope = $scope === null ? $this->scope : $scope;
		
		// known variable
		if(isset($this->vars[$scope][$name]))
			return $this->vars[$scope][$name]->get_type();
		
		// handle $this
		if($name == '$this' && ($dp = FWS_String::strpos($scope,'::')) !== false)
			return new PC_Type(PC_Type::OBJECT,null,FWS_String::substr($scope,0,$dp));
		
		return null;
	}
	
	/**
	 * Handles a variable-defintion
	 *
	 * @return PC_Type the type of the variable
	 */
	private function _handle_variable()
	{
		// check if we're right here
		list($t,$str,) = $this->tokens[$this->pos];
		if($t != T_VARIABLE)
			return null;
		
		// store var-name and walk to next 'interesting' token
		$var = $str;
		$oldpos = $this->pos;
		$this->pos++;
		$this->_skip_rubbish();
		
		list($t,,) = $this->tokens[$this->pos];
		$type = $this->_get_var_type($var);
		$res = $type;
	
		// $foo->bar() or $foo->bar ?
		if($t == T_OBJECT_OPERATOR)
		{
			$this->pos++;
			$this->_skip_rubbish();
			$res = $this->_handle_object_access($res);
			$oldpos = $this->pos;
			// we don't support assignments for class-fields (methods are disallowed anyway)
			$var = null;
			$this->pos++;
			$this->_skip_rubbish();
			list($t,,) = $this->tokens[$this->pos];
		}
		
		// $array[...] ?
		$keys = array();
		$isarray = false;
		if($t == '[')
		{
			$isarray = true;
			$res = $this->_handle_array_access($keys,$res);
			$oldpos = $this->pos;
			$this->pos++;
			$this->_skip_rubbish();
		}
		
		// handle assignment
		list($t,,) = $this->tokens[$this->pos];
		if($var !== null && $t == '=')
		{
			// to next interesting token
			$this->pos++;
			$this->_skip_rubbish();
			
			$res = $this->_get_expr_value();
			if($res !== null)
			{
				if($isarray)
				{
					$stype = $type;
					$valid = true;
					for($i = 0,$n = count($keys);$i < $n - 1;$i++)
					{
						if($keys[$i] === null)
						{
							$valid = false;
							break;
						}
						$stype = $stype->get_array_type($keys[$i]->get_value());
						if($stype->get_type() != PC_Type::TARRAY)
							$stype->set_type(PC_Type::TARRAY);
					}
					// ignore invalid array-accesses
					if($stype !== null && $valid && $keys[$n - 1] !== null)
						$stype->set_array_type($keys[$n - 1]->get_value(),$res);
				}
				else
					$this->_set_local_var($var,$res);
			}
			else
				$this->_set_local_var($var,new PC_Type(PC_Type::UNKNOWN));
		}
		else
			$this->pos = $oldpos;
		
		return $res;
	}
	
	/**
	 * Handles a function-call or field-access on an object with given type. The method assumes
	 * that we are behind the object-operator.
	 *
	 * @param PC_Type $type the type of the object
	 * @return PC_Type the return-type (of the call or the field)
	 */
	private function _handle_object_access($type)
	{
		$oldpos = $this->pos;
		// function-name or field-name
		$this->_skip_rubbish();
		list(,$str,$line) = $this->tokens[$this->pos++];
		
		// ensure that we are at '('
		$this->_skip_rubbish();
		list($t,,) = $this->tokens[$this->pos];
		
		// $foo->funcName() ?
		if($t == '(')
		{
			// build function-call; use the type of the variable
			$call = new PC_Call($this->file,$line);
			// is it an object?
			if($type !== null && $type->get_type() == PC_Type::OBJECT)
				$cname = $type->get_class();
			else
				$cname = PC_Class::UNKNOWN;
			$call->set_class($cname);
			$call->set_function($str);
			
			$res = $this->_handle_func_call_rec($call);
		}
		// $foo->fieldName... ?
		else
		{
			$this->pos = $oldpos;
			$res = $this->_handle_class_field($type);
		}
		
		return $res;
	}
	
	/**
	 * Handles a class-field-access. Assumes that we are on the field-name.
	 * 
	 * @param PC_Type $vartype the found variable-type for this class-field-access
	 * @return PC_Type the return-type
	 */
	private function _handle_class_field($vartype)
	{
		$oldpos = $this->pos;
		list($t,$str,) = $this->tokens[$this->pos++];
		// just strings as field-names supported
		if($t != T_STRING)
			return new PC_Type(PC_Type::UNKNOWN);

		// determine type of $var->$fieldName
		$fieldtype = new PC_Type(PC_Type::UNKNOWN);
		if($vartype !== null && $vartype->get_type() == PC_Type::OBJECT &&
			($class = $this->types->get_class($vartype->get_class())) !== null)
		{
			$field = $class->get_field('$'.$str);
			if($field !== null)
				$fieldtype = $field->get_type();
		}
		
		$this->_skip_rubbish();
		list($t,,) = $this->tokens[$this->pos];
		
		// it is an object
		if($t == T_OBJECT_OPERATOR)
		{
			$this->pos++; // go to next token
			$res = $this->_handle_object_access($fieldtype);
		}
		// array access
		else if($t == '[')
		{
			$keys = array();
			$res = $this->_handle_array_access($keys,$fieldtype);
		}
		// simple access
		else
		{
			$res = $fieldtype;
			// we walked to far
			$this->pos = $oldpos;
		}
		
		return $res;
	}
	
	/**
	 * Handles a "new <class>(...)" and returns the the type
	 *
	 * @return PC_Type the type
	 */
	private function _handle_new()
	{
		list($t,,) = $this->tokens[$this->pos];
		if($t != T_NEW)
			return new PC_Type(PC_Type::UNKNOWN);
		
		// step to name
		$this->pos++;
		$this->_skip_rubbish();
		
		list($t,$str,$line) = $this->tokens[$this->pos];
		if($t == T_STRING)
		{
			// build constructor-call
			$call = new PC_Call($this->file,$line);
			$call->set_object_creation(true);
			if(strcasecmp($str,'self') == 0)
				$call->set_class($this->_get_var_type('$this')->get_class());
			else
				$call->set_class($str);
			$call->set_function('__construct');
			
			// go to '('
			$this->pos++;
			$this->_skip_rubbish();
			
			$this->_handle_call_args($call);
			return new PC_Type(PC_Type::OBJECT,null,$str);
		}
		// variables are not supported here yet!
		
		return new PC_Type(PC_Type::UNKNOWN);
	}
	
	/**
	 * Assumes that the current token is T_STRING and the value the function-name / class-name.
	 * Returns the return-type of the call.
	 *
	 * @return PC_Type the return-type
	 */
	private function _handle_func_call()
	{
		$call = $this->_handle_func_name();
		if($call !== null)
		{
			// no call, but type (class-constant or constant) ?
			if($call instanceof PC_Type)
				return $call;
			
			$rettype = $this->_handle_func_call_rec($call);
			// TODO if $rettype is null we have to handle class-constants, right?
			return $rettype;
		}
		
		//return new PC_Type(PC_Type::UNKNOWN);
		return null;
	}
	
	/**
	 * The recursive function that handles stuff like foo()->bar()->something()
	 *
	 * @param PC_Call $call the call
	 * @return PC_Type the type that the last function-call returns
	 */
	private function _handle_func_call_rec($call)
	{
		$rettype = $this->_handle_call_args($call);
		if($rettype === null)
			return null;
		$oldp = $this->pos;
		
		// step to the next token
		$this->pos++;
		$this->_skip_rubbish();
		
		list($t,,) = $this->tokens[$this->pos];
		// foo()->
		if($t == T_OBJECT_OPERATOR)
		{
			// step to name
			$this->pos++;
			$this->_skip_rubbish();
			
			list($t,$str,$line) = $this->tokens[$this->pos++];
			$subcall = new PC_Call($this->file,$line);
			// an object?
			if($rettype !== null && $rettype->get_type() == PC_Type::OBJECT)
				$cname = $rettype->get_class();
			else
				$cname = PC_Class::UNKNOWN;
			$subcall->set_class($cname);
			$subcall->set_function($str);
			
			// ensure that we are at '('
			$this->_skip_rubbish();
			$rettype = $this->_handle_func_call_rec($subcall);
		}
		// otherwise we simply walk back and ignore this call
		else
			$this->pos = $oldp;
		
		return $rettype;
	}
	
	/**
	 * Handles a function-name. This may be either a method or a function. The method assumes
	 * that the current position is the first name part. You'll get the scanned call or null if
	 * no call has been found. The position will be the '(' of the call after this method.
	 *
	 * @return PC_Call the call or null
	 */
	private function _handle_func_name()
	{
		$oldpos = $this->pos;
		
		// grab name
		list($t,$str,$line) = $this->tokens[$this->pos++];
		if($t != T_STRING)
			return null;
		$call = new PC_Call($this->file,$line);
		$first = $str;
		$second = '';
		
		$this->_skip_rubbish();
		
		// do we have a :: ?
		list($t,$str,$line) = $this->tokens[$this->pos++];
		if($t == T_DOUBLE_COLON)
		{
			// the call is static
			$call->set_static(true);
			
			// grab real func-name
			$this->_skip_rubbish();
			list($t,$str,$line) = $this->tokens[$this->pos++];
			if($t == T_STRING)
				$second = $str;
			// if this is e.g. a variable, we can't handle the func-call
			else
			{
				$this->pos = $oldpos;
				return null;
			}
		}
		// no func-call
		else if($t != '(')
		{
			$this->pos = $oldpos;
			$c = $this->types->get_constant($first);
			if($c !== null)
				return $c->get_type();
			return null;
		}
		// walk backwards to scan the token again
		else
			$this->pos--;
		
		// walk to '('
		$this->_skip_rubbish();
		
		// foo() or foo::bar() ?
		if(!$second)
			$call->set_function($first);
		else
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t != '(')
			{
				$this->pos--;
				
				// determine classname
				$classname = $first;
				if(strcasecmp($first,'self') == 0)
				{
					$dp = FWS_String::strpos($this->scope,'::');
					if($dp !== false)
						$classname = FWS_String::substr($this->scope,0,$dp);
				}
				
				$class = $this->types->get_class($classname);
				if($class !== null)
				{
					if($class->get_constant($second) !== null)
						return $class->get_constant($second)->get_type();
				}
				return null;
			}
			
			$call->set_function($second);
			
			// handle 'parent::'
			if(strcasecmp($first,'parent') == 0)
			{
				$first = PC_Class::UNKNOWN;
				$cclass = $this->_get_var_type('$this');
				if($cclass !== null && $cclass->get_type() == PC_Type::OBJECT)
				{
					$cclassobj = $this->types->get_class($cclass->get_class());
					if($cclassobj !== null)
					{
						if($cclassobj->get_super_class() !== null)
						{
							$first = $cclassobj->get_super_class();
							// by default we mark the call as not-static
							$call->set_static(false);
							// if the parent-method is known we set it static if that method is static
							// because parent::<method> may be static and not-static.
							$pclass = $this->types->get_class($first);
							if($pclass !== null)
							{
								$method = $pclass->get_method($second);
								if($method !== null)
									$call->set_static($method->is_static());
							}
						}
					}
				}
			}
			// handle 'self::'
			else if(strcasecmp($first,'self') == 0)
			{
				$selftype = $this->_get_var_type('$this');
				if($selftype !== null)
					$first = $selftype->get_class();
			}
			
			$call->set_class($first);
		}
		return $call;
	}
	
	/**
	 * Handles the global-keyword. The method assumes that we are at the global-keyword.
	 * It will stop on the ';'.
	 */
	private function _handle_global()
	{
		for($this->pos++;$this->pos < $this->end;$this->pos++)
		{
			$this->_skip_rubbish();
			list($t,$str,) = $this->tokens[$this->pos];
			if($t == ';')
				break;
			if($t == ',')
				continue;
			if($t == T_VARIABLE)
			{
				$type = $this->_get_var_type($str,PC_Variable::SCOPE_GLOBAL);
				$this->_set_local_var($str,$type === null ? new PC_Type(PC_Type::UNKNOWN) : $type);
			}
		}
	}
	
	/**
	 * Handles the arguments of a call. Will stop on the ')'.
	 *
	 * @param PC_Call $call the call
	 * @return PC_Type the return-type of the function that is called
	 */
	private function _handle_call_args($call)
	{
		list($t,,) = $this->tokens[$this->pos];
		if($t != '(')
			return null;
		
		$this->pos++;
		$arg = null;
		$curlies = 1;
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			
			// count curlies, so we know when we're done
			if($t == '(')
				$curlies++;
			else if($t == ')')
				$curlies--;
			else if($curlies == 1)
			{
				if($t == ',')
				{
					// walk to next argument
					if($arg !== null)
						$call->add_argument($arg);
					$arg = null;
				}
				else if($arg === null)
					$arg = $this->_get_expr_value();
			}
			
			// arguments finished..
			if($curlies == 0)
			{
				if($arg !== null)
					$call->add_argument($arg);
				$this->calls[] = $call;
				// determine return type
				$rettype = $this->_get_return_type($call->get_function(),$call->get_class());
				return $rettype;
			}
		}
		
		// there must be something wrong here..
		return null;
	}
	
	/**
	 * Handles a definition of an array. Assumes that we are on the T_ARRAY token
	 */
	private function _handle_array_def()
	{
		$type = new PC_Type(PC_Type::TARRAY);
		
		// go to '('
		$this->pos++;
		$this->_skip_rubbish();
		
		$numkey = 0;
		$key = null;
		$assoc = false;
		$curlies = 1;
		$casttype = null;
		$arg = null;
		$valchecked = false;
		for($this->pos++;$this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			
			// count curlies, so we know when we're done
			if($t == '(')
				$curlies++;
			else if($t == ')')
				$curlies--;
			
			// an argument? (we may have subcalls or similar...)
			if($curlies == 1)
			{
				if($t == T_DOUBLE_ARROW)
				{
					// save that we may have a valid key
					$assoc = true;
					$key = $arg !== null ? $arg->get_value() : null;
					
					// reset
					$valchecked = false;
					$arg = null;
					$casttype = null;
				}
				else if($t == ',')
				{
					// insert element
					if($assoc && $key !== null)
					{
						$type->set_array_type($key,$arg);
						if(FWS_Helper::is_integer($key))
							$numkey = $key + 1;
					}
					else if(!$assoc)
						$type->set_array_type($numkey++,$arg);
					
					// reset
					$valchecked = false;
					$assoc = false;
					$key = null;
					$arg = null;
					$casttype = null;
				}
				else
				{
					$valchecked = true;
					$tmp = $this->_get_expr_value();
					if($tmp !== null)
						$arg = $tmp;
				}
			}
			// end of array-def?
			else if($curlies == 0)
			{
				if($valchecked)
				{
					// insert element
					if($assoc && $key !== null)
					{
						$type->set_array_type($key,$arg);
						if(FWS_Helper::is_integer($key))
							$numkey = $key + 1;
					}
					else if(!$assoc)
						$type->set_array_type($numkey++,$arg);
				}
				break;
			}
		}
		
		return $type;
	}
	
	/**
	 * Handles an array-access ('$array[...]'). Assumes that we are on the '[' token
	 * 
	 * @param array $keys a list of keys that will be collected
	 * @param PC_Type $var the array to access (has not to be an array)
	 * @return PC_Type the resulting type of the access
	 */
	private function _handle_array_access(&$keys,$var)
	{
		$arg = null;
		$valchecked = false;
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == ']')
				break;
			
			// we allow just a single token atm
			if(!$valchecked)
			{
				$arg = $this->_get_expr_value();
				if($arg !== null)
					$valchecked = true;
			}
			// otherwise the type is unknown
			else
				$arg = new PC_Type(PC_Type::UNKNOWN);
		}
		
		// access
		$key = $valchecked ? null : new PC_Type(PC_Type::INT,$var === null ? 0 : $var->get_array_count());
		$subvar = null;
		if($var !== null && $var->get_type() == PC_Type::TARRAY && $arg !== null &&
				$arg->get_value() !== null)
		{
			if($arg->is_scalar())
			{
				$key = $arg;
				$subvar = $var->get_array_type($arg->get_value());
			}
		}
		$keys[] = $key;
		if($subvar === null)
			$subvar = new PC_Type(PC_Type::UNKNOWN);
		
		// handle multi-dimensional arrays
		$oldpos = $this->pos;
		$this->pos++;
		$this->_skip_rubbish();
		list($t,,) = $this->tokens[$this->pos];
		if($t == '[')
			return $this->_handle_array_access($keys,$subvar);
	
		// $array[...]->bar() or $array[...]->bar ?
		if($t == T_OBJECT_OPERATOR)
		{
			$this->pos++;
			$this->_skip_rubbish();
			return $this->_handle_object_access($subvar);
		}
		
		$this->pos = $oldpos;
		return $subvar;
	}
	
	/**
	 * Evaluates the expression at the current token.
	 *
	 * @param PC_Type $val the value (for recursive calls)
	 * @param int $oldpos the old position (for recursive calls)
	 * @param int $brcount the number of open braces (for recursive calls)
	 * @return PC_Type the type
	 */
	private function _get_expr_value($val = null,$oldpos = null,$brcount = 0)
	{
		if(!isset($this->tokens[$this->pos]))
			return $val;
		
		list($t,$str,) = $this->tokens[$this->pos];
		$coldpos = $this->pos;
		switch($t)
		{
			// the tokens which do a recursive call
			case '+':
			case '-':
			case '/':
			case '*':
			case '%':
			case T_SL:
			case T_SR:
			case '&':
			case '|':
			case '^':
			case '.':
			case '~':
			case T_IS_EQUAL:
			case T_IS_IDENTICAL:
			case T_IS_NOT_EQUAL:
			case T_IS_NOT_IDENTICAL:
			case T_IS_GREATER_OR_EQUAL:
			case T_IS_SMALLER_OR_EQUAL:
			case '>':
			case '<':
			case T_BOOL_CAST:
			case T_ARRAY_CAST:
			case T_DOUBLE_CAST:
			case T_INT_CAST:
			case T_OBJECT_CAST:
			case T_UNSET_CAST:
			case T_STRING_CAST:
			case T_FUNC_C:
			case T_CLASS_C:
			case T_METHOD_C:
			case T_FILE:
			case T_CONSTANT_ENCAPSED_STRING:
			case T_DNUMBER:
			case T_LINE:
			case T_LNUMBER:
			case '(':
			case '?':
				$this->pos++;
				$this->_skip_rubbish();
				break;
			
			case '"':
			case T_ARRAY:
			case T_NEW:
			case T_STRING:
			case T_VARIABLE:
				break;
			
			case ')':
				if($brcount > 0)
				{
					$this->pos++;
					$this->_skip_rubbish();
					break;
				}
				// fall through
			
			default:
				if($oldpos !== null)
					$this->pos = $oldpos;
				return $val;
		}

		$arg = null;
		switch($t)
		{
			// arithmetic and boolean ops
			case '+':
			case '-':
			case '/':
			case '*':
			case '%':
			case T_SL:
			case T_SR:
			case '&':
			case '|':
			case '^':
				$rop = $this->_get_expr_value(null,$coldpos,$brcount);
				// unary +/- ?
				if(($t == '+' || $t == '-') && $val === null)
				{
					eval('$num='.$t.$rop->get_value_as_number().';');
					if(is_float($num))
						$arg = new PC_Type(PC_Type::FLOAT,$num);
					else
						$arg = new PC_Type(PC_Type::INT,$num);
				}
				// reference operator
				else if($t == '&' && $val === null)
					$arg = new PC_Type(PC_Type::UNKNOWN);
				else
				{
					if($rop === null || $val === null)
						$rop = new PC_Type(PC_Type::UNKNOWN);
					else if(is_float($val->get_value_as_number()) || is_float($rop->get_value_as_number()))
						$arg = new PC_Type(PC_Type::FLOAT);
					else
						$arg = new PC_Type(PC_Type::INT);
				}
				break;
			
			case '(':
				// check last token
				for($p = $coldpos - 1;$p >= 0;$p--)
				{
					list($t,,) = $this->tokens[$p];
					if($t != T_WHITESPACE && $t != T_COMMENT && $t != T_DOC_COMMENT && $t != '@')
						break;
				}
				// if it was a keyword requires an open brace behind it we ignore this brace
				$t = $this->tokens[$p][0];
				switch($t)
				{
					case T_IF:
					case T_SWITCH:
					case T_WHILE:
					case T_FOR:
					case T_FOREACH:
					case T_CATCH:
					case T_ELSEIF:
					case T_LIST:
					case T_ISSET:
					case T_PRINT:
					case T_INCLUDE:
					case T_INCLUDE_ONCE:
					case T_REQUIRE:
					case T_REQUIRE_ONCE:
					case T_EVAL:
						// walk back to ensure that we don't miss a token
						$this->pos = $coldpos;
						break;
					
					default:
						$arg = $this->_get_expr_value(null,$coldpos,$brcount + 1);
						break;
				}
				break;
			
			case ')':
				$arg = $this->_get_expr_value($val,$coldpos,$brcount - 1);
				break;
			
			// concatenation
			case '.':
				$this->_get_expr_value(null,$coldpos,$brcount);
				$arg = new PC_Type(PC_Type::STRING);
				break;
			
			// unary
			case '~':
				$this->_get_expr_value(null,$coldpos,$brcount);
				$arg = new PC_Type(PC_Type::INT);
				break;
			
			// condition
			case T_IS_EQUAL:
			case T_IS_IDENTICAL:
			case T_IS_NOT_EQUAL:
			case T_IS_NOT_IDENTICAL:
			case T_IS_GREATER_OR_EQUAL:
			case T_IS_SMALLER_OR_EQUAL:
			case '>':
			case '<':
				$savepos = $this->pos;
				$arg = $this->_get_expr_value(null,$coldpos,$brcount);
				// hack to support conditions
				if($savepos == $this->pos)
					$arg = new PC_Type(PC_Type::BOOL);
				break;
			
			// expr ? expr : expr
			case '?':
				$first = $this->_get_expr_value(null,$coldpos,$brcount);
				$this->pos++;
				$this->_skip_rubbish();
				// skip ':'
				$coldpos = $this->pos;
				$this->pos++;
				$this->_skip_rubbish();
				$second = $this->_get_expr_value(null,$coldpos,$brcount);
				// we can't be sure about the value here
				$arg = new PC_Type($first->is_unknown() ? $second->get_type() : $first->get_type());
				break;
			
			// casts
			case T_BOOL_CAST:
			case T_ARRAY_CAST:
			case T_DOUBLE_CAST:
			case T_INT_CAST:
			case T_OBJECT_CAST:
			case T_UNSET_CAST:
			case T_STRING_CAST:
				$rop = $this->_get_expr_value(null,$coldpos,$brcount);
				$arg = $this->_get_type_from_cast($t);
				if(!$rop->is_unknown() && $rop->get_value() !== null)
				{
					eval('$arg->set_value('
						.($t == T_STRING_CAST ? '"\'".(' : '').$str.$rop->get_value_for_use()
						.($t == T_STRING_CAST ? ')."\'"' : '').');');
				}
				break;
			
			// variable-def or access
			case T_VARIABLE:
				$res = $this->_handle_variable();
				if($res === null)
					$res = new PC_Type(PC_Type::UNKNOWN);
				$coldpos = $this->pos;
				$this->pos++;
				$this->_skip_rubbish();
				$arg = $this->_get_expr_value($res,$coldpos,$brcount);
				break;
			
			case T_FUNC_C:
			case T_CLASS_C:
			case T_METHOD_C:
			case T_FILE:
				// TODO
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::STRING),$coldpos,$brcount);
				break;
			case T_LINE:
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::INT),$coldpos,$brcount);
				break;
			
			// var-strings
			case '"':
				$this->_run_to_token('"');
				$coldpos = $this->pos;
				$this->pos++;
				$this->_skip_rubbish();
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::STRING),$coldpos,$brcount);
				break;
			
			// plain types
			case T_CONSTANT_ENCAPSED_STRING:
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::STRING,$str),$coldpos,$brcount);
				break;
			case T_DNUMBER:
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::FLOAT,(double)$str),$coldpos,$brcount);
				break;
			case T_LNUMBER:
				$arg = $this->_get_expr_value(new PC_Type(PC_Type::INT,(int)$str),$coldpos,$brcount);
				break;
			
			case T_ARRAY:
				$arg = $this->_handle_array_def();
				break;
			
			case T_NEW:
				$arg = $this->_handle_new();
				break;
			
			// constant, class-constant, true, false, null or function-call
			case T_STRING:
				if(strcasecmp($str,'true') == 0 || strcasecmp($str,'false') == 0 ||
						strcasecmp($str,'null') == 0)
				{
					$this->pos++;
					$this->_skip_rubbish();
				}
				
				if(strcasecmp($str,'true') == 0)
					$arg = $this->_get_expr_value(new PC_Type(PC_Type::BOOL,true),$coldpos,$brcount);
				else if(strcasecmp($str,'false') == 0)
					$arg = $this->_get_expr_value(new PC_Type(PC_Type::BOOL,false),$coldpos,$brcount);
				else if(strcasecmp($str,'null') == 0)
					$arg = $this->_get_expr_value(new PC_Type(PC_Type::UNKNOWN),$coldpos,$brcount);
				// handle func call
				else
				{
					$res = $this->_handle_func_call();
					if($res === null)
						$res = new PC_Type(PC_Type::UNKNOWN);
					$coldpos = $this->pos;
					$this->pos++;
					$this->_skip_rubbish();
					$arg = $this->_get_expr_value($res,$coldpos,$brcount);
				}
				break;
		}
		return $arg;
	}
	
	/**
	 * Determines the type of the given cast
	 *
	 * @param int $cast the cast-type
	 * @return PC_Type the type-instance
	 */
	private function _get_type_from_cast($cast)
	{
		switch($cast)
		{
			case T_BOOL_CAST:
				return new PC_Type(PC_Type::BOOL);
			case T_ARRAY_CAST:
				return new PC_Type(PC_Type::TARRAY);
			case T_DOUBLE_CAST:
				return new PC_Type(PC_Type::FLOAT);
			case T_INT_CAST:
				return new PC_Type(PC_Type::INT);
			case T_STRING_CAST:
				return new PC_Type(PC_Type::STRING);
			
			case T_OBJECT_CAST:
			case T_UNSET_CAST:
			default:
				return new PC_Type(PC_Type::UNKNOWN);
		}
	}
	
	/**
	 * Runs until the current token is $token and stops there
	 * 
	 * @param string $token the token which causes a stop
	 */
	private function _run_to_token($token = ';')
	{
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == $token)
				return;
		}
	}
	
	/**
	 * Skips whitespace and comments. If there is nothing, the position will remain the same.
	 * Otherwise the position will be the first "not-rubbish"-token. That means you'll be always
	 * on the position you want to have.
	 */
	private function _skip_rubbish()
	{
		$heredoc = false;
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == T_START_HEREDOC)
				$heredoc = true;
			else if($t == T_END_HEREDOC)
				$heredoc = false;
			else if(!$heredoc && $t != T_WHITESPACE && $t != T_COMMENT && $t != T_DOC_COMMENT && $t != '@')
				return;
		}
	}
	
	/**
	 * Determines the return-type of the given function / class
	 *
	 * @param array $funcs an array of all known functions
	 * @param array $classes an array of all known classes
	 * @param string $function the function-name
	 * @param string $classname optional, the class-name
	 * @return PC_Type the type
	 */
	private function _get_return_type($function,$classname = '')
	{
		if(!$classname)
		{
			$func = $this->types->get_function($function);
			if($func !== null)
				return $func->get_return_type();
		}
		else
		{
			$class = $this->types->get_class($classname);
			if($class !== null)
			{
				$cfuncs = $class->get_methods();
				if(isset($cfuncs[$function]))
					return $cfuncs[$function]->get_return_type();
			}
		}
		return new PC_Type(PC_Type::UNKNOWN);
	}
	
	/**
	 * Prints the current slice (pos-3,...,pos+3) of the token-array
	 */
	private function _print_slice()
	{
		echo $this->pos.': '.FWS_Printer::to_string(
			array_slice($this->tokens,max(0,$this->pos - 20),40,true)
		);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>