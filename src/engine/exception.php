<?php
/**
 * Contains the parser-exception
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The parser-exception
 * 
 * @package			PHPCheck
 * @subpackage	src
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
?>