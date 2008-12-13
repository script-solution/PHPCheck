<?php
/**
 * Contains the type-scanner-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Scans for types in a given string or file. That means classes, functions and constants will
 * be collected.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_TypeScanner extends FWS_Object
{
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
	 * The last phpdoc-comment
	 *
	 * @var string
	 */
	private $doc = null;
	
	/**
	 * The file we're scanning
	 *
	 * @var string
	 */
	private $file = null;
	
	/**
	 * The functions:
	 * <code>array(<name> => PC_Method,...)</code>
	 *
	 * @var array
	 */
	private $functions = array();
	
	/**
	 * The classes:
	 * <code>array(<name> => PC_Class,...)</code>
	 *
	 * @var array
	 */
	private $classes = array();
	
	/**
	 * The constants:
	 * <code>array(<name> => PC_Constant,...)</code>
	 *
	 * @var array
	 */
	private $constants = array();
	
	/**
	 * @return array the collected functions: <code>array(<name> => <obj>)</code>
	 */
	public function get_functions()
	{
		return $this->functions;
	}
	
	/**
	 * @return array the collected classes: <code>array(<name> => <obj>)</code>
	 */
	public function get_classes()
	{
		return $this->classes;
	}
	
	/**
	 * @return array the collected contants: <code>array(<name> => PC_Constant,...)</code>
	 */
	public function get_constants()
	{
		return $this->constants;
	}
	
	/**
	 * Scans the given file for functions and classes
	 *
	 * @param string $file the filename
	 */
	public function scan_file($file)
	{
		$this->file = $file;
		$this->scan(FWS_FileUtils::read($file));
	}
	
	/**
	 * Scans the given string for functions and classes
	 *
	 * @param string $source the string
	 */
	public function scan($source)
	{
		$this->doc = null;
		$this->tokens = PC_Utils::get_tokens($source);
		$this->end = count($this->tokens);
		for($this->pos = 0;$this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			
			// detect doc-comments
			if($t == T_DOC_COMMENT)
				$this->doc = $str;
			else
			{
				$this->_skip_rubbish();
				list($t,$str,) = $this->tokens[$this->pos];
				
				$res = false;
				if($t == T_STRING && strcasecmp($str,'define') == 0)
					$res = $this->_handle_define();
				if(!$res)
					$res = $this->_handle_function();
				if(!$res)
					$this->_handle_class();
			}
		}
	}
	
	/**
	 * Determines the type from the given token
	 *
	 * @param int|string $t the token
	 * @param string $str the token-value
	 * @return PC_Type the type
	 */
	private function _get_type_from_token($t,$str)
	{
		// TODO we have to do some expression evaluation here or at least a detection of arithmetic...
		switch($t)
		{
			case T_CONSTANT_ENCAPSED_STRING:
				return new PC_Type(PC_Type::STRING,$str);
			
			case T_STRING:
				if(strcasecmp($str,'true') == 0)
					return new PC_Type(PC_Type::BOOL,true);
				else if(strcasecmp($str,'false') == 0)
					return new PC_Type(PC_Type::BOOL,false);
				// constants
				else if(isset($this->constants[$str]))
					return new PC_Type($this->constants[$str]->get_type()->get_type());
				break;
				// TODO handle constants / func-calls

			case T_ARRAY:
				return new PC_Type(PC_Type::TARRAY);
			
			case T_DNUMBER:
				return new PC_Type(PC_Type::FLOAT,(double)$str);
			
			case T_LNUMBER:
				return new PC_Type(PC_Type::INT,(int)$str);
		}
		
		return new PC_Type(PC_Type::UNKNOWN);
	}
	
	/**
	 * Handles a constant-definition. The method assumes that the current token is a string with the
	 * value 'define'. If it is no valid define, the method restores the position.
	 *
	 * @return boolean true if a constant has been found.
	 */
	private function _handle_define()
	{
		$oldpos = $this->pos++;
		$this->_skip_rubbish();
		list($t,,$line) = $this->tokens[$this->pos++];
		if($t != '(')
		{
			$this->pos = $oldpos;
			return false;
		}
		
		// get constant name
		$this->_skip_rubbish();
		list($t,$str,) = $this->tokens[$this->pos++];
		$name = FWS_String::substr($str,1,-1);
		
		// skip stuff until ','
		$this->_skip_rubbish();
		// skip ','
		$this->pos++;
		// skip rubbish until value
		$this->_skip_rubbish();
		
		list($t,$str,) = $this->tokens[$this->pos++];
		$type = $this->_get_type_from_token($t,$str);
		
		$this->constants[$name] = new PC_Constant($this->file,$line,$name,$type);
		
		// skip rubbish until ')'
		$this->_skip_rubbish();
		// skip ')'
		$this->pos++;
		return true;
	}
	
	/**
	 * Handles a class-definition. If at the current token is no class, it returns false. Otherwise
	 * it reads the complete class.
	 *
	 * @return boolean true if a class has been read
	 */
	private function _handle_class()
	{
		// check for performance issues
		static $vtokens = null;
		if($vtokens === null)
			$vtokens = FWS_Array_Utils::get_fast_access(array(T_CLASS,T_INTERFACE,T_ABSTRACT,T_FINAL));
		list($t,,$line) = $this->tokens[$this->pos];
		if(!isset($vtokens[$t]))
			return false;
		
		$class = new PC_Class($this->file,$line);
		
		// save position just in case it is no function
		$oldpos = $this->pos;
		
		// scan "[abstract|final]? class"
		$finished = false;
		for(;!$finished && $this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			switch($t)
			{
				case '{':
					$finished = true;
					break;
				
				// name
				case T_INTERFACE:
					$class->set_interface(true);
					// fall through
				
				case T_CLASS:
					$this->pos++;
					$this->_skip_rubbish();
					list($t,$str,) = $this->tokens[$this->pos];
					$class->set_name($str);
					break;
				
				case T_EXTENDS:
					if(!$class->is_interface())
					{
						$this->pos++;
						$this->_skip_rubbish();
						list($t,$str,) = $this->tokens[$this->pos];
						$class->set_super_class($str);
						break;
					}
					// interfaces fall through
					
				case T_IMPLEMENTS:
					$this->pos++;
					$this->_skip_rubbish();
					for(;!$finished && $this->pos < $this->end;$this->pos++)
					{
						list($t,$str,) = $this->tokens[$this->pos];
						if($t == ',')
							continue;
						if($t == '{')
							$finished = true;
						else if($t == T_STRING)
							$class->add_interface($str);
					}
					break;
				
				// other modifier
				case T_ABSTRACT:
					$class->set_abstract(true);
					break;
				case T_FINAL:
					$class->set_final(true);
					break;
				
				case T_COMMENT:
				case T_WHITESPACE:
					// skip this shit ;)
					break;
				
				case T_DOC_COMMENT:
					$this->doc = $str;
					break;
				
				// in all other cases there is something wrong
				default:
					$this->pos = $oldpos;
					return false;
			}
		}
		
		$this->_skip_rubbish();
		// we are in the class now
		
		// search for functions
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			if($t == '}')
				break;
			
			if($t == T_DOC_COMMENT)
				$this->doc = $str;
			else
			{
				if($t == T_CONST)
					$this->_handle_class_const($class);
				else
				{
					if(!$class->is_interface())
						$this->_handle_field($class);
					$this->_handle_function($class);
				}
			}
		}
		
		$this->classes[$class->get_name()] = $class;
		return true;
	}
	
	/**
	 * Handles a const-definition. The method assumes that the current token is T_CONST.
	 *
	 * @param PC_Class $class the class in which we are currently
	 */
	private function _handle_class_const($class)
	{
		// to const-name
		$this->pos++;
		$this->_skip_rubbish();
		
		list(,$str,$line) = $this->tokens[$this->pos++];
		$name = $str;
		
		// go to '='
		$this->_skip_rubbish();
		
		// go to value
		$this->pos++;
		$this->_skip_rubbish();
		list($t,$str,) = $this->tokens[$this->pos];
		
		$type = $this->_get_type_from_token($t,$str);
		
		$class->add_constant(new PC_Constant($this->file,$line,$name,$type));
		
		$this->_run_to(';');
	}
	
	/**
	 * Handles a field-definition. If at the current token is no field the method does nothing.
	 * Otherwise it reads the complete field
	 *
	 * @param PC_Class $class the class in which we are currently
	 */
	private function _handle_field($class)
	{
		// save position
		$oldpos = $this->pos;
		
		list($t,,$line) = $this->tokens[$this->pos];
		switch($t)
		{
			case T_PUBLIC:
			case T_VAR:
			case T_PRIVATE:
			case T_PROTECTED:
				$field = new PC_Field($this->file,$line);
				if($t == T_PROTECTED)
					$field->set_visibity(PC_Visible::V_PROTECTED);
				else if($t == T_PRIVATE)
					$field->set_visibity(PC_Visible::V_PRIVATE);
				else
					$field->set_visibity(PC_Visible::V_PUBLIC);
				break;
			
			default:
				return;
		}
		
		// scan the name
		$this->pos++;
		$this->_skip_rubbish();
		list($t,$str,) = $this->tokens[$this->pos];
		
		// handle static
		if($t == T_STATIC)
		{
			$field->set_static(true);
			// to next interesting token
			$this->pos++;
			$this->_skip_rubbish();
			list($t,$str,) = $this->tokens[$this->pos];
		}
				
		// is it a function?
		if($t != T_VARIABLE)
		{
			$this->pos = $oldpos;
			return;
		}
		
		$field->set_name($str);
		
		// look for default type
		$this->pos++;
		$this->_skip_rubbish();
		list($t,$str,) = $this->tokens[$this->pos];
		if($t == '=')
		{
			$this->pos++;
			$this->_skip_rubbish();
			
			list($t,$str,) = $this->tokens[$this->pos];
			$type = $this->_get_type_from_token($t,$str);
			$field->set_type($type);
		}
		
		// run to the end
		$this->_run_to(';');
		
		// add field
		if($this->doc !== null)
		{
			$field->set_type(PC_Type::get_type_by_name($this->_get_field_type($this->doc)));
			$this->doc = null;
		}
		$class->add_field($field);
	}
	
	/**
	 * Handles a function-/method-definition. If at the current token is no function, it returns false.
	 * Otherwise it reads the complete function.
	 *
	 * @param PC_Class $class if you are in a class, please pass the class-object
	 * @return boolean true if a function has been read
	 */
	private function _handle_function($class = null)
	{
		// check for performance issues
		static $vtokens = null;
		if($vtokens === null)
		{
			$vtokens = FWS_Array_Utils::get_fast_access(
				array(T_FUNCTION,T_PUBLIC,T_PRIVATE,T_PROTECTED,T_STATIC,T_ABSTRACT,T_FINAL)
			);
		}
		list($t,,$line) = $this->tokens[$this->pos];
		if(!isset($vtokens[$t]))
			return false;
		
		$method = new PC_Method($this->file,$line,$class === null);
		if($class === null)
			$method->set_visibity(PC_Visible::V_PUBLIC);
		
		// save position just in case it is no function
		$oldpos = $this->pos;
		
		// scan "[public|private|protected]? static? [abstract|final]? function"
		$finished = false;
		for(;!$finished && $this->pos < $this->end;$this->pos++)
		{
			list($t,$str,) = $this->tokens[$this->pos];
			switch($t)
			{
				// name
				case T_FUNCTION:
					// ok, break here and look for the name...
					$finished = true;
					break;
				
				// visibility
				case T_PUBLIC:
					if($class !== null)
						$method->set_visibity(PC_Visible::V_PUBLIC);
					break;
				case T_PRIVATE:
					if($class !== null)
						$method->set_visibity(PC_Visible::V_PRIVATE);
					break;
				case T_PROTECTED:
					if($class !== null)
						$method->set_visibity(PC_Visible::V_PROTECTED);
					break;
				
				// other modifier
				case T_ABSTRACT:
					$method->set_abstract(true);
					break;
				case T_FINAL:
					$method->set_final(true);
					break;
				case T_STATIC:
					$method->set_static(true);
					break;
				
				case T_COMMENT:
				case T_WHITESPACE:
					// skip this shit ;)
					break;
				
				case T_DOC_COMMENT:
					$this->doc = $str;
					break;
				
				// in all other cases there is something wrong
				default:
					$this->pos = $oldpos;
					return false;
			}
		}
		
		$this->_skip_rubbish();
		
		// grab func-name
		list($t,$str,) = $this->tokens[$this->pos++];
		// TODO store references
		if($t == '&')
		{
			$this->_skip_rubbish();
			list($t,$str,) = $this->tokens[$this->pos++];
		}
		else if($t != T_STRING)
		{
			$this->pos = $oldpos;
			return false;
		}
		$method->set_name($str);
		
		$this->_skip_rubbish();
		
		// we are at '(' now
		$this->_handle_params($method);
		// we are at ')' now
		
		// add types from phpdoc
		if($this->doc !== null)
		{
			$this->_parse_phpdoc($method,$this->doc);
			$this->doc = null;
		}
		
		// run to the function-end
		if(($class !== null && $class->is_interface()) || $method->is_abstract())
			$this->_run_to(';');
		else
		{
			// wait for the end of the the function-body
			$curlies = 0;
			for($this->pos++;$this->pos < $this->end;$this->pos++)
			{
				$this->_skip_rubbish();
				list($t,$str,) = $this->tokens[$this->pos];
				if($t == '{')
					$curlies++;
				else if($t == '}')
				{
					$curlies--;
					if($curlies == 0)
						break;
				}
				
				$res = false;
				if($t == T_STRING && strcasecmp($str,'define') == 0)
					$res = $this->_handle_define();
				// there may be nested functions
				if(!$res)
					$this->_handle_function();
			}
		}
		
		// save method
		if($class !== null)
			$class->add_method($method);
		else
			$this->functions[$method->get_name()] = $method;
		
		return true;
	}
	
	/**
	 * Handles the parameter of a function/method. It scans the complete parameters and adds them
	 * to the given method-object.
	 *
	 * @param PC_Method $method the method-object
	 */
	private function _handle_params($method)
	{
		// we are at the '('
		$this->pos++;
		
		// we are at ')' or the first param
		for(;$this->pos < $this->end;$this->pos++)
		{
			$this->_skip_rubbish();
			list($t,$str,) = $this->tokens[$this->pos];
			// list finished
			if($t == ')')
				break;
			if($t == ',')
				continue;
			
			// skip default values
			if($t == '=')
			{
				// make the last parameter optional
				$param->set_optional(true);
				
				$round = 0;
				for($this->pos++;$this->pos < $this->end;$this->pos++)
				{
					list($t,,) = $this->tokens[$this->pos];
					if($t == '(')
						$round++;
					else if($t == ')')
					{
						$round--;
						if($round < 0)
						{
							$this->pos--;
							break;
						}
					}
					else if($round == 0 && $t == ',')
						break;
				}
				continue;
			}
			
			$param = new PC_Parameter();
			
			// handle references
			// TODO store references!
			if($t == '&')
			{
				$this->pos++;
				$this->_skip_rubbish();
				list($t,$str,) = $this->tokens[$this->pos];
			}
			
			// type hinting?
			if($t == T_STRING)
			{
				$param->set_mtype(PC_MultiType::get_type_by_name($str));
				$this->pos++;
				$this->_skip_rubbish();
				list(,$str,) = $this->tokens[$this->pos];
				$param->set_name($str);
			}
			else if($t == T_VARIABLE)
				$param->set_name($str);
			
			$method->put_param($param);
		}
		/*$this->_skip_rubbish();
		// next is either ',', ')' or '='
		list($t,$str,) = $this->tokens[$this->pos];
		
		// is there a default-value
		if($t == '=')
		{
			// ok, then use this for the type
			$round = 0;
			for($this->pos++;$this->pos < $this->end;$this->pos++)
			{
				list($t,,) = $this->tokens[$this->pos];
				// round '(' and ')' in case the parameter has an array as default-value
				if($t == '(')
					$round++;
				else if($t == ')')
				{
					$round--;
					if($round == 0)
						break;
				}
				// are we finished with the argument?
				else if($round == 0 && $t == ',')
					break;
				
				// TODO class-constants are missing here. anything else?
				switch($t)
				{
					case T_CONSTANT_ENCAPSED_STRING:
						$param->set_type(PC_Type::$STRING);
						break;
					case T_DNUMBER:
						$param->set_type(PC_Type::$FLOAT);
						break;
					case T_LNUMBER:
						$param->set_type(PC_Type::$INT);
						break;
					case T_ARRAY:
						$param->set_type(PC_Type::$TARRAY);
						// we want to ignore the array-elements
						return $param;
				}
			}
		}*/
	}
	
	/**
	 * Runs to the given token or one of the given tokens
	 *
	 * @param string|int|array $token either one token or an array of tokens
	 */
	private function _run_to($token)
	{
		for(;$this->pos < $this->end;$this->pos++)
		{
			list($t,,) = $this->tokens[$this->pos];
			if($t == T_DOC_COMMENT)
				$this->doc = $this->tokens[$this->pos][1];
			if(is_array($token) ? in_array($t,$token) : $t === $token)
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
			else if(!$heredoc)
			{
				if($t != T_WHITESPACE && $t != T_COMMENT && $t != T_DOC_COMMENT)
					return;
				if($t == T_DOC_COMMENT)
					$this->doc = $this->tokens[$this->pos][1];
			}
		}
	}
	
	/**
	 * Parses the type of a class-field from the given phpdoc
	 *
	 * @param string $doc the phpdoc
	 * @return string the field-type
	 */
	private function _get_field_type($doc)
	{
		$matches = array();
		if(preg_match('/\@var\s+([^\s]+)/',$doc,$matches))
			return $matches[1];
		
		return null;
	}

	/**
	 * Parses the given method-phpdoc
	 *
	 * @param PC_Method $func the method to which the phpdoc belongs
	 * @param string $doc the phpdoc
	 */
	private function _parse_phpdoc($func,$doc)
	{
		// look for params
		$matches = array();
		preg_match_all('/\@param\s+([^\s]+)\s+([^\s]+)/',$doc,$matches);
		foreach($matches[1] as $k => $match)
		{
			$param = $matches[2][$k];
			// does the param exist?
			if(($fp = $func->get_param($param)) !== null)
				$fp->set_mtype(PC_MultiType::get_type_by_name($match));
		}
		
		// look for return-type
		if(preg_match('/\@return\s+([^\s]+)/',$doc,$matches))
			$func->set_return_type(PC_Type::get_type_by_name($matches[1]));
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>