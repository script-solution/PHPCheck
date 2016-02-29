<?php
/**
 * Tests return-statement-checks
 * 
 * @package			PHPCheck
 * @subpackage	tests
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

class PC_Tests_Returns extends PC_UnitTest
{
	public function test_returns()
	{
		$code = '<?php
abstract class superfoo {
	/* @return int */
	abstract function a();
}
class foo extends superfoo {
	function a() {
	}
}

function b() {
	return 0;
}

/* @return int|string */
function c() {
	if($_)
		return 0;
	return 12.3;
}

function d() {
	return 1;
	return;
}

/** @return int|string */
function good() {
	if($_)
		return "foo";
	else
		return 2+3;
	return 1;
}

/** @return foo */
function e(): foo {
	return new foo();
}

/** @return int */
function f() : int {
	return 1 + 1;
}

/** @return array */
function g(): array {
	return array();
}

/** @return float */
function h(): float {
	return 1.1;
}

function i() {
}

class j {
	/**
	 * @return j
	*/
	public function __construct() {
		return 1;
	}
}

$x = i();

/** @return int */
function k() {
	if(true)
		return "foo";
	return 1;
}
?>';
		
		list($functions,,,,$errors) = $this->analyze($code);
		self::assert_equals(9,count($errors));
		
		self::assert_equals("function b(): integer=0",$functions['b']);
		self::assert_equals("function c(): integer or string",$functions['c']);
		self::assert_equals("function d(): integer=1 or void",$functions['d']);
		self::assert_equals("function e(): foo",$functions['e']);
		self::assert_equals("function f(): integer",$functions['f']);
		self::assert_equals("function g(): array",$functions['g']);
		self::assert_equals("function h(): float",$functions['h']);
		self::assert_equals("function i(): void",$functions['i']);
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_T_RETURN_DIFFERS_FROM_DOC,$error->get_type());
		self::assert_regex(
			'/The constructor of class j specifies return type j/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#foo#::a" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC,$error->get_type());
		self::assert_regex(
			'/The function\/method "b" has no return-specification in PHPDoc, but does return a value/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC,$error->get_type());
		self::assert_regex(
			'/The return-specification \(PHPDoc\) of function\/method "c" does not match with the returned'
			.' values \(spec="integer or string", returns="integer=0 or float=12.3"\)/',
			$error->get_msg()
		);
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_MIXED_RET_AND_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "d" has return-statements without expression and return-statements with expression/',
			$error->get_msg()
		);
		
		$error = $errors[5];
		self::assert_equals(PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC,$error->get_type());
		self::assert_regex(
			'/The function\/method "d" has no return-specification in PHPDoc, but does return a value/',
			$error->get_msg()
		);
		
		$error = $errors[6];
		self::assert_equals(PC_Obj_Error::E_S_CONSTR_RETURN,$error->get_type());
		self::assert_regex(
			'/The constructor of "j" has a return-statement with expression/',
			$error->get_msg()
		);
		
		$error = $errors[7];
		self::assert_equals(PC_Obj_Error::E_S_VOID_ASSIGN,$error->get_type());
		self::assert_regex(
			'/Assignment of void to \$x/',
			$error->get_msg()
		);
		
		$error = $errors[8];
		self::assert_equals(PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC,$error->get_type());
		self::assert_equals(
			'The return-specification (PHPDoc) of function/method "k" does not match with the returned values (spec="integer", returns="string=foo or integer=1")',
			$error->get_msg()
		);
	}
}
