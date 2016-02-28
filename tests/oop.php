<?php
/**
 * Tests OOP-functionality
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

class PC_Tests_OOP extends PC_UnitTest
{
	private static $code = '<?php
/** @param int $a @param string $b */
function strstr($a,$b) {
}
	
class a {
	/* @var int|string */
	private $foo,$bar = -1;
	/* @var string */
	private $a,$b = "a";
	
	const ME = 4, YOU = "str";
	
  const c = 0;
  private $f = "abc";
  protected $p = array(1,2,3);
  public $pub;
  public $pubint = 4;
  public $pubarr = array(1,2,3);
  /** @var a */
  public $pubobj;
  public function __construct() {}
  protected function test() {}
  /** @param a $arg @return a */
  public function test2(a $arg) {
  	return $arg;
  }
  
  public function aaa() {
  	strstr(self::ME,a::YOU);
  }
}

interface j {}

abstract class b extends a implements i,j {
	/** @param b $arg @return b */
	public function test2(b $arg) {
		return $arg;
	}
	/** @return int */
	public static function get42() {
		return 42;
	}
	/** @return int */
	public static function sdf() {
		return self::get42();
	}
	public function partest() {
		parent::test();
	}
}

interface i1 {}
interface i2 {}

interface i extends i1,i2 {
	/**
	 * @return string
	 */
	public function doSomething();
}

/** @param int $a */
function dummy($a) {}

final class x extends b implements i {
	private static $var = 4;
	public static $array1 = array(1,2,3);
	public static $array2 = array(array(4,5,6));
	public function doSomething() {
		// nothing
		dummy(self::$array1[1]);
		dummy(x::$array1[2]);
		dummy(x::$array2[0][2]);
		dummy(x::$array2[0][4]);
		dummy(x::$array2[1][2]);
		return "";
	}
	public static function mystatic() {}
}

$a = a::c;
$b = new a();
$c = $b->test2($b);
$d = new x();
$e = $d->test2($d);
$f = $b->pubint;
$g = $b->pubarr[0];
$h = $b->pubobj->pubint;
$i = $d->test2($b)->test2($b);
$j = b::sdf();
$k = $d->partest();
$n = __FILE__;
$o = __LINE__;
$p = array(new a(),new x());
$q = $p[0]->test2($b);
$r = $p[1]->test2($b);
?>';
	
	public function test_oop()
	{
		list(,$classes,$vars,$calls,$errors) = $this->analyze(self::$code);
		
		self::assert_equals(0,count($errors));
		
		$a = $classes['a'];
		/* @var $a PC_Obj_Class */
		self::assert_equals(false,$a->is_abstract());
		self::assert_equals(false,$a->is_interface());
		self::assert_equals(false,$a->is_final());
		self::assert_equals(null,$a->get_super_class());
		self::assert_equals(array(),$a->get_interfaces());
		
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(0),
			(string)$a->get_constant('c')->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$a->get_constant('ME')->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_string('str'),
			(string)$a->get_constant('YOU')->get_type()
		);
		
		self::assert_equals(
			(string)new PC_Obj_Field('',0,'f',PC_Obj_MultiType::create_string('abc'),PC_Obj_Field::V_PRIVATE),
			(string)$a->get_field('f')
		);
		self::assert_equals(
			(string)new PC_Obj_Field(
				'',0,'foo',PC_Obj_MultiType::get_type_by_name('int|string'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('foo')
		);
		self::assert_equals(
			(string)new PC_Obj_Field(
				'',0,'bar',PC_Obj_MultiType::get_type_by_name('int|string'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('bar')
		);
		self::assert_equals(
			(string)new PC_Obj_Field(
				'',0,'a',PC_Obj_MultiType::create_string(),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('a')
		);
		self::assert_equals(
			(string)new PC_Obj_Field(
				'',0,'b',PC_Obj_MultiType::create_string('a'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('b')
		);
		
		$array = PC_Obj_MultiType::create_array(array());
		$array->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(1));
		$array->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(2));
		$array->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(3));
		self::assert_equals(
			(string)new PC_Obj_Field('',0,'p',$array,PC_Obj_Field::V_PROTECTED),
			(string)$a->get_field('p')
		);
		self::assert_equals(
			'public function __construct()',
			(string)$a->get_method('__construct')
		);
		self::assert_equals(
			'protected function test(): void',
			(string)$a->get_method('test')
		);
		self::assert_equals(
			'public function test2(a): a',
			(string)$a->get_method('test2')
		);
		
		$b = $classes['b'];
		/* @var $b PC_Obj_Class */
		self::assert_equals(true,$b->is_abstract());
		self::assert_equals(false,$b->is_interface());
		self::assert_equals(false,$b->is_final());
		self::assert_equals('a',$b->get_super_class());
		self::assert_equals(array('i','j'),$b->get_interfaces());
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(0),
			(string)$b->get_constant('c')->get_type()
		);
		self::assert_equals(null,$b->get_field('f'));
		self::assert_equals(
			(string)new PC_Obj_Field('',0,'p',$array,PC_Obj_Field::V_PROTECTED),
			(string)$b->get_field('p')
		);
		
		$i = $classes['i'];
		/* @var $i PC_Obj_Class */
		self::assert_equals(true,$i->is_abstract());
		self::assert_equals(true,$i->is_interface());
		self::assert_equals(false,$i->is_final());
		self::assert_equals(array('i1','i2'),$i->get_interfaces());
		self::assert_equals(
			'public abstract function doSomething(): string',
			(string)$i->get_method('doSomething')
		);
		
		$x = $classes['x'];
		/* @var $x PC_Obj_Class */
		self::assert_equals(false,$x->is_abstract());
		self::assert_equals(false,$x->is_interface());
		self::assert_equals(true,$x->is_final());
		self::assert_equals('b',$x->get_super_class());
		self::assert_equals(array('i'),$x->get_interfaces());
		self::assert_equals(
			'public function doSomething(): string',
			(string)$x->get_method('doSomething')
		);
		self::assert_equals(
			'public function test2(b): b',
			(string)$x->get_method('test2')
		);
		self::assert_equals(
			'public static function mystatic(): void',
			(string)$x->get_method('mystatic')
		);
		$field = new PC_Obj_Field('',0,'var',PC_Obj_MultiType::create_int(4),PC_Obj_Field::V_PRIVATE);
		$field->set_static(true);
		self::assert_equals(
			(string)$field,
			(string)$x->get_field('var')
		);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['a']->get_type());
		self::assert_equals('a',(string)$global['b']->get_type());
		self::assert_equals('a',(string)$global['c']->get_type());
		self::assert_equals('x',(string)$global['d']->get_type());
		self::assert_equals('b',(string)$global['e']->get_type());
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$global['f']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(1),
			(string)$global['g']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$global['h']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_object('b'),
			(string)$global['i']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(),
			(string)$global['j']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_string(),
			(string)$global['n']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_int(95),
			(string)$global['o']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_object('a'),
			(string)$global['q']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_object('b'),
			(string)$global['r']->get_type()
		);
		
		// check calls
		$i = 0;
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'strstr(integer=4, string=str)');
		self::assert_call($calls[$i++],'b','get42',true);
		self::assert_call($calls[$i++],'a','test',false);
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy(integer=2)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy(integer=3)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy(integer=6)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy(unknown)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy(unknown)');
		self::assert_call($calls[$i++],'a','__construct',false);
		self::assert_call($calls[$i++],'a','test2',false);
		self::assert_call($calls[$i++],'x','__construct',false);
		self::assert_call($calls[$i++],'x','test2',false);
		self::assert_call($calls[$i++],'x','test2',false);
		self::assert_call($calls[$i++],'b','test2',false);
		self::assert_call($calls[$i++],'b','sdf',true);
		self::assert_call($calls[$i++],'x','partest',false);
		self::assert_call($calls[$i++],'a','__construct',false);
		self::assert_call($calls[$i++],'x','__construct',false);
		self::assert_call($calls[$i++],'a','test2',false);
		self::assert_call($calls[$i++],'x','test2',false);
	}

	private static function assert_call($call,$class,$method,$static)
	{
		self::assert_equals($class,$call->get_class());
		self::assert_equals($method,$call->get_function());
		self::assert_equals($static,$call->is_static());
	}
	
	public function test_fields()
	{
		$code = '<?php
class A {
	public function foo() {
		$this->a = 2;
		$b = $this->x + 2;
		$c = $this->y[2];
	}
}
$a = new A();
$a->asd = 4;
$b = $a->foo;
?>';

		list(,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(5,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assert_regex('/Access of not-existing field "a" of class "#A#"/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assert_regex('/Access of not-existing field "x" of class "#A#"/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assert_regex('/Access of not-existing field "y" of class "#A#"/',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assert_regex('/Access of not-existing field "asd" of class "#A#"/',$error->get_msg());
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assert_regex('/Access of not-existing field "foo" of class "#A#"/',$error->get_msg());
	}
	
	public function test_modifiers()
	{
		$code = '<?php
class A {
	public function foo() {
		$this->priv();
		$this->prot();
	}
	
	/** @param A $a */
	public function bar(A $a) {
		$a->priv();
		$a->prot();
	}
	
	private function priv() {
	}
	protected function prot() {
	}
}

class B extends A {
	private function __construct() {
	}
	
	/** @param B $b */
	public function test(B $b) {
		$this->prot();
		$b->prot();
		$this->priv();
		$b->priv();
	}
}

$a = new A();
$a->foo();
$a->priv();
$a->prot();
$b = new B();
?>';

		list(,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(7,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "priv" does not exist in the class "#B#"!/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_MISSING,$error->get_type());
		self::assert_regex('/The method "priv" does not exist in the class "#B#"!/',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "A::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[5];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "A::prot" is protected at this location/',$error->get_msg());
		
		$error = $errors[6];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::__construct" is private at this location/',$error->get_msg());
	}
	
	public function test_anon()
	{
		$code = '<?php
$a = new class extends A implements I {
	public function test() {
	}
};

$a->test();
?>';

		list(,$classes,$vars,$calls,$errors) = $this->analyze($code);
		
		self::assert_equals(0,count($errors));
		
		$anon = $classes['#anon1'];
		/* @var $anon PC_Obj_Class */
		self::assert_equals(false,$anon->is_abstract());
		self::assert_equals(false,$anon->is_interface());
		self::assert_equals(true,$anon->is_final());
		self::assert_equals(true,$anon->is_anonymous());
		self::assert_equals('A',$anon->get_super_class());
		self::assert_equals(array('I'),$anon->get_interfaces());
		
		self::assert_equals(
			'public function test(): void',
			(string)$anon->get_method('test')
		);
		
		$a = $vars[PC_Obj_Variable::SCOPE_GLOBAL]['a'];
		self::assert_equals('#anon1',$a->get_type());
	}
	
	public function test_spec()
	{
		$code = '<?php
class A {}
class B extends A {}
interface I {}
interface J extends I {}
class C implements I {}
class D implements J {}
class E extends B implements J {}

/** @return A */
function a() {
	return new A;
	return new B;
	return new E;
}

/** @return B */
function b() {
	return new B;
	return new E;
}

/** @return I */
function c() {
	return new C;
	return new D;
	return new E;
}

/**
 * @param A $a
 * @param B $b
 * @param I $c
 */
function d($a,$b,$c) {
}

d(new A,new B,new C);
d(new B,new E,new D);
d(new E,new B,new E);
?>';

		list(,,,,$errors) = $this->analyze($code);

		self::assert_equals(0,count($errors));
	}
	
	public function test_case()
	{
		$code = '<?php
class B {
	const XYZ = 3;
	protected $AB = 1;
}
interface i {}
class A extends b implements I {
	const ABC = 2;
	
	private static $x = 4;
	PRIVATE $vAr = 1;
	
	Public function __CONSTRuct() {}
	
	PUBLIC STATIC function FOO() {
		$a = SELF::abc + sElf::$x + PARENT::XYZ + 1;
	}
	/**
	 * @return b
	 * @throws B
	 */
	public function bar() {
		$a = parent::$AB;
		throw new b();
		
		return new b();
	}
}

$a = new A();
?>';
		
		list(,$classes,$vars,,$errors) = $this->analyze($code);
		
		self::assert_equals(0,count($errors));
		
		$a = $classes['a'];
		self::assert_true($a->is_implementing('I'));
		
		self::assert_equals('const ABC[integer=2]',$a->get_constant('abc'));
		self::assert_equals('private vAr[integer=1]',$a->get_field('var'));
		self::assert_equals('public static function FOO(): void',$a->get_method('foo'));
		self::assert_equals('public function bar(): b throws B',$a->get_method('bar'));
		self::assert_equals('public function __CONSTRuct(): void',$a->get_method('__construct'));
		
		self::assert_equals('A::FOO[a = integer=10]',$vars['A::FOO']['a']);
		self::assert_equals('A::bar[a = integer=1]',$vars['A::bar']['a']);
	}
}
