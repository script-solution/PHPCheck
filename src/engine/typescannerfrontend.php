<?php
/**
 * Contains the frontend for the type-scanner
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
 * The frontend for the type-scanner
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_TypeScannerFrontend extends FWS_Object
{
	/**
	 * The found types and errors
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	
	/**
	 * Constructor
	 *
	 * @param PC_Engine_Options $options the options
	 */
	public function __construct($options)
	{
		parent::__construct();
		
		if(!($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		
		$this->types = new PC_Engine_TypeContainer($options);
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
		$this->parse(PC_Engine_TypeScanner::get_for_file($file,$this->types->get_options()));
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$this->parse(PC_Engine_TypeScanner::get_for_string($source,$this->types->get_options()));
	}
	
	/**
	 * Does the actual parsing
	 * 
	 * @param PC_Engine_TypeScanner $lexer the lexer
	 */
	private function parse($lexer)
	{
		$parser = new PC_Engine_TypeParser($lexer);
		while($lexer->advance($parser))
			$parser->doParse($lexer->get_token(),$lexer->get_value());
		$parser->doParse(0,0);
		
		$this->types->add($lexer->get_types());
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
