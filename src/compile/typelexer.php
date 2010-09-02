<?php
/**
 * Contains the type-lexer
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
class PC_Compile_TypeLexer extends PC_Compile_BaseLexer
{
	/**
	 * @param string $file the filename
	 * @return PC_Compile_TypeLexer the instance for lexing a file
	 */
	public static function get_for_file($file)
	{
		return new self($file,true);
	}
	
	/**
	 * @param string $string the string
	 * @return PC_Compile_TypeLexer the instance for lexing a string
	 */
	public static function get_for_string($string)
	{
		return new self($string,false);
	}
	
	/**
	 * The last line in which we saw a function-declaration
	 * 
	 * @var int
	 */
	private $lastFunctionLine = 0;
	/**
	 * The last line in which we saw a class- or interface-declaration
	 * 
	 * @var int
	 */
	private $lastClassLine = 0;
	
	/**
	 * Comments for functions; because lastComment will get overwritten in certain sitations.
	 * 
	 * @var array
	 */
	private $funcComments = array();
	/**
	 * Comments for fields; because lastComment will get overwritten in certain sitations.
	 * 
	 * @var array
	 */
	private $fieldComments = array();
	/**
	 * Comments for constants; because lastComment will get overwritten in certain sitations.
	 * 
	 * @var array
	 */
	private $constComments = array();
	
	/**
	 * The found functions
	 * 
	 * @var array
	 */
	private $functions = array();
	/**
	 * The found classes
	 * 
	 * @var array
	 */
	private $classes = array();
	
	/**
	 * @return int the line in which the last function was declared
	 */
	public function get_last_function_line()
	{
		return $this->lastFunctionLine;
	}
	
	/**
	 * @return int the line in which the last class was declared
	 */
	public function get_last_class_line()
	{
		return $this->lastClassLine;
	}
	
	/**
	 * @return array the found functions
	 */
	public function get_functions()
	{
		return $this->functions;
	}
	
	/**
	 * @return array the found classes
	 */
	public function get_classes()
	{
		return $this->classes;
	}
	
	/**
	 * Declares a function
	 * 
	 * @param string $name the name
	 * @param array $params an array of PC_Obj_Parameter
	 */
	public function declare_function($name,$params)
	{
		$func = new PC_Obj_Method($this->get_file(),$this->get_last_function_line(),true);
		$func->set_name($name);
		foreach($params as $param)
			$func->put_param($param);
		$this->parse_method_doc($func);
		$this->functions[$func->get_name()] = $func;
	}
	
	/**
	 * Declares a class
	 * 
	 * @param string $name the name
	 * @param array $modifiers an array of modifiers (public, protected, abstract, final, ...) as keys
	 * @param string $extends the class-name of the super-class or empty
	 * @param array $implements an array of implemented interface-names
	 * @param array $stmts an array of statements from the class-body
	 */
	public function declare_class($name,$modifiers,$extends,$implements,$stmts)
	{
		$class = new PC_Obj_Class($this->get_file(),$this->get_last_class_line());
		$class->set_name($name);
		$class->set_abstract(isset($modifiers['abstract']));
		$class->set_final(isset($modifiers['final']));
		$class->set_super_class($extends ? $extends : null);
		foreach($implements as $if)
			$class->add_interface($if);
		$this->handle_class_stmts($class,$stmts);
		$this->classes[$class->get_name()] = $class;
	}
	
	/**
	 * Declares an interface
	 * 
	 * @param string $name the name
	 * @param array $extends an array of extended interface-names
	 * @param array $stmts an array of statements from the interface-body
	 */
	public function declare_interface($name,$extends,$stmts)
	{
		$class = new PC_Obj_Class($this->get_file(),$this->get_last_class_line());
		$class->set_name($name);
		$class->set_interface(true);
		$class->set_abstract(true);
		foreach($extends as $if)
			$class->add_interface($if);
		$this->handle_class_stmts($class,$stmts);
		$this->classes[$class->get_name()] = $class;
	}
	
	/**
	 * Puts constants, fields and methods from the given statements into $class
	 * 
	 * @param PC_Obj_Class $class the class
	 * @param array $stmts an array of PC_Obj_Constant, PC_Obj_Field and PC_Obj_Method
	 */
	private function handle_class_stmts($class,$stmts)
	{
		foreach($stmts as $stmt)
		{
			if($stmt instanceof PC_Obj_Constant)
				$class->add_constant($stmt);
			else if($stmt instanceof PC_Obj_Field)
				$class->add_field($stmt);
			else if($stmt instanceof PC_Obj_Method)
			{
				// methods in interfaces are implicitly abstract
				if($class->is_interface())
					$stmt->set_abstract(true);
				$class->add_method($stmt);
			}
			else
				FWS_Helper::error('Unknown statement: '.$stmt);
		}
	}
	
	/**
	 * Parses the type of a class-constant from the given phpdoc
	 *
	 * @param PC_Obj_Constant $const the constant
	 */
	public function parse_const_doc($const)
	{
		if(isset($this->constComments[$const->get_name()]))
		{
			$type = $this->parse_var_from($this->constComments[$const->get_name()]);
			if($type !== null)
				$const->set_type($type);
			unset($this->constComments[$const->get_name()]);
		}
	}
	
	/**
	 * Parses the type of a class-field from the given phpdoc
	 *
	 * @param PC_Obj_Field $field the field
	 */
	public function parse_field_doc($field)
	{
		if(isset($this->fieldComments[$field->get_name()]))
		{
			$type = $this->parse_var_from($this->fieldComments[$field->get_name()]);
			if($type !== null)
				$field->set_type($type);
			unset($this->fieldComments[$field->get_name()]);
		}
	}
	
	/**
	 * Parses the PHPdoc-tag "var" from the given doc
	 * 
	 * @param string $doc the doc-comment
	 * @return PC_Obj_Type the type or null
	 */
	private function parse_var_from($doc)
	{
		$matches = array();
		if(preg_match('/\@var\s+([^\s]+)/',$doc,$matches))
			return PC_Obj_Type::get_type_by_name($matches[1]);
		return null;
	}

	/**
	 * Parses the given method-phpdoc
	 *
	 * @param PC_Obj_Method $func the method to which the phpdoc belongs
	 */
	public function parse_method_doc($func)
	{
		if(isset($this->funcComments[$func->get_name()]))
		{
			$doc = $this->funcComments[$func->get_name()];
			// look for params
			$matches = array();
			preg_match_all('/\@param\s+([^\s]+)\s+([^\s]+)/',$doc,$matches);
			foreach($matches[1] as $k => $match)
			{
				$param = $matches[2][$k];
				// does the param exist?
				if(($fp = $func->get_param($param)) !== null)
					$fp->set_mtype(PC_Obj_MultiType::get_type_by_name($match));
			}
			
			// look for return-type
			if(preg_match('/\@return\s+([^\s]+)/',$doc,$matches))
				$func->set_return_type(PC_Obj_Type::get_type_by_name($matches[1]));
			unset($this->funcComments[$func->get_name()]);
		}
	}
	
	public function advance($parser)
	{
		// do it before because if e.g. after a class-declaration follows immediatly another one
		// we would get this token here BEFORE the class-declaration is handled in our parser
		// therefore we check it for the previous token
		if($this->pos >= 0)
		{
			$type = $this->tokens[$this->pos][0];
			if($type == T_CLASS || $type == T_INTERFACE)
				$this->lastClassLine = $this->get_line();
			else if($type == T_FUNCTION)
			{
				$this->lastFunctionLine = $this->get_line();
				// save the last comment for this function so that we don't loose it
				$this->funcComments[$this->get_name_for_comment()] = $this->lastComment;
				$this->lastComment = '';
			}
			else if($type == T_CONST)
			{
				$this->constComments[$this->get_name_for_comment()] = $this->lastComment;
				$this->lastComment = '';
			}
			else if($type == T_VAR || $type == T_PUBLIC || $type == T_PRIVATE || $type == T_PROTECTED)
			{
				// save the last comment for this field so that we don't loose it
				for($i = $this->pos + 1; $i < $this->N; $i++)
				{
					// seems that this was no field..
					if($this->tokens[$i][0] == T_FUNCTION)
						break;
					if($this->tokens[$i][0] == T_VARIABLE)
					{
						$this->fieldComments[substr($this->tokens[$i][1],1)] = $this->lastComment;
						$this->lastComment = '';
						break;
					}
				}
			}
		}
		
		return parent::advance($parser);
	}
	
	private function get_name_for_comment()
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