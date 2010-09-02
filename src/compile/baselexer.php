<?php
/**
 * Contains the base-lexer
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The base-class for the statement- and type-lexer
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Compile_BaseLexer
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
	protected $N;
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
	 * @param $str the file or string
	 * @param $is_file wether $str is a file
	 */
	protected function __construct($str,$is_file)
	{
		if($is_file)
		{
			$this->file = $str;
			$str = FWS_FileUtils::read($str);
		}
		$this->tokens = token_get_all($str);
		$this->N = count($this->tokens);
		for($i = 0; $i < $this->N; $i++)
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
			'value' => $this->value,
			'debugval' => $val
		));
	}
	
	/**
	 * Handles the given unary-operator. Note that its needed for static-scalars in the type-scanner,
	 * which is the reason why its here.
	 * 
	 * @param string $op the operator (+,-,...)
	 * @param PC_Obj_Variable $e the expression
	 * @return PC_Obj_Variable the result
	 */
	public function handle_unary_op($op,$e)
	{
		$type = $e->get_type();
		if($type->is_unknown() || $type->get_value() === null)
			return new PC_Obj_Variable('',new PC_Obj_Type($this->get_type_from_op($op,$type)));
		eval('$res = '.$op.$type->get_value_for_eval().';');
		return $this->get_type_from_php($res);
	}
	
	/**
	 * @param mixed $val the value
	 * @return PC_Obj_Variable the type
	 */
	protected function get_type_from_php($val)
	{
		if(is_array($val))
			return new PC_Obj_Variable('',PC_Obj_Type::get_type_by_value($val));
		else
		{
			$type = PC_Obj_Type::get_type_by_name(gettype($val));
			return new PC_Obj_Variable('',new PC_Obj_Type($type->get_type(),$val));
		}
	}
	
	/**
	 * Determines the type from the operation (assumes that the type or the value is unknown)
	 * 
	 * @param string $op the operator
	 * @param PC_Obj_Type $t1 the type of the first operand
	 * @param PC_Obj_Type $t2 the type of the second operand (may be null for unary ops)
	 * @return int the type
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
				return PC_Obj_Type::INT;
			
			// concatenation leads always to string
			case '.':
				return PC_Obj_Type::STRING;
			
			case '+':
			case '-':
			case '*':
			case '/':
			case '%':
				// if one of them is unknown we don't know wether we would get a float or int
				if($t1->is_unknown() || ($t2 !== null && $t2->is_unknown()))
					return PC_Obj_Type::UNKNOWN;
				// if both are arrays, the result is an array
				if($t1->get_type() == PC_Obj_Type::TARRAY && $t2->get_type() == PC_Obj_Type::TARRAY)
					return PC_Obj_Type::TARRAY;
				// if one of them is float, the result is float
				if($t1->get_type() == PC_Obj_Type::FLOAT || ($t2 !== null && $t2->get_type() == PC_Obj_Type::FLOAT))
					return PC_Obj_Type::FLOAT;
				// otherwise its always int
				return PC_Obj_Type::INT;
			
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
				return PC_Obj_Type::BOOL;
			
			default:
				FWS_Helper::error('Unknown operator "'.$op.'"');
		}
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
		while($this->pos < $this->N)
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
						$this->lastComment = $this->tokens[$this->pos][1];
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
?>