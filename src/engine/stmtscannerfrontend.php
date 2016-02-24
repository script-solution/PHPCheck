<?php
/**
 * Contains the frontend for the statement-scanner
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
 * The frontend for the statement-scanner
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_StmtScannerFrontend extends FWS_Object
{
	/**
	 * The found types and errors
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	
	/**
	 * The options
	 *
	 * @var PC_Engine_Options
	 */
	private $options;
	
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
	 * @param PC_Engine_Options $options the options
	 */
	public function __construct($types,$options)
	{
		parent::__construct();
		
		if(!($types instanceof PC_Engine_TypeContainer))
			FWS_Helper::def_error('instance','types',PC_Engine_TypeContainer,$types);
		if(!($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		
		$this->types = $types;
		$this->options = $options;
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
		$lexer = PC_Engine_StmtScanner::get_for_file($file,$this->types,$this->options);
		$this->parse($lexer);
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$lexer = PC_Engine_StmtScanner::get_for_string($source,$this->types,$this->options);
		$this->parse($lexer);
	}
	
	/**
	 * Does the actual parsing
	 * 
	 * @param PC_Engine_StmtScanner $lexer our lexer
	 */
	private function parse($lexer)
	{
		$parser = new PC_Engine_StmtParser($lexer);
		while($lexer->advance($parser))
			$parser->doParse($lexer->get_token(),$lexer->get_value());
		$parser->doParse(0,0);
		
		$this->vars = array_merge($this->vars,$lexer->get_vars()->get_all());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
