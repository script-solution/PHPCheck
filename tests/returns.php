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
	public function testReturns()
	{
		$code = '<?php
class superfoo {
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
?>';
		
		list($functions,,,,$errors,) = $this->analyze($code);
		self::assertEquals(5,count($errors));
		
		self::assertEquals("function b(): integer=0",$functions['b']);
		self::assertEquals("function c(): integer or string",$functions['c']);
		self::assertEquals("function d(): integer=1 or void",$functions['d']);
		self::assertEquals("function e(): foo",$functions['e']);
		self::assertEquals("function f(): integer",$functions['f']);
		self::assertEquals("function g(): array",$functions['g']);
		self::assertEquals("function h(): float",$functions['h']);
		self::assertEquals("function i(): void",$functions['i']);
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assertRegExp(
			'/The function\/method "#foo#::a" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assertEquals(PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC,$error->get_type());
		self::assertRegExp(
			'/The function\/method "b" has no return-specification in PHPDoc, but does return a value/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assertEquals(PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC,$error->get_type());
		self::assertRegExp(
			'/The return-specification \(PHPDoc\) of function\/method "c" does not match with the returned'
			.' values \(spec="integer or string", returns="integer=0 or float=12.3"\)/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assertEquals(PC_Obj_Error::E_S_MIXED_RET_AND_NO_RET,$error->get_type());
		self::assertRegExp(
			'/The function\/method "d" has return-statements without expression and return-statements with expression/',
			$error->get_msg()
		);
		
		$error = $errors[4];
		self::assertEquals(PC_Obj_Error::E_S_RET_BUT_NO_RET_SPEC,$error->get_type());
		self::assertRegExp(
			'/The function\/method "d" has no return-specification in PHPDoc, but does return a value/',
			$error->get_msg()
		);
	}
}
