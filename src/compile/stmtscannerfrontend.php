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
class PC_Compile_StmtScannerFrontend extends FWS_Object
{
	/**
	 * Our lexer
	 * 
	 * @var PC_Compile_StmtLexer
	 */
	private $lexer;
	/**
	 * The variables
	 * 
	 * @var array
	 */
	private $vars = array();
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
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 * @param PC_Compile_TypeContainer $types the type-container
	 */
	public function scan_file($file,$types)
	{
		$this->lexer = PC_Compile_StmtLexer::get_for_file($file,$types);
		$this->parse();
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 * @param PC_Compile_TypeContainer $types the type-container
	 */
	public function scan($source,$types)
	{
		$this->lexer = PC_Compile_StmtLexer::get_for_string($source,$types);
		$this->parse();
	}
	
	/**
	 * Does the actual parsing
	 */
	private function parse()
	{
		$parser = new PC_Compile_StmtParser($this->lexer);
		while($this->lexer->advance($parser))
			$parser->doParse($this->lexer->get_token(),$this->lexer->get_value());
		$parser->doParse(0,0);
		
		$this->vars = array_merge($this->vars,$this->lexer->get_vars());
		$this->calls = array_merge($this->calls,$this->lexer->get_calls());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>