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
	 * The global-scope identifier
	 * 
	 * @var string
	 */
	const SC_GLOBAL = '#global';
	
	/**
	 * @param string $file the filename
	 * @return PC_Compile_StmtLexer the instance for lexing a file
	 */
	public static function get_for_file($file)
	{
		return new self($file,true);
	}
	
	/**
	 * @param string $string the string
	 * @return PC_Compile_StmtLexer the instance for lexing a string
	 */
	public static function get_for_string($string)
	{
		return new self($string,false);
	}
	
	/**
	 * The current scope
	 * 
	 * @var string
	 */
	private $scope = PC_Obj_Variable::SCOPE_GLOBAL;
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
	 * Returns the value of the variable with given name in current scope. If it does not exist,
	 * a new one is created (but not stored in scope).
	 * 
	 * @param string $var the variable-name
	 * @return PC_Obj_Variable the variable
	 */
	public function get_var($var)
	{
		if(!isset($this->vars[$this->scope][$var]))
			return new PC_Obj_Variable($var,PC_Obj_Type::get_type_by_name('unknown'));
		return $this->vars[$this->scope][$var];
	}
	
	/**
	 * Sets the given variable in the current scope to given value
	 * 
	 * @param PC_Obj_Variable $var the variable to set
	 * @param PC_Obj_Type $value the value
	 */
	public function set_var($var,$value)
	{
		$var->set_type($value);
		$var->set_function($this->get_func());
		$var->set_class($this->get_class());
		$this->vars[$this->scope][$var->get_name()] = $var;
		return $value;
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
	 * Handles the given binary-assignment-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $var the variable
	 * @param PC_Obj_type $e the expression
	 * @return PC_Obj_Type the result
	 */
	public function handle_bin_assign_op($op,$var,$e)
	{
		$res = $this->handle_bin_op($op,$var->get_type(),$e);
		$this->set_var($var,$res);
		return $res;
	}
	
	/**
	 * Handles the given binary-operator
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Type $e1 the left expression
	 * @param PC_Obj_type $e2 the right expression
	 * @return PC_Obj_Type the result
	 */
	public function handle_bin_op($op,$e1,$e2)
	{
		// if we don't know one of the types, there is no chance the calculate the result
		if($e1->is_unknown() || $e2->is_unknown())
			return $e1;
		if($e1->get_value() === null || $e2->get_value() === null)
		{
			// if we have a float, the result gets a float
			if($e1->get_type() == PC_Obj_Type::FLOAT || $e2->get_type() == PC_Obj_Type::FLOAT)
				return new PC_Obj_Type(PC_Obj_Type::FLOAT);
			// otherwise its always an integer since it are integer-operations
			return new PC_Obj_Type(PC_Obj_Type::INT);
		}
		$res = 0;
		eval('$res = '.$e1->get_value().' '.$op.' '.$e2->get_value().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * Handles post-increments and -decrements
	 * 
	 * @param string $op the operator (+,-)expression
	 * @param PC_Obj_Variable $var the variable
	 */
	public function handle_post_op($op,$var)
	{
		$type = $var->get_type();
		if($type->is_unknown())
			return PC_Obj_Type(PC_Obj_Type::UNKNOWN);
		$res = 0;
		eval('$res = '.$type.$op.$op.';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * @param mixed $val the value
	 * @return PC_Obj_Type the type
	 */
	private function get_type_from_php($val)
	{
		$type = PC_Obj_Type::get_type_by_name(gettype($val));
		return new PC_Obj_Type($type->get_type(),$val);
	}
	
	public function advance($parser)
	{
		if($this->pos >= 0)
		{
			$type = $this->tokens[$this->pos][0];
			if($type == T_FUNCTION)
				$this->start_function($this->get_type_name());
			else if($type == T_CLASS)
				$this->start_class($this->get_type_name());
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