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
  private function test() {}
  /** @return a */
  protected function test2(a $arg) {
  	return $arg;
  }
  
  public function aaa() {
  	strstr(self::ME,a::YOU);
  }
}

abstract class b extends a implements i,j {
	/** @return b */
	protected function test2(b $arg) {
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
	/** @return a */
	public function partest() {
		return parent::test();
	}
}

interface i extends i1,i2 {
	/**
	 * @return string
	 */
	public function doSomething();
}
final class x extends b implements i {
	private static $var = 4;
	public static $array1 = array(1,2,3);
	public static $array2 = array(array(4,5,6));
	public function doSomething() {
		// nothing
		dummy1(self::$array1[1]);
		dummy2(x::$array1[2]);
		dummy3(x::$array2[0][2]);
		dummy4(x::$array2[0][4]);
		dummy5(x::$array2[1][2]);
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
$i = $d->test2($b)->test2(1);
$j = b::sdf();
$k = $d->partest();
$n = __FILE__;
$o = __LINE__;
$p = array(new a(),new b());
$q = $p[0]->test2($b);
$r = $p[1]->test2($b);
?>';
	
	public function test_oop()
	{
		list(,$classes,$vars,$calls,,) = $this->analyze(self::$code);
		
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
			'private function test(): void',
			(string)$a->get_method('test')
		);
		self::assert_equals(
			'protected function test2(a): a',
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
			'protected function test2(b): b',
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
			(string)PC_Obj_MultiType::create_int(82),
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
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy1(integer=2)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy2(integer=3)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy3(integer=6)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy4(unknown)');
		self::assert_equals((string)$calls[$i++]->get_call(null,false),'dummy5(unknown)');
		self::assert_call($calls[$i++],'a','__construct',false);
		self::assert_call($calls[$i++],'a','test2',false);
		self::assert_call($calls[$i++],'x','__construct',false);
		self::assert_call($calls[$i++],'x','test2',false);
		self::assert_call($calls[$i++],'x','test2',false);
		self::assert_call($calls[$i++],'b','test2',false);
		self::assert_call($calls[$i++],'b','sdf',true);
		self::assert_call($calls[$i++],'x','partest',false);
		self::assert_call($calls[$i++],'a','__construct',false);
		self::assert_call($calls[$i++],'b','__construct',false);
		self::assert_call($calls[$i++],'a','test2',false);
		self::assert_call($calls[$i++],'b','test2',false);
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

		list(,,,,$errors,) = $this->analyze($code);
		
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

		list(,,,,$errors,) = $this->analyze($code);
		
		self::assert_equals(5,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "A::priv" is private at this location/',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "A::prot" is protected at this location/',$error->get_msg());
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_METHOD_VISIBILITY,$error->get_type());
		self::assert_regex('/The function\/method "B::__construct" is private at this location/',$error->get_msg());
	}
}
