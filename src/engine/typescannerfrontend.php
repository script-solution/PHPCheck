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
	 * The environment
	 *
	 * @var PC_Engine_Env
	 */
	private $env;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Env $env the environment
	 */
	public function __construct($env)
	{
		parent::__construct();
		
		if(!($env instanceof PC_Engine_Env))
			FWS_Helper::def_error('instance','env','PC_Engine_Env',$env);
	
		$this->env = $env;
	}
	
	/**
	 * Scans the given file
	 *
	 * @param string $file the file to scan
	 */
	public function scan_file($file)
	{
		$this->parse(new PC_Engine_TypeScanner($file,true,$this->env));
	}
	
	/**
	 * Scannes the given string
	 *
	 * @param string $source the string to scan
	 */
	public function scan($source)
	{
		$this->parse(new PC_Engine_TypeScanner($source,false,$this->env));
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
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
