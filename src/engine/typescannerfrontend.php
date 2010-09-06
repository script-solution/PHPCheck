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
class PC_Engine_TypeScannerFrontend extends FWS_Object
{
	/**
	 * Our lexer
	 * 
	 * @var PC_Engine_TypeLexer
	 */
	private $lexer;
	
	/**
	 * The found types and errors
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->types = new PC_Engine_TypeContainer(PC_Project::CURRENT_ID,false);
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
		$this->lexer = PC_Engine_TypeLexer::get_for_file($file);
		$this->parse();
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$this->lexer = PC_Engine_TypeLexer::get_for_string($source);
		$this->parse();
	}
	
	/**
	 * Does the actual parsing
	 */
	private function parse()
	{
		$parser = new PC_Engine_TypeParser($this->lexer);
		while($this->lexer->advance($parser))
			$parser->doParse($this->lexer->get_token(),$this->lexer->get_value());
		$parser->doParse(0,0);
		
		$this->types->add($this->lexer->get_types());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>