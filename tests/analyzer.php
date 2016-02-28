<?php
/**
 * Tests the analyzer
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

class PC_Tests_Analyzer extends PC_UnitTest
{
	public function test_s_method_missing()
	{
		$code = '<?php
class A {
	public function foo() {}
}
$A = new A();
$A->foo();
$A->bar();

class B extends A {
}
$B = new B();
$B->foo();
$B->bar();

/** @return C */
function getc() { return new C; }
/** @return E */
function geti() { return new E; }

interface I {}
class C {}
class D extends C {
	public function bar() {}
}
class E implements I {
	public function bar() {}
}
$D = getc();
$D->bar();			// ok, because there is a subclass that provides this method. so maybe its correct
$D->foobar();		// not ok, because there is no subclass that provides that method
$E = geti();
$E->bar();			// ok, because there is a class that implements that interface. so maybe its correct
$E->foobar();		// not ok, because there is no class that implements that method
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(4,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "bar" does not exist in the class "#A#"!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "bar" does not exist in the class "#B#"!/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "foobar" does not exist in the class "#C#"!/',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "foobar" does not exist in the class "#E#"!/',$error->get_msg());
	}
	
	public function test_s_return_spec()
	{
		$code = '<?php
class C {}

/** @return C */
function a() {
	return new C;
}

/** @return I */
function b() {
	
}

/** @return int */
function c() {
	return "foo";
}

/** @return void */
function d() {
	return;
}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex('/The function\/method "b" has a return-specification in PHPDoc, but does not return a value/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_RETURNS_DIFFER_FROM_SPEC,$error->get_type());
		self::assert_regex('/The return-specification \(PHPDoc\) of function\/method "c" does not match with the returned values \(spec="integer", returns="string=foo"\)/',$error->get_msg());
	}
	
	public function test_s_abstract_class_inst()
	{
		$code = '<?php
abstract class A {
	// otherwise it would complain about an abstract class without abstract method
	abstract function dummy();
}
$A = new A();

class B extends A {
	public function __construct() {
		parent::__construct();
	}
	// otherwise it would complain about a not-abstract class with abstract method
	function dummy() {}
}
$B = new B();

interface I {
	// otherwise it would complain about a missing constructor
	public function __construct();
}
$I = new I();
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_ABSTRACT_CLASS_INSTANTIATION,$error->get_type());
		self::assert_regex('/You can\'t instantiate the abstract class "#A#"!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_ABSTRACT_CLASS_INSTANTIATION,$error->get_type());
		self::assert_regex('/You can\'t instantiate the abstract class "#I#"!/',$error->get_msg());
	}
	
	public function test_s_static_call()
	{
		$code = '<?php
class A {
	public static function foo() {}
	public function bar() {
		$this->bar();	// ok, bar is not static
	}
}
A::foo();			// ok, foo is static
$A = new A();
$A->bar();		// ok, bar is not static
A::bar();			// not ok, bar is not static

class B extends A {
	public static function foo2() {
		parent::foo();	// ok, foo is static
		self::foo2();		// ok, foo2 is static
		parent::bar();	// not ok, bar is not static, but we are in a static context
	}
}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_STATIC_CALL,$error->get_type());
		self::assert_regex(
			'/Your call "#A#::bar\(\)" calls "bar" statically, but the method is not static!/',$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_STATIC_CALL,$error->get_type());
		self::assert_regex(
			'/Your call "#A#::bar\(\)" calls "bar" statically, but the method is not static!/',$error->get_msg()
		);
	}
	
	public function test_s_nonstatic_call()
	{
		$code = '<?php
class A {
	public static function foo() {}
	public function bar() {
		$this->bar();	// ok, bar is not static
	}
}
A::foo();			// ok, foo is static
$A = new A();
$A->foo();		// not ok, foo is static

class B extends A {
	public static function foo2() {
		parent::foo();	// ok, foo is static
		self::foo2();		// ok, foo2 is static
		$this->foo2();	// not ok, foo2 is static
	}
	public function bar2() {
		$this->foo2();	// not ok, foo2 is static and we are in a non-static context
	}
}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_NONSTATIC_CALL,$error->get_type());
		self::assert_regex(
			'/Your call "#A#->foo\(\)" calls "foo" not statically, but the method is static!/',$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_NONSTATIC_CALL,$error->get_type());
		self::assert_regex(
			'/Your call "#B#->foo2\(\)" calls "foo2" not statically, but the method is static!/',$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_NONSTATIC_CALL,$error->get_type());
		self::assert_regex(
			'/Your call "#B#->foo2\(\)" calls "foo2" not statically, but the method is static!/',$error->get_msg()
		);
	}
	
	public function test_s_class_missing()
	{
		$code = '<?php
$A = new A();
$B = A::foo;			// TODO we are not able to detect that yet
$C = A::bar();
$D = A::$a->b();	// TODO same problem
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_MISSING,$error->get_type());
		self::assert_regex(
			'/The class "#A#" does not exist!/',$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_MISSING,$error->get_type());
		self::assert_regex(
			'/The class "#A#" does not exist!/',$error->get_msg()
		);
	}
	
	public function test_s_function_missing()
	{
		$code = '<?php
fooo();
$name = "bar";
$bar();					// TODO not yet detectable
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(1,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_FUNCTION_MISSING,$error->get_type());
		self::assert_regex(
			'/The function "fooo" does not exist!/',$error->get_msg()
		);
	}
	
	public function test_s_wrong_argcount()
	{
		$code = '<?php
// prevent type-error
/** @param int $a @param int $b @param int $c */
function foo($a,$b,$c) {}
foo();
foo(1);
foo(1,2);
foo(1,2,3);
foo(1,2,3,4);

/** @param int $a @param int $b @param int $c */
function bar($a,$b,$c = 1) {}
bar();
bar(1);
bar(1,2);
bar(1,2,3);
bar(1,2,3,4);

class A {
	/** @param int $a @param int $b */
	public static function test($a,$b) {}
	public function foo() {}
	/** @param string $a */
	public function bar($a = "str") {}
}
A::test();
$A = new A();
$A->foo(1);
$A->foo(1,2);
$A->bar();
$A->bar("test");
$A->bar("test","test2");
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(11,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "foo\(\)" requires 3 arguments but you have given 0/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "foo\(integer=1\)" requires 3 arguments but you have given 1/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "foo\(integer=1, integer=2\)" requires 3 arguments but you have given 2/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "foo\(integer=1, integer=2, integer=3, integer=4\)" requires 3 arguments but you have given 4/',
			$error->get_msg()
		);
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "bar\(\)" requires 2 to 3 arguments but you have given 0/',
			$error->get_msg()
		);
		
		$error = $errors[5];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "bar\(integer=1\)" requires 2 to 3 arguments but you have given 1/',
			$error->get_msg()
		);
		
		$error = $errors[6];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "bar\(integer=1, integer=2, integer=3, integer=4\)" requires 2 to 3 arguments but you have given 4/',
			$error->get_msg()
		);
		
		$error = $errors[7];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "#A#::test\(\)" requires 2 arguments but you have given 0/',
			$error->get_msg()
		);
		
		$error = $errors[8];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "#A#->foo\(integer=1\)" requires 0 arguments but you have given 1/',
			$error->get_msg()
		);
		
		$error = $errors[9];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "#A#->foo\(integer=1, integer=2\)" requires 0 arguments but you have given 2/',
			$error->get_msg()
		);
		
		$error = $errors[10];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_COUNT,$error->get_type());
		self::assert_regex(
			'/The \S+ called by "#A#->bar\(string=test, string=test2\)" requires 0 to 1 arguments but you have given 2/',
			$error->get_msg()
		);
	}
	
	public function test_s_wrong_argtype()
	{
		$code = '<?php
/**
 * @param int $a
 * @param int $b
 * @param int $c
 */
function foo($a,$b,$c) {}
foo(1,2,3);								// ok
foo("str",12.3,array());	// all 3 wrong
foo(true,false,3);				// 1 and 2 wrong

/**
 * @param string $a
 * @param string $b
 * @param int $c
 */
function bar($a,$b,$c = 1) {}
bar($_,$_,12.3);					// first 2 are unknown -> ok
bar("str","str",1);				// third is ok, type is known by default-value
bar("str","str",true);		// third is wrong

class A {}

/**
 * @param A $a
 * @param array $b
 */
function foobar(A $a,array $b) {}
foobar(new A(),array());	// both ok
foobar(null,12);					// first unknown -> ok, second wrong
foobar("str",true);				// both wrong
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(10,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 1 in "foo\(string=str, float=12.3, array={}\)" requires an "integer" .*? "string=str"/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 2 in "foo\(string=str, float=12.3, array={}\)" requires an "integer" .*? "float=12.3"/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 3 in "foo\(string=str, float=12.3, array={}\)" requires an "integer" .*? "array={}"/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 1 in "foo\(bool=1, bool=, integer=3\)" requires an "integer" .*? "bool=1"/',
			$error->get_msg()
		);
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 2 in "foo\(bool=1, bool=, integer=3\)" requires an "integer" .*? "bool="/',
			$error->get_msg()
		);
		
		$error = $errors[5];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 3 in "bar\(unknown, unknown, float=12.3\)" requires an "integer" .*? "float=12.3"/',
			$error->get_msg()
		);
		
		$error = $errors[6];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 3 in "bar\(string=str, string=str, bool=1\)" requires an "integer" .*? "bool=1"/',
			$error->get_msg()
		);
		
		$error = $errors[7];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 2 in "foobar\(unknown, integer=12\)" requires an "array" .*? "integer=12"/',
			$error->get_msg()
		);
		
		$error = $errors[8];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 1 in "foobar\(string=str, bool=1\)" requires an "A" .*? "string=str"/',
			$error->get_msg()
		);
		
		$error = $errors[9];
		self::assert_equals(PC_Obj_Error::E_S_WRONG_ARGUMENT_TYPE,$error->get_type());
		self::assert_regex(
			'/parameter 2 in "foobar\(string=str, bool=1\)" requires an "array" .*? "bool=1"/',
			$error->get_msg()
		);
	}
	
	public function test_t_final_class_inheritance()
	{
		$code = '<?php
final class A {}
class B extends A {}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(1,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_FINAL_CLASS_INHERITANCE,$error->get_type());
		self::assert_regex('/The class "#B#" inherits from the final class "#A#/',$error->get_msg());
	}
	
	public function test_t_class_not_abstract()
	{
		$code = '<?php
class A {
	abstract function dummy();
}
class B extends A {
}

interface I {
	public function foo();
}
class C implements I {}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_NOT_ABSTRACT,$error->get_type());
		self::assert_regex('/The class "#A#" is NOT abstract but contains abstract methods!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_NOT_ABSTRACT,$error->get_type());
		self::assert_regex('/The class "#B#" is NOT abstract but contains abstract methods!/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_NOT_ABSTRACT,$error->get_type());
		self::assert_regex('/The class "#C#" is NOT abstract but contains abstract methods!/',$error->get_msg());
	}
	
	public function test_t_class_missing()
	{
		$code = '<?php
class A {
}
class B extends A {}
class C extends UnknownClass {}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(1,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_MISSING,$error->get_type());
		self::assert_regex('/The class "#UnknownClass#" does not exist!/',$error->get_msg());
	}
	
	public function test_t_interface_missing()
	{
		$code = '<?php
interface I {}
class D implements I {}
class E implements UnknownInterface {}
class F implements I,UnknownInterface,Unknown2 {}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_INTERFACE_MISSING,$error->get_type());
		self::assert_regex('/The interface "#UnknownInterface#" does not exist!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_INTERFACE_MISSING,$error->get_type());
		self::assert_regex('/The interface "#UnknownInterface#" does not exist!/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_INTERFACE_MISSING,$error->get_type());
		self::assert_regex('/The interface "#Unknown2#" does not exist!/',$error->get_msg());
	}
	
	public function test_t_if_is_no_if()
	{
		$code = '<?php
interface I {}
class FakeI {}
class D implements I {}
class E implements FakeI {}
class F implements I,FakeI {}
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		self::assert_equals(2,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_IF_IS_NO_IF,$error->get_type());
		self::assert_regex('/"#FakeI#" is no interface, but implemented by class #E#!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_IF_IS_NO_IF,$error->get_type());
		self::assert_regex('/"#FakeI#" is no interface, but implemented by class #F#!/',$error->get_msg());
	}
	
	public function test_callable()
	{
		$code = '<?php
function my_func() {}
class A {
	public function test() {}
}

/**
 * @param callable $func
 */
function call($func) {
	$func();
}

call("my_func"); 								// ok
call("my_func2"); 							// function missing
call("a".$_);										// unknown

call(array(new A(),"test"));		// ok
call(array());									// invalid
$a = array();
$a[$_] = 1;
call($a);												// unknown
call(array($_,1));							// invalid
call(array($_,"foo"));					// unknown
call(array(new A,$_));					// unknown
call(array(1,2));								// invalid
call(array(new A(),1));					// invalid
call(array(new A(),"foo"));			// method missing
call(array(new B(),"test"));		// class missing

call(function() {});						// ok

call(1);												// invalid
?>';
		
		list(,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(9,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_FUNCTION_MISSING,$error->get_type());
		self::assert_regex('/The function "my_func2" does not exist!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_CALLABLE_INVALID,$error->get_type());
		self::assert_regex('/Invalid callable: array={}!/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_CALLABLE_INVALID,$error->get_type());
		self::assert_regex('/Invalid callable: array={0 = unknown;1 = integer=1;}!/',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_CALLABLE_INVALID,$error->get_type());
		self::assert_regex('/Invalid callable: array={0 = integer=1;1 = integer=2;}!/',$error->get_msg());
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_CALLABLE_INVALID,$error->get_type());
		self::assert_regex('/Invalid callable: array={0 = A;1 = integer=1;}!/',$error->get_msg());
		
		$error = $errors[5];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "foo" does not exist in the class "#A#"!/',$error->get_msg());
		
		$error = $errors[6];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_MISSING,$error->get_type());
		self::assert_regex('/The class "#B#" does not exist!/',$error->get_msg());
		
		// twice because it is instantiated first and then we try to call a method on it
		$error = $errors[7];
		self::assert_equals(PC_Obj_Error::E_S_CLASS_MISSING,$error->get_type());
		self::assert_regex('/The class "#B#" does not exist!/',$error->get_msg());
		
		$error = $errors[8];
		self::assert_equals(PC_Obj_Error::E_S_CALLABLE_INVALID,$error->get_type());
		self::assert_regex('/Invalid callable: integer=1!/',$error->get_msg());
	}
}
