<?php
/**
 * Contains the frontend for the type-scanner
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The frontend for the type-scanner
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Compile_TypeScannerFrontend extends FWS_Object
{
	/**
	 * Our lexer
	 * 
	 * @var PC_Compile_TypeLexer
	 */
	private $lexer;
	
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
	 * The found constants
	 * 
	 * @var array
	 */
	private $consts = array();
	
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
	 * @return array the found constants
	 */
	public function get_constants()
	{
		return $this->consts;
	}
	
	/**
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 */
	public function scan_file($file)
	{
		$this->lexer = PC_Compile_TypeLexer::get_for_file($file);
		$this->parse();
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$this->lexer = PC_Compile_TypeLexer::get_for_string($source);
		$this->parse();
	}
	
	/**
	 * Does the actual parsing
	 */
	private function parse()
	{
		$parser = new PC_Compile_TypeParser($this->lexer);
		while($this->lexer->advance($parser))
			$parser->doParse($this->lexer->get_token(),$this->lexer->get_value());
		$parser->doParse(0,0);
		
		$this->functions = array_merge($this->functions,$this->lexer->get_functions());
		$this->classes = array_merge($this->classes,$this->lexer->get_classes());
		$this->consts = array_merge($this->consts,$this->lexer->get_constants());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>