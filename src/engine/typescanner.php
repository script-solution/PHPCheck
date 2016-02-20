<?php
/**
 * Contains the type-lexer
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
class PC_Engine_TypeScanner extends PC_Engine_BaseScanner
{
	/**
	 * @param string $file the filename
	 * @return PC_Engine_TypeScanner the instance for lexing a file
	 */
	public static function get_for_file($file)
	{
		return new self($file,true);
	}
	
	/**
	 * @param string $string the string
	 * @return PC_Engine_TypeScanner the instance for lexing a string
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
	 * The found types and errors
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	
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
	 */
	protected function __construct($str,$is_file)
	{
		parent::__construct($str,$is_file);
		$this->types = new PC_Engine_TypeContainer(PC_Project::CURRENT_ID,false);
	}
	
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
	 * @return PC_Engine_TypeContainer the found types and errors
	 */
	public function get_types()
	{
		return $this->types;
	}
	
	/**
	 * Declares a function
	 * 
	 * @param string $name the name (empty for anonymous functions)
	 * @param array $params an array of PC_Obj_Parameter
	 */
	public function declare_function($name,$params)
	{
		$func = new PC_Obj_Method($this->get_file(),$this->get_last_function_line(),true);
		if($name == '')
		{
			$func->set_anonymous(true);
			$func->set_name(PC_Obj_Method::ANON_PREFIX.($this->anon_id++));
		}
		else
			$func->set_name($name);
		foreach($params as $param)
			$func->put_param($param);
		$this->parse_method_doc($func);
		$this->types->add_functions(array($func));
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
		$this->types->add_classes(array($class));
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
		$this->types->add_classes(array($class));
	}
	
	/**
	 * Creates class fields.
	 *
	 * @param array $vars an array of PC_Obj_Variable's
	 * @param array $modifiers an array with modifiers
	 * @return array an array of PC_Obj_Field
	 */
	public function create_fields($vars,$modifiers)
	{
		$res = array();
		$base = new PC_Obj_Field(
			$this->get_file(),$this->get_line(),$vars[0]['name']
		);
		$base->set_static(in_array('static',$modifiers));
		if(in_array('private',$modifiers))
			$base->set_visibility(PC_Obj_Visible::V_PRIVATE);
		else if(in_array('protected',$modifiers))
			$base->set_visibility(PC_Obj_Visible::V_PROTECTED);
		else
			$base->set_visibility(PC_Obj_Visible::V_PUBLIC);
		$this->parse_field_doc($base);
		
		foreach($vars as $v)
		{
			$field = clone $base;
			$field->set_name($v['name']);
			if(!$field->get_type()->is_multiple() && isset($v['val']))
				$field->set_type($v['val']);
			$res[] = $field;
		}
		return $res;
	}
	
	/**
	 * Creates class constants.
	 *
	 * @param array $consts an array with the constants
	 * @return array an array of PC_Obj_Constant
	 */
	public function create_consts($consts)
	{
		$res = array();
		$base = new PC_Obj_Constant($this->get_file(),$this->get_line(),'dummy');
		$this->parse_const_doc($base);
		
		foreach($consts as $c)
		{
			$const = clone $base;
			$const->set_name($c['name']);
			if(isset($c['val']))
				$const->set_type($c['val']);
			$res[] = $const;
		}
		return $res;
	}
	
	/**
	 * Creates a method.
	 *
	 * @param string $name the name
	 * @param array $modifiers the modifiers
	 * @param array $params an array of PC_Obj_Parameter's
	 * @return PC_Obj_Method the method
	 */
	public function create_method($name,$modifiers,$params)
	{
		$m = new PC_Obj_Method($this->get_file(),$this->get_last_function_line(),false);
		$m->set_name($name);
		$m->set_static(in_array('static',$modifiers));
		$m->set_abstract(in_array('abstract',$modifiers));
		$m->set_final(in_array('final',$modifiers));
		if(in_array('private',$modifiers))
			$m->set_visibility(PC_Obj_Visible::V_PRIVATE);
		else if(in_array('protected',$modifiers))
			$m->set_visibility(PC_Obj_Visible::V_PROTECTED);
		else
			$m->set_visibility(PC_Obj_Visible::V_PUBLIC);
		foreach($params as $param)
			$m->put_param($param);
		$this->parse_method_doc($m);
		return $m;
	}
	
	/**
	 * Creates a parameter with given attributes.
	 *
	 * @param string $name the name
	 * @param PC_Obj_MultiType $type the type from type hinting (or null)
	 * @param PC_Obj_MultiType $val the default value (or null)
	 * @param bool $optional whether it's optional
	 * @return PC_Obj_Parameter the parameter
	 */
	public function create_parameter($name,$type,$val,$optional)
	{
		$p = new PC_Obj_Parameter($name);
		if($val)
			$val->clear_values(); // value is not interesting here
		if($type && !$type->is_unknown())
			$p->set_mtype($type);
		else if($val)
			$p->set_mtype($val);
		$p->set_optional($optional);
		return $p;
	}
	
	/**
	 * Handles a define
	 * 
	 * @param array $args an array of arguments (PC_Obj_MultiType)
	 */
	public function handle_define($args)
	{
		// define has 2 or 3 args
		if(count($args) != 2 && count($args) != 3)
			return;
		// if the type of the name is unknown, do nothing
		$name = $args[0] ? $args[0]->get_string() : null;
		if($name === null)
			return;
		$type = $args[1] !== null ? $args[1] : null;
		$this->types->add_constants(array(
			new PC_Obj_Constant($this->get_file(),$this->get_line(),$name,$type)
		));
	}
	
	/**
	 * Returns the value of the given constant
	 * 
	 * @param string $name the constant-name
	 * @return PC_Obj_MultiType the type
	 */
	public function get_constant_type($name)
	{
		if(($const = $this->types->get_constant($name)) !== null)
			return $const->get_type();
		if(strcasecmp($name,'null') == 0)
			return new PC_Obj_MultiType();
		return PC_Obj_MultiType::create_string($name);
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
			// if we already know the value, we don't have to use the phpdoc
			// TODO we could issue a warning here if the type differs
			if($const->get_type()->is_unknown())
			{
				$type = $this->parse_var_from($this->constComments[$const->get_name()]);
				if($type !== null)
					$const->set_type($type);
			}
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
			// if we already know the value, we don't have to use the phpdoc
			// TODO we could issue a warning here if the type differs
			if($field->get_type()->is_unknown())
			{
				$type = $this->parse_var_from($this->fieldComments[$field->get_name()]);
				if($type !== null)
					$field->set_type($type);
			}
			unset($this->fieldComments[$field->get_name()]);
		}
	}
	
	/**
	 * Parses the PHPdoc-tag "var" from the given doc
	 * 
	 * @param string $doc the doc-comment
	 * @return PC_Obj_MultiType the type or null
	 */
	private function parse_var_from($doc)
	{
		$matches = array();
		if(preg_match('/\@var\s+(\S+)/',$doc,$matches))
			return PC_Obj_MultiType::get_type_by_name($matches[1]);
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
				$param = substr($matches[2][$k],1);
				// does the param exist?
				if(($fp = $func->get_param($param)) !== null)
				{
					$fp->set_mtype(PC_Obj_MultiType::get_type_by_name($match));
					$fp->set_has_doc(true);
				}
				else
				{
					$this->report_error(
						'Found PHPDoc for parameter "'.$param.'" ('.$match.'),'
						.' but the parameter does not exist',
						PC_Obj_Error::E_T_DOC_WITHOUT_PARAM,
						$func->get_line()
					);
				}
			}
			
			// look for return-type
			if(preg_match('/\@return\s+([^\s]+)/',$doc,$matches))
			{
				$func->set_return_type(PC_Obj_MultiType::get_type_by_name($matches[1]));
				$func->set_has_return_doc(true);
			}
			unset($this->funcComments[$func->get_name()]);
		}
	}
	
	/**
	 * Adds the given type and param to the potential errors we should process in the finalizer
	 * 
	 * @param int $type the error-type
	 * @param array $info information about the pot-error
	 * @param int $line if you know better than $this->get_line(), provide the line-number
	 */
	private function report_pot_error($type,$info,$line = 0)
	{
		$this->types->add_pot_errors(array(array(
			$type,$info,$this->get_file(),$line === 0 ? $this->get_line() : $line
		)));
	}
	
	/**
	 * Adds the given message and type to the errors
	 * 
	 * @param string $msg the message
	 * @param int $type the error-type (PC_Obj_Error::*)
	 * @param int $line if you know better than $this->get_line(), provide the line-number
	 */
	private function report_error($msg,$type,$line = 0)
	{
		$this->types->add_errors(array(new PC_Obj_Error(
			new PC_Obj_Location($this->get_file(),$line === 0 ? $this->get_line() : $line),$msg,$type
		)));
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
				for($i = $this->pos + 1; $i < $this->tokCount; $i++)
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
	
	/**
	 * @return string the next following function/constant-name (token T_STRING)
	 */
	private function get_name_for_comment()
	{
		for($i = $this->pos + 1; $i < $this->tokCount; $i++)
		{
			if($this->tokens[$i][0] == T_STRING)
				return $this->tokens[$i][1];
		}
		return null;
	}
}
