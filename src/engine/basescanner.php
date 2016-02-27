<?php
/**
 * Contains the base-lexer
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
 * The base-class for the statement- and type-lexer
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_BaseScanner
{
	/**
	 * Value of T_DOC_COMMENT
	 * 
	 * @var int|bool
	 */
	protected static $T_DOC_COMMENT = false;
	
	/**
	 * Turn on/off debugging
	 * 
	 * @var bool
	 */
	private $debug = false;
	
	/**
	 * The file we're scanning (for the parser)
	 *
	 * @var string
	 */
	private $file = null;
	/**
	 * The current token-line (for the parser)
	 * 
	 * @var int
	 */
	private $line = 0;
	/**
	 * The current token (for the parser)
	 * 
	 * @var int
	 */
	private $token;
	/**
	 * The current token-value (for the parser)
	 * 
	 * @var string
	 */
	private $value;
	
	/**
	 * The number of tokens
	 * 
	 * @var int
	 */
	protected $tokCount;
	/**
	 * The tokens
	 * 
	 * @var array
	 */
	protected $tokens;
	/**
	 * The current position in the tokens
	 * 
	 * @var int
	 */
	protected $pos = -1;
	/**
	 * The last comment we've seen
	 * 
	 * @var string
	 */
	protected $lastComment = '';
	
	/**
	 * Protected constructor because its a bit ugly (without method-overloading)
	 * 
	 * @param string $str the file or string
	 * @param bool $is_file whether $str is a file
	 */
	protected function __construct($str,$is_file)
	{
		if($is_file)
		{
			$this->file = $str;
			$str = FWS_FileUtils::read($str);
		}
		$this->tokens = token_get_all($str);
		$this->tokCount = count($this->tokens);
		for($i = 0; $i < $this->tokCount; $i++)
		{
			if(!is_array($this->tokens[$i]))
				$this->tokens[$i] = array(ord($this->tokens[$i]),$this->tokens[$i]);
		}
		$this->pos = -1;
		$this->line = 1;
		self::$T_DOC_COMMENT = defined('T_DOC_COMMENT') ? constant('T_DOC_COMMENT') : 10000;
	}
	
	/**
	 * @return string the file
	 */
	public function get_file()
	{
		return $this->file;
	}
	
	/**
	 * @return int the current line
	 */
	public function get_line()
	{
		return $this->line;
	}
	
	/**
	 * @return int the current token
	 */
	public function get_token()
	{
		return $this->token;
	}
	
	/**
	 * @return string the current token-value
	 */
	public function get_value()
	{
		return $this->value;
	}
	
	/**
	 * Prints the given value and various other information
	 * 
	 * @param mixed $val the value to print
	 */
	protected function debug($val)
	{
		$token = token_name($this->tokens[$this->pos][0]);
		if($token == 'UNKNOWN')
			$token = $this->tokens[$this->pos][1];
		echo FWS_Printer::to_string(array(
			'file' => $this->file,
			'line' => $this->line,
			'token' => $token,
			'value' => $this->tokens[$this->pos][1],
			'debugval' => $val
		));
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
	 * Handles the given unary-operator. Note that its needed for static-scalars in the type-scanner,
	 * which is the reason why its here.
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_MultiType $type the expression
	 * @return PC_Obj_MultiType the result
	 */
	public function handle_unary_op($op,$type)
	{
		if($type->is_array_unknown())
			return $this->get_type_from_op($op,$type);
		$res = 0;
		eval('$res = '.$op.$type->get_first()->get_value_for_eval().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * @param mixed $val the value
	 * @return PC_Obj_MultiType the type
	 */
	protected function get_type_from_php($val)
	{
		if(is_array($val))
			return new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value($val));
		else
		{
			$type = PC_Obj_Type::get_type_by_name(gettype($val));
			return new PC_Obj_MultiType(new PC_Obj_Type($type->get_type(),$val));
		}
	}
	
	/**
	 * Determines the type from the operation (assumes that the type or the value is unknown)
	 * 
	 * @param string $op the operator
	 * @param PC_Obj_MultiType $t1 the type of the first operand
	 * @param PC_Obj_MultiType $t2 the type of the second operand (may be null for unary ops)
	 * @return PC_Obj_MultiType the variable
	 */
	protected function get_type_from_op($op,$t1,$t2 = null)
	{
		switch($op)
		{
			// bitwise operators have always int as result
			case '|':
			case '&':
			case '^':
			case '>>':
			case '<<':
			case '~':
			case '?:':
				return PC_Obj_MultiType::create_int();
			
			// concatenation leads always to string
			case '.':
				return PC_Obj_MultiType::create_string();
			
			case '+':
			case '-':
			case '*':
			case '/':
			case '%':
				// if one of them is unknown we don't know whether we would get a float or int
				if($t1->is_unknown() || $t1->is_multiple() ||
						($t2 !== null && ($t2->is_unknown() || $t2->is_multiple())))
					return new PC_Obj_MultiType();
				$ti1 = $t1->get_first()->get_type();
				$ti2 = $t2 === null ? -1 : $t2->get_first()->get_type();
				// if both are arrays, the result is an array
				if($ti1 == PC_Obj_Type::TARRAY && $ti2 == PC_Obj_Type::TARRAY)
					return PC_Obj_MultiType::create_array();
				// if one of them is float, the result is float
				if($ti1 == PC_Obj_Type::FLOAT || $ti2 == PC_Obj_Type::FLOAT)
					return PC_Obj_MultiType::create_float();
				// otherwise its always int
				return PC_Obj_MultiType::create_int();
			
			case '==':
			case '!=':
			case '===':
			case '!==':
			case '<':
			case '>':
			case '<=':
			case '>=':
			case '&&':
			case '||':
			case 'xor':
			case '!':
				// always bool
				return PC_Obj_MultiType::create_bool();
			
			default:
				FWS_Helper::error('Unknown operator "'.$op.'"');
		}
	}
	
	/**
	 * Determines the type for the given type name, which is used, e.g., for return specifications.
	 *
	 * @param string $name the name
	 * @return PC_Obj_MultiType the type
	 */
	public function get_type_by_name($name)
	{
		switch($name)
		{
			case 'array':
				return PC_Obj_MultiType::create_array();
			case 'callable':
				return PC_Obj_MultiType::create_callable();
			case 'bool':
				return PC_Obj_MultiType::create_bool();
			case 'float':
				return PC_Obj_MultiType::create_float();
			case 'int':
				return PC_Obj_MultiType::create_int();
			case 'string':
				return PC_Obj_MultiType::create_string();
			
			case 'self':
				// TODO get class name
				return PC_Obj_MultiType::create_object();
			
			default:
				return PC_Obj_MultiType::create_object($name);
		}
	}
	
	/**
	 * Reports the given error for the current location.
	 *
	 * @param string $msg the error message
	 * @return PC_Obj_MultiType an unknown type
	 */
	protected function handle_error($msg)
	{
		trigger_error('Error in '.$this->file.', line '.$this->line.': '.$msg,E_USER_ERROR);
		return $this->create_unknown();
	}
	
	/**
	 * @return PC_Obj_MultiType an unknown type
	 */
	protected function create_unknown()
	{
		return new PC_Obj_MultiType();
	}
	
	/**
	 * @param string $name the name
	 * @param PC_Obj_MultiType $type the type
	 * @return PC_Obj_Variable an unknown variable
	 */
	protected function create_var($name = '',$type = null)
	{
		if($type == null)
			$type = new PC_Obj_MultiType();
		return new PC_Obj_Variable($this->file,$this->line,$name,$type);
	}
	
	/**
	 * Moves to the next token
	 * 
	 * @param object $parser the parser
	 * @return bool true if there is one
	 */
	public function advance($parser)
	{
		$this->pos++;
		while($this->pos < $this->tokCount)
		{
			if($this->debug)
			{
				if(token_name($this->tokens[$this->pos][0]) == 'UNKNOWN')
					echo $this->tokens[$this->pos][1];
				else
					echo token_name($this->tokens[$this->pos][0]).'(' .$this->tokens[$this->pos][1].')';
				echo "<br>";
			}
			
			switch($this->tokens[$this->pos][0])
			{
				// simple ignore tags.
				case T_CLOSE_TAG:
				case T_OPEN_TAG_WITH_ECHO:
					$this->pos++;
					continue;

					// comments - store for phpdoc
				case self::$T_DOC_COMMENT;
				case T_COMMENT:
					if(substr($this->tokens[$this->pos][1],0,2) == '/*')
						$this->lastComment .= $this->tokens[$this->pos][1];
					$this->line += substr_count($this->tokens[$this->pos][1],"\n");
					$this->pos++;
					continue;

					// large
				case T_OPEN_TAG:
				case T_INLINE_HTML:
				case T_ENCAPSED_AND_WHITESPACE:
				case T_WHITESPACE:
					$this->line += substr_count($this->tokens[$this->pos][1],"\n");
					$this->pos++;
					continue;

				// everything else!
				default:
					$this->line += substr_count($this->tokens[$this->pos][1],"\n");

					$this->token = $this->tokens[$this->pos][0];
					$this->value = $this->tokens[$this->pos][1];
					$this->token = $parser->transTable[$this->token];
					return true;
			}
		}
		return false;
	}
}
