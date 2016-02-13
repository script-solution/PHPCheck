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
	 * The current line
	 * 
	 * @var int
	 */
	private $_line;
	/**
	 * The current file
	 * 
	 * @var string
	 */
	private $_file;
	/**
	 * The current token-name
	 * 
	 * @var string
	 */
	private $_token;
	/**
	 * The current token-value
	 * 
	 * @var string
	 */
	private $_tokvalue;
	/**
	 * The expected tokens
	 * 
	 * @var array
	 */
	private $_expected;
	
	public function __construct($file,$line,$token,$tokvalue,$expected)
	{
		parent::__construct(
			'Unexpected token: '.$token.' ('.$tokvalue.') in file "'.$file.'", line '.$line
		);
		$this->_token = $token;
		$this->_tokvalue = $tokvalue;
		$this->_expected = $expected;
	}
	
	public function __toString()
	{
		return $this->getMessage().'; Expected one of '.implode(', ',$this->_expected);
	}
}
