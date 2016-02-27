<?php
/**
 * Contains the parser-exception
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
 * The parser-exception
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_Exception extends Exception
{
	/**
	 * The current token-name
	 * 
	 * @var string
	 */
	private $token;
	/**
	 * The current token-value
	 * 
	 * @var string
	 */
	private $tokvalue;
	/**
	 * The expected tokens
	 * 
	 * @var array
	 */
	private $expected;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file
	 * @param int $line the line
	 * @param string $token the token
	 * @param string $tokvalue the token value
	 * @param array $expected the expected tokens
	 */
	public function __construct($file,$line,$token,$tokvalue,$expected)
	{
		parent::__construct(
			'Unexpected token: '.$token.' ('.$tokvalue.') in file "'.$file.'", line '.$line
		);
		$this->token = $token;
		$this->tokvalue = $tokvalue;
		$this->expected = $expected;
	}
	
	public function __toString()
	{
		return $this->getMessage().'; Expected one of '.implode(', ',$this->expected);
	}
}
