<?php
/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_ActionScanner extends FWS_Object
{
	// global scope
	const SCOPE_GLOBAL = '__global';
	
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
	 * The known functions
	 * TODO maybe we should put this somewhere else..
	 *
	 * @var array
	 */
	private $funcs = array();
	
	/**
	 * The known classes
	 * TODO maybe we should put this somewhere else..
	 *
	 * @var array
	 */
	private $classes = array();
	
	/**
	 * The known constants
	 * TODO maybe we should put this somewhere else..
	 *
	 * @var array
	 */
	private $constants = array();
	
	/**
	 * The current scope
	 *
	 * @var string
	 */
	private $scope = self::SCOPE_GLOBAL;
	
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
	 * @return array all found variables: <code>array(<scope> => array(<var1> => <type1>,...),...)</code>
	 */
	public function get_vars()
	{
		return $this->vars;
	}
	
	/**
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 * @param array $funcs the known functions
	 * @param array $classes the known classes
	 * @param array $constants the known constants
	 */
	public function scan_file($file,$funcs,$classes,$constants)
	{
		$this->file = $file;
		$this->scan(FWS_FileUtils::read($file),$funcs,$classes,$constants);
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 * @param array $funcs the known functions
	 * @param array $classes the known classes
	 * @param array $constants the known constants
	 */
	public function scan($source,$funcs,$classes,$constants)
	{
		$this->funcs = $funcs;
		$this->classes = $classes;
		$this->constants = $constants;
		
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
					if($t == T_VARIABLE)
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
						$state = self::ST_WAIT_FOR_FUNC_NAME;
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
						if(isset($this->funcs[$str]))
							$this->_add_parameters_to_local($this->funcs[$str]->get_params());
						
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
						if(isset($this->classes[$classname]))
						{
							$method = $this->classes[$classname]->get_method($str);
							if($method !== null)
								$this->_add_parameters_to_local($method->get_params());
						}
					}
					break;
				
				// we have found the name, skip arguments and wait for body
				// TODO we should add the arguments to local variables
				case self::ST_WAIT_FOR_METHOD_BODY:
					if($t === '{')
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
				$this->_set_local_var($param->get_name(),PC_Type::$UNKNOWN);
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
		
		$this->vars[$this->scope][$name] = $type;
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
			return $this->vars[$scope][$name];
		
		// handle $this
		if($name == '$this' && ($dp = FWS_String::strpos($scope,'::')) !== false)
			return new PC_Type(PC_Type::OBJECT,null,FWS_String::substr($scope,0,$dp));
		
		return null;
	}
	
	/**
	 * Handles a variable-defintion
	 *
	 * @param int $c the recursion-count
	 * @return PC_Type the type of the variable
	 */
	private function _handle_variable($c = 0)
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
		
		$res = PC_Type::$UNKNOWN;
		list($t,,) = $this->tokens[$this->pos++];
	
		// $foo->bar() or $foo->bar ?
		if($t == T_OBJECT_OPERATOR)
		{
			$type = $this->_get_var_type($var);
			$res = $this->_handle_object_access($type);
			
			// just store the result if we are in a sub-call
			if($c == 0)
				$res = null;
			
			// go to next interesting token. It might be a '='
			$this->_skip_rubbish();
			// just continue if we have a '='
			if(!isset($this->tokens[$this->pos]) || $this->tokens[$this->pos][0] != '=')
				return $res;
			
			list($t,,) = $this->tokens[$this->pos++];
		}
		
		// assignment?
		if($t == '=')
		{
			$this->_skip_rubbish();
			
			// determine type
			$casttype = null;
			$res = null;
			for(;$this->pos < $this->end;$this->pos++)
			{
				list($t,,) = $this->tokens[$this->pos];
				if($t == ';')
					break;
				
				$res = $this->_get_type_from_token($casttype,$res,$c);
			}
			
			// null = unknown
			if($res === null)
				$res = PC_Type::$UNKNOWN;
			
			// if we are in an inner call, assign the result to the variable
			// this handles stuff like $a = $b = $c = 3;
			if($c > 0)
				$this->_set_local_var($var,$res);
		}
		// handle array accesses
		else if($t == '[')
		{
			$type = $this->_get_var_type($var);
			$res = $this->_handle_array_access($type);
		}
		// in all other cases we skip the variable
		else
		{
			$this->pos = $oldpos;
			if($c > 0)
			{
				$res = $this->_get_var_type($var);
				// we don't want to get null here
				if($res === null)
					$res = PC_Type::$UNKNOWN;
			}
			else
				$res = null;
		}
		
		// do not run to ';' in inner calls; the same when this was no var-def
		//if($c == 0 && $this->pos != $oldpos)
		//	$this->_run_to_sep();
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
		// function-name or field-name
		$this->_skip_rubbish();
		$oldpos = $this->pos;
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
			// step back to field-name
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
			return PC_Type::$UNKNOWN;

		// determine type of $var->$fieldName
		$fieldtype = PC_Type::$UNKNOWN;
		if($vartype !== null && $vartype->get_type() == PC_Type::OBJECT &&
			isset($this->classes[$vartype->get_class()]))
		{
			$class = $this->classes[$vartype->get_class()];
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
			return PC_Type::$UNKNOWN;
		
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
		
		return PC_Type::$UNKNOWN;
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
		
		//return PC_Type::$UNKNOWN;
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
			$this->pos -= 2;
			if(isset($this->constants[$first]))
				return $this->constants[$first];
			
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
				
				if(isset($this->classes[$classname]))
				{
					$class = $this->classes[$classname];
					if($class->get_constant($second) !== null)
						return $class->get_constant($second);
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
					if(isset($this->classes[$cclass->get_class()]))
					{
						$cclassobj = $this->classes[$cclass->get_class()];
						if($cclassobj->get_super_class() !== null)
						{
							$first = $cclassobj->get_super_class();
							// by default we mark the call as not-static
							$call->set_static(false);
							// if the parent-method is known we set it static if that method is static
							// because parent::<method> may be static and not-static.
							if(isset($this->classes[$first]))
							{
								$method = $this->classes[$first]->get_method($second);
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
				$type = $this->_get_var_type($str,self::SCOPE_GLOBAL);
				$this->_set_local_var($str,$type === null ? PC_Type::$UNKNOWN : $type);
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
		$casttype = null;
		$curlies = 1;
		for(;$this->pos < $this->end;$this->pos++)
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
				if($t == ',')
				{
					// walk to next argument
					if($arg !== null)
						$call->add_argument($arg);
					$arg = null;
					$casttype = null;
				}
				else
					$arg = $this->_get_type_from_token($casttype,$arg);
			}
			// arguments finished..
			else if($curlies == 0)
			{
				if($arg !== null)
					$call->add_argument($arg);
				$this->calls[] = $call;
				// determine return type
				$rettype = PC_Utils::get_return_type(
					$this->funcs,$this->classes,$call->get_function(),$call->get_class()
				);
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
		$val = null;
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
					$key = $val;
					
					// reset
					$valchecked = false;
					$val = null;
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
					$val = null;
					$key = null;
					$arg = null;
					$casttype = null;
				}
				else
				{
					// determine value (will just be valid if it is a single token)
					if(!$valchecked)
					{
						$val = $this->_get_value_from_token();
						$valchecked = true;
					}
					else if($t != T_WHITESPACE && $t != T_COMMENT && $t != T_DOC_COMMENT)
						$val = null;
					
					// determine type
					$arg = $this->_get_type_from_token($casttype,$arg);
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
	 * @param PC_Type $var the array to access (has not to be an array)
	 * @return PC_Type the resulting type of the access
	 */
	private function _handle_array_access($var)
	{
		$arg = null;
		$casttype = null;
		$valchecked = false;
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == ']')
				break;
			
			// we allow just a single token atm
			if(!$valchecked)
			{
				$arg = $this->_get_type_from_token($casttype,$arg);
				if($arg !== null)
					$valchecked = true;
			}
			// otherwise the type is unknown
			else
				$arg = PC_Type::$UNKNOWN;
		}
		
		// access
		$subvar = null;
		if($var !== null && $var->get_type() == PC_Type::TARRAY && $arg !== null &&
				$arg->get_value() !== null)
		{
			if($arg->is_scalar())
				$subvar = $var->get_array_type($arg->get_value());
		}
		if($subvar === null)
			$subvar = PC_Type::$UNKNOWN;
		
		// handle multi-dimensional arrays
		$this->pos++;
		$this->_skip_rubbish();
		if($this->tokens[$this->pos][0] == '[')
			return $this->_handle_array_access($subvar);
		
		$this->pos--;
		return $subvar;
	}
	
	/**
	 * Determines the type from the current token
	 *
	 * @param PC_Type $casttype the casttype. will be set by the function
	 * @param PC_Type $arg the current type you've detected for the expression
	 * @param int $c the recursion-count for handle_variable (1 will be added)
	 * @return PC_Type the type
	 */
	private function _get_type_from_token(&$casttype,$arg,$c = 0)
	{
		list($t,$str,) = $this->tokens[$this->pos];
		switch($t)
		{
			// arithmetic
			case '+':
			case '-':
			case '/':
			case '*':
			case '%':
			case T_SL:
			case T_SR:
				$arg = $casttype === null ? PC_Type::$FLOAT : $casttype;
				break;
			
			// bool ops
			case '&':
			case '|':
			case '~':
			case '^':
				$arg = $casttype === null ? PC_Type::$INT : $casttype;
				break;
			
			// concatenation
			case '.':
				$arg = $casttype === null ? PC_Type::$STRING : $casttype;
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
				$arg = $casttype === null ? PC_Type::$BOOL : $casttype;
				break;
			
			// expr ? expr : expr
			case ':':
			case '?':
				// use the type from the next token
				// TODO is it ok to use the exact value here? at least it is one of the two options...
				$arg = null;
				break;
			
			case T_BOOL_CAST:
			case T_ARRAY_CAST:
			case T_DOUBLE_CAST:
			case T_INT_CAST:
			case T_OBJECT_CAST:
			case T_UNSET_CAST:
			case T_STRING_CAST:
				if($casttype === null)
					$casttype = $this->_get_type_from_cast($t);
				break;
			
			case T_VARIABLE:
				$res = $this->_handle_variable($c + 1);
				if($arg === null)
					$arg = $casttype === null ? $res : $casttype;
				break;
			
			case T_FUNC_C:
			case T_CLASS_C:
			case T_METHOD_C:
			case T_FILE:
				if($arg === null)
					$arg = $casttype === null ? PC_Type::$STRING : $casttype;
				break;
			
			case T_CONSTANT_ENCAPSED_STRING:
				if($arg === null)
					$arg = $casttype === null ? new PC_Type(PC_Type::STRING,$str) : $casttype;
				break;
			
			case T_DNUMBER:
				if($arg === null)
					$arg = $casttype === null ? new PC_Type(PC_Type::FLOAT,(double)$str) : $casttype;
				break;
			
			case T_LINE:
				if($arg === null)
					$arg = $casttype === null ? PC_Type::$INT : $casttype;
				break;
			
			case T_LNUMBER:
				if($arg === null)
					$arg = $casttype === null ? new PC_Type(PC_Type::INT,(int)$str) : $casttype;
				break;
			
			case T_ARRAY:
				$arg = $this->_handle_array_def();
				if($casttype !== null)
					$arg = $casttype;
				break;
			
			case T_NEW:
				if($arg === null)
				{
					$arg = $this->_handle_new();
					if($casttype !== null)
						$arg = $casttype;
				}
				break;
			
			case T_STRING:
				if($arg === null)
				{
					// true
					if(strcasecmp($str,'true') == 0)
					{
						$arg = $casttype === null ? new PC_Type(PC_Type::BOOL,true) : $casttype;
						break;
					}
					// false
					else if(strcasecmp($str,'false') == 0)
					{
						$arg = $casttype === null ? new PC_Type(PC_Type::BOOL,false) : $casttype;
						break;
					}
					// null
					else if(strcasecmp($str,'null') == 0)
					{
						$arg = $casttype === null ? PC_Type::$UNKNOWN : $casttype;
						break;
					}
				}
				
				// handle func call
				$res = $this->_handle_func_call();
				if($arg === null)
					$arg = $casttype === null ? $res : $casttype;
				break;
		}
		return $arg;
	}
	
	/**
	 * Determines the value of the current token, if possible
	 *
	 * @return mixed the value or null if not known
	 */
	private function _get_value_from_token()
	{
		list($t,$str,) = $this->tokens[$this->pos];
		$val = null;
		switch($t)
		{
			case T_DNUMBER:
			case T_LNUMBER:
			case T_CONSTANT_ENCAPSED_STRING:
				$val = $str;
				break;
			
			// look if we know the value of the variable
			case T_VARIABLE:
				$var = $this->_get_var_type($str);
				if($var !== null && $var->get_value() !== null)
					$val = $var->get_value();
				break;
			
			case T_STRING:
				if(strcasecmp($str,'true') == 0)
					$val = true;
				else if(strcasecmp($str,'false') == 0)
					$val = false;
				// we can skip null here since null can't be distinguished from "unknown"
				break;
		}
		return $val;
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
				return PC_Type::$BOOL;
			case T_ARRAY_CAST:
				return PC_Type::$TARRAY;
			case T_DOUBLE_CAST:
				return PC_Type::$FLOAT;
			case T_INT_CAST:
				return PC_Type::$INT;
			case T_STRING_CAST:
				return PC_Type::$STRING;
			
			case T_OBJECT_CAST:
			case T_UNSET_CAST:
			default:
				return PC_Type::$UNKNOWN;
		}
	}
	
	/**
	 * Runs until the current token is ';' and stops there
	 */
	private function _run_to_sep()
	{
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == ';')
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
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>