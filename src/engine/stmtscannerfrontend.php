<?php
/**
 * Contains the frontend for the statement-scanner
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The frontend for the statement-scanner
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_StmtScannerFrontend extends FWS_Object
{
	/**
	 * Our lexer
	 * 
	 * @var PC_Engine_StmtLexer
	 */
	private $lexer;
	
	/**
	 * The found types and errors
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	/**
	 * The variables
	 * 
	 * @var array
	 */
	private $vars = array();
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_TypeContainer $types the type-container
	 */
	public function __construct($types)
	{
		parent::__construct();
		$this->types = $types;
	}
	
	/**
	 * @return array the found variables
	 */
	public function get_vars()
	{
		return $this->vars;
	}

	/**
	 * @return PC_Engine_TypeContainer the found types and errors
	 */
	public function get_types()
	{
		return $this->types;
	}
	
	/**
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 */
	public function scan_file($file)
	{
		$this->lexer = PC_Engine_StmtLexer::get_for_file($file,$this->types);
		$this->parse();
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$this->lexer = PC_Engine_StmtLexer::get_for_string($source,$this->types);
		$this->parse();
	}
	
	/**
	 * Does the actual parsing
	 */
	private function parse()
	{
		$parser = new PC_Engine_StmtParser($this->lexer);
		while($this->lexer->advance($parser))
			$parser->doParse($this->lexer->get_token(),$this->lexer->get_value());
		$parser->doParse(0,0);
		
		$this->vars = array_merge($this->vars,$this->lexer->get_vars());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>