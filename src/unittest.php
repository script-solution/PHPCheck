<?php
/**
 * Contains the base-class for all unittests.
 * 
 * @package			PHPCheck
 * @subpackage	src
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
