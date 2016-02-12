<?php
/**
 * Contains the base-class for all unittests.
 *
 * @version         $Id$
 * @package         PHPCheck
 * @subpackage  tests
 * @author          Nils Asmussen <nils@script-solution.de>
 * @copyright       2003-2008 Nils Asmussen
 * @link                http://www.script-solution.de
 */

/**
 * Base-class for all unittests.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */	
class PC_UnitTest extends FWS_Object
{
	/**
	 * Checks whether both strings are equal.
	 *
	 * @param string $exp the expected string
	 * @param string $recv the received string
	 */
	protected static function assertEquals($exp,$recv)
	{
		if($exp != $recv)
			throw new Exception('Strings are not equal. Expected "'.$exp.'", got "'.$recv.'"');
	}
	
	/**
	 * Checks whether the string matches the given regular expression.
	 *
	 * @param string $pattern the regular expression
	 * @param string $string the received string
	 */
	protected static function assertRegExp($pattern,$string)
	{
		if(!preg_match($pattern,$string))
			throw new Exception('String does not match pattern. Expected "'.$pattern.'", got "'.$string.'"');
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>
