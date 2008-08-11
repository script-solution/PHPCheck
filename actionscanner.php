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
	 */
	public function scan_file($file,$funcs,$classes)
	{
		$this->file = $file;
		$this->scan(FWS_FileUtils::read($file),$funcs,$classes);
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 * @param array $funcs the known functions
	 * @param array $classes the known classes
	 */
	public function scan($source,$funcs,$classes)
	{
		$this->funcs = $funcs;
		$this->classes = $classes;
		
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
							if(($dp = strpos($this->scope,'::')) !== false)
							{
								// the end of methods means: 1 bracket left
								if($curlycount == 1)
								{
									// restore class scope
									$this->scope = substr($this->scope,0,$dp);
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
							$this->vars[$this->scope][$str] = $res;
					}
					// handle instantiations (for "return new ..." or simply "new ...")
					else if($t == T_NEW)
						$this->_handle_new();
					// detect function-calls
					else if($t == T_STRING)
						$this->_handle_func_call();
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
						$this->scope .= '::'.$str;
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
	 * Determines the type of the given variable in the current scope
	 *
	 * @param string $name the variable-name
	 * @return PC_Type the type or null if not found
	 */
	private function _get_var_type($name)
	{
		// known variable
		if(isset($this->vars[$this->scope][$name]))
			return $this->vars[$this->scope][$name];
		
		// handle $this
		if($name == '$this' && ($dp = FWS_String::strpos($this->scope,'::')) !== false)
			return new PC_Type(PC_Type::OBJECT,FWS_String::substr($this->scope,0,$dp));
		
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
		
		// assignment?
		if($t == '=')
		{
			$this->_skip_rubbish();
			
			// TODO this section does not take care of e.g. "2 + 1.4" or similar stuff.
			// we just take the first token and use it's type.
			list($t,$str,) = $this->tokens[$this->pos];
			switch($t)
			{
				case T_VARIABLE:
					// handle something like $a = $b = 2; or $a = $b->foo();
					$res = $this->_handle_variable($c + 1);
					break;
				
				// detect simple type assignments
				case T_CONSTANT_ENCAPSED_STRING:
					$res = PC_Type::$STRING;
					break;
				case T_DNUMBER:
					$res = PC_Type::$FLOAT;
					break;
				case T_LNUMBER:
					$res = PC_Type::$INT;
					break;
				case T_ARRAY:
					$res = PC_Type::$TARRAY;
					break;
				case T_STRING:
					// we'll get true and false here
					if(strcasecmp($str,'true') == 0 || strcasecmp($str,'false') == 0)
						$res = PC_Type::$BOOL;
					else
					{
						// this may be a function-call
						$res = $this->_handle_func_call();
						
						// if the call was no call, we have to walk one step back, so that we are in front of ';'
						if($res === null)
						{
							$res = PC_Type::$UNKNOWN;
							$this->pos = $oldpos;
						}
					}
					break;
				
				// handle instantiations
				case T_NEW:
					$res = $this->_handle_new();
					break;
				
				// by default we don't know the type
				default:
					$res = PC_Type::$UNKNOWN;
					break;
			}
			
			// if we are in an inner call, assign the result to the variable
			// this handles stuff like $a = $b = $c = 3;
			if($c > 0)
				$this->vars[$this->scope][$var] = $res;
		}
		// $foo->bar() ?
		else if($t == T_OBJECT_OPERATOR)
		{
			$this->_skip_rubbish();
			list($t,$str,$line) = $this->tokens[$this->pos++];
			
			// build function-call; use the type of the variable
			$call = new PC_Call($this->file,$line);
			$vartype = $this->_get_var_type($var);
			// is it an object?
			if($vartype !== null && $vartype->get_type() == PC_Type::OBJECT)
				$cname = $vartype->get_class();
			else
				// TODO how to handle that?
				$cname = '__UNKNOWN__';
			$call->set_class($cname);
			$call->set_function($str);
			
			// ensure that we are at '('
			$this->_skip_rubbish();
			$res = $this->_handle_func_call_rec($call);
			
			// just store the result if we are in a sub-call
			if($c == 0)
				$res = null;
		}
		// in all other cases we skip the variable
		else
		{
			$this->pos = $oldpos;
			$res = null;
		}
		
		// do not run to ';' in inner calls; the same when this was no var-def
		if($c == 0 && $this->pos != $oldpos)
			$this->_run_to_sep();
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
			$call->set_class($str);
			$call->set_function('__construct');
			
			// go to '('
			$this->pos++;
			$this->_skip_rubbish();
			
			$this->_handle_call_args($call);
			return new PC_Type(PC_Type::OBJECT,$str);
		}
		// variables are not supported here yet!
		
		return PC_Type::$UNKNOWN;
	}
	
	/**
	 * Assumes that the current token is T_STRING and the value the function-name / class-name.
	 * Returns the return-type of the call.
	 *
	 * @param boolean $runtosep wether the method should run to the ';' after the call
	 * @return PC_Type the return-type
	 */
	private function _handle_func_call($runtosep = true)
	{
		$call = $this->_handle_func_name();
		if($call !== null)
		{
			$rettype = $this->_handle_func_call_rec($call);
			// TODO if $rettype is null we have to handle class-constants, right?
			// if the function-call is in another function call, for example, we may not want to
			// walk to ';'
			if($runtosep)
				$this->_run_to_sep();
			return $rettype;
		}
		
		return PC_Type::$UNKNOWN;
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
				// TODO how to handle that?
				$cname = '__UNKNOWN__';
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
			// grab real func-name
			$this->_skip_rubbish();
			list($t,$str,$line) = $this->tokens[$this->pos++];
			if($t == T_STRING)
				$second = $str;
			// if this is e.g. a variable, we can't handle the func-call
			else
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
			$call->set_function($second);
			if(strcasecmp($first,'parent') == 0)
			{
				// TODO improve that!
				$first = '__UNKNOWN__';
				$cclass = $this->_get_var_type('$this');
				if($cclass !== null && $cclass->get_type() == PC_Type::OBJECT)
				{
					if(isset($this->classes[$cclass->get_class()]))
					{
						$cclassobj = $this->classes[$cclass->get_class()];
						if($cclassobj->get_super_class() !== null)
							$first = $cclassobj->get_super_class();
					}
				}
			}
			$call->set_class($first);
		}
		return $call;
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
		$arg_finished = false;
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
				switch($t)
				{
					case T_VARIABLE:
						if(!$arg_finished)
						{
							// do not run to ';'
							$arg = $this->_handle_variable(1);
						}
						break;
					case T_CONSTANT_ENCAPSED_STRING:
						if(!$arg_finished)
							$arg = PC_Type::$STRING;
						break;
					case T_DNUMBER:
						if(!$arg_finished)
							$arg = PC_Type::$FLOAT;
						break;
					case T_LNUMBER:
						if(!$arg_finished)
							$arg = PC_Type::$INT;
						break;
					case T_ARRAY:
						if(!$arg_finished)
							$arg = PC_Type::$TARRAY;
						break;
					case T_NEW:
						if(!$arg_finished)
							$arg = $this->_handle_new();
						break;
					case T_STRING:
						// we'll get true and false here
						if(strcasecmp($str,'true') == 0 || strcasecmp($str,'false') == 0)
							$arg = PC_Type::$BOOL;
						else
						{
							// do not walk to ';' here, because it is a sub-call
							$arg = $this->_handle_func_call(false);
							// TODO keep this here?
							if($arg === null)
								$this->pos--;
							$arg_finished = true;
						}
						break;
					case ',':
						// walk to next argument
						if($arg !== null)
							$call->add_argument($arg);
						$arg = null;
						$arg_finished = false;
						break;
				}
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
			else if(!$heredoc && $t != T_WHITESPACE && $t != T_COMMENT && $t != T_DOC_COMMENT)
				return;
		}
	}
	
	protected function get_print_vars()
	{
		return get_object_vars($this);
	}
}
?>