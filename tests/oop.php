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
$l = (1 + 2) * 4;
$m = (1 < 2) ? 1 : 2;
$m2 = (1 < $_) ? "str" : true;
$n = __FILE__;
$o = __LINE__;
$p = array(new a(),new b());
$q = $p[0]->test2($b);
$r = $p[1]->test2($b);
?>';
	
	public function testOOP()
	{
		$tscanner = new PC_Engine_TypeScannerFrontend();
		$tscanner->scan(self::$code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
			
		$classes = $typecon->get_classes();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Engine_StmtScannerFrontend($typecon);
		$ascanner->scan(self::$code);
		$vars = $ascanner->get_vars();
		
		$a = $classes['a'];
		/* @var $a PC_Obj_Class */
		self::assertEquals(false,$a->is_abstract());
		self::assertEquals(false,$a->is_interface());
		self::assertEquals(false,$a->is_final());
		self::assertEquals(null,$a->get_super_class());
		self::assertEquals(array(),$a->get_interfaces());
		
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(0),
			(string)$a->get_constant('c')->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$a->get_constant('ME')->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_string('str'),
			(string)$a->get_constant('YOU')->get_type()
		);
		
		self::assertEquals(
			(string)new PC_Obj_Field('',0,'f',PC_Obj_MultiType::create_string('abc'),PC_Obj_Field::V_PRIVATE),
			(string)$a->get_field('f')
		);
		self::assertEquals(
			(string)new PC_Obj_Field(
				'',0,'foo',PC_Obj_MultiType::get_type_by_name('int|string'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('foo')
		);
		self::assertEquals(
			(string)new PC_Obj_Field(
				'',0,'bar',PC_Obj_MultiType::get_type_by_name('int|string'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('bar')
		);
		self::assertEquals(
			(string)new PC_Obj_Field(
				'',0,'a',PC_Obj_MultiType::create_string(),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('a')
		);
		self::assertEquals(
			(string)new PC_Obj_Field(
				'',0,'b',PC_Obj_MultiType::create_string('a'),PC_Obj_Field::V_PRIVATE
			),
			(string)$a->get_field('b')
		);
		
		$array = PC_Obj_MultiType::create_array();
		$array->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(1));
		$array->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(2));
		$array->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(3));
		self::assertEquals(
			(string)new PC_Obj_Field('',0,'p',$array,PC_Obj_Field::V_PROTECTED),
			(string)$a->get_field('p')
		);
		self::assertEquals(
			'public function __construct(): unknown',
			(string)$a->get_method('__construct')
		);
		self::assertEquals(
			'private function test(): unknown',
			(string)$a->get_method('test')
		);
		self::assertEquals(
			'protected function test2(a): a',
			(string)$a->get_method('test2')
		);
		
		$b = $classes['b'];
		/* @var $b PC_Obj_Class */
		self::assertEquals(true,$b->is_abstract());
		self::assertEquals(false,$b->is_interface());
		self::assertEquals(false,$b->is_final());
		self::assertEquals('a',$b->get_super_class());
		self::assertEquals(array('i','j'),$b->get_interfaces());
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(0),
			(string)$b->get_constant('c')->get_type()
		);
		self::assertEquals(null,$b->get_field('f'));
		self::assertEquals(
			(string)new PC_Obj_Field('',0,'p',$array,PC_Obj_Field::V_PROTECTED),
			(string)$b->get_field('p')
		);
		
		$i = $classes['i'];
		/* @var $i PC_Obj_Class */
		self::assertEquals(true,$i->is_abstract());
		self::assertEquals(true,$i->is_interface());
		self::assertEquals(false,$i->is_final());
		self::assertEquals(array('i1','i2'),$i->get_interfaces());
		self::assertEquals(
			'public abstract function doSomething(): string',
			(string)$i->get_method('doSomething')
		);
		
		$x = $classes['x'];
		/* @var $x PC_Obj_Class */
		self::assertEquals(false,$x->is_abstract());
		self::assertEquals(false,$x->is_interface());
		self::assertEquals(true,$x->is_final());
		self::assertEquals('b',$x->get_super_class());
		self::assertEquals(array('i'),$x->get_interfaces());
		self::assertEquals(
			'public function doSomething(): string',
			(string)$x->get_method('doSomething')
		);
		self::assertEquals(
			'protected function test2(b): b',
			(string)$x->get_method('test2')
		);
		self::assertEquals(
			'public static function mystatic(): unknown',
			(string)$x->get_method('mystatic')
		);
		$field = new PC_Obj_Field('',0,'var',PC_Obj_MultiType::create_int(4),PC_Obj_Field::V_PRIVATE);
		$field->set_static(true);
		self::assertEquals(
			(string)$field,
			(string)$x->get_field('var')
		);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assertEquals((string)PC_Obj_MultiType::create_int(0),(string)$global['a']->get_type());
		self::assertEquals('a',(string)$global['b']->get_type());
		self::assertEquals('a',(string)$global['c']->get_type());
		self::assertEquals('x',(string)$global['d']->get_type());
		self::assertEquals('b',(string)$global['e']->get_type());
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$global['f']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(1),
			(string)$global['g']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(4),
			(string)$global['h']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_object('b'),
			(string)$global['i']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(),
			(string)$global['j']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(12),
			(string)$global['l']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(1),
			(string)$global['m']->get_type()
		);
		self::assertEquals(
			(string)new PC_Obj_MultiType(array(
				new PC_Obj_Type(PC_Obj_Type::STRING,'str'),
				new PC_Obj_Type(PC_Obj_Type::BOOL,true)
			)),
			(string)$global['m2']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_string(),
			(string)$global['n']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_int(81),
			(string)$global['o']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_object('a'),
			(string)$global['q']->get_type()
		);
		self::assertEquals(
			(string)PC_Obj_MultiType::create_object('b'),
			(string)$global['r']->get_type()
		);
		
		// check calls
		$calls = $typecon->get_calls();
		$i = 0;
		self::assertCall($calls[$i++],'b','get42',true);
		self::assertCall($calls[$i++],'a','test',false);
		self::assertEquals((string)$calls[$i++]->get_call(false,false),'dummy1(integer=2)');
		self::assertEquals((string)$calls[$i++]->get_call(false,false),'dummy2(integer=3)');
		self::assertEquals((string)$calls[$i++]->get_call(false,false),'dummy3(integer=6)');
		self::assertEquals((string)$calls[$i++]->get_call(false,false),'dummy4(unknown)');
		self::assertEquals((string)$calls[$i++]->get_call(false,false),'dummy5(unknown)');
		self::assertCall($calls[$i++],'a','__construct',false);
		self::assertCall($calls[$i++],'a','test2',false);
		self::assertCall($calls[$i++],'x','__construct',false);
		self::assertCall($calls[$i++],'x','test2',false);
		self::assertCall($calls[$i++],'x','test2',false);
		self::assertCall($calls[$i++],'b','test2',false);
		self::assertCall($calls[$i++],'b','sdf',true);
		self::assertCall($calls[$i++],'x','partest',false);
		self::assertCall($calls[$i++],'a','__construct',false);
		self::assertCall($calls[$i++],'b','__construct',false);
		self::assertCall($calls[$i++],'a','test2',false);
		self::assertCall($calls[$i++],'b','test2',false);
	}

	private static function assertCall($call,$class,$method,$static)
	{
		self::assertEquals($class,$call->get_class());
		self::assertEquals($method,$call->get_function());
		self::assertEquals($static,$call->is_static());
	}
	
	public function testFields()
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
		
		$tscanner = new PC_Engine_TypeScannerFrontend();
		$tscanner->scan($code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Engine_StmtScannerFrontend($typecon);
		$ascanner->scan($code);
		
		$errors = $typecon->get_errors();
		self::assertEquals(5,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assertRegExp('/Access of not-existing field "a" of class "#A#"/',$error->get_msg());
		
		$error = $errors[1];
		self::assertEquals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assertRegExp('/Access of not-existing field "x" of class "#A#"/',$error->get_msg());
		
		$error = $errors[2];
		self::assertEquals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assertRegExp('/Access of not-existing field "y" of class "#A#"/',$error->get_msg());
		
		$error = $errors[3];
		self::assertEquals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assertRegExp('/Access of not-existing field "asd" of class "#A#"/',$error->get_msg());
		
		$error = $errors[4];
		self::assertEquals(PC_Obj_Error::E_S_NOT_EXISTING_FIELD,$error->get_type());
		self::assertRegExp('/Access of not-existing field "foo" of class "#A#"/',$error->get_msg());
	}
}
