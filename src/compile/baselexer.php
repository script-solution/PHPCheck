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