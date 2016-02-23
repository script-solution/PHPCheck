<?php
/**
 * Tests try-catch statements
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

class PC_Tests_TryCatch extends PC_UnitTest
{
	public function testTryCatch()
	{
		$code = '<?php
try {
	echo "foo";
}
catch(Exception $e) {
	myfunc($e);
	echo "bar";
}
?>';
		
		list(,,$vars,$calls,,) = $this->analyze($code);
		
		self::assertEquals('myfunc(Exception)',(string)$calls[0]->get_call(null,false));
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$global['e']->get_type());
	}
	
	public function testThrows()
	{
		$code = '<?php
class A {
	/**
	 * @throws Exception
	 */
	public abstract function foo();
}

class B extends A {
	public function foo() {
		throw new Exception();
	}
}

/**
 * @throws Exception always
 */
function a() {
}

/**
 * @throws A
 * @throws B
 */
function b() {
	throw new B();
	throw new Exception();
	throw 1;
}
?>';

		list($functions,$classes,,,$errors,) = $this->analyze($code);
		
		$func = $functions['a'];
		self::assertEquals('a',$func->get_name());
		self::assertEquals(
			FWS_Printer::to_string(array('Exception' => 'self')),
			FWS_Printer::to_string($func->get_throws())
		);
		
		$func = $functions['b'];
		self::assertEquals('b',$func->get_name());
		self::assertEquals(
			FWS_Printer::to_string(array('A' => 'self','B' => 'self')),
			FWS_Printer::to_string($func->get_throws())
		);
		
		self::assertEquals(4,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_S_DOC_WITHOUT_THROW,$error->get_type());
		self::assertRegExp('/The function\/method "a" throws "Exception" according to PHPDoc, but does not throw it/',$error->get_msg());
		
		$error = $errors[1];
		self::assertEquals(PC_Obj_Error::E_S_DOC_WITHOUT_THROW,$error->get_type());
		self::assertRegExp('/The function\/method "b" throws "A" according to PHPDoc, but does not throw it/',$error->get_msg());
		
		$error = $errors[2];
		self::assertEquals(PC_Obj_Error::E_S_THROW_NOT_IN_DOC,$error->get_type());
		self::assertRegExp('/The function\/method "b" does not throw "Exception" according to PHPDoc, but throws it/',$error->get_msg());
		
		$error = $errors[3];
		self::assertEquals(PC_Obj_Error::E_S_THROW_INVALID,$error->get_type());
		self::assertRegExp('/The function\/method "b" throws a non-object \(integer=1\)/',$error->get_msg());
	}
}
