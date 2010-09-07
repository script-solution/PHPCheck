<?php
/**
 * Tests OOP-functionality
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_OOP extends PHPUnit_Framework_Testcase
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
	public function doSomething() {
		// nothing
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
			'public function <b>__construct</b>(): unknown',
			(string)$a->get_method('__construct')
		);
		self::assertEquals(
			'private function <b>test</b>(): unknown',
			(string)$a->get_method('test')
		);
		self::assertEquals(
			'protected function <b>test2</b>(a): a',
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
			'public abstract function <b>doSomething</b>(): string',
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
			'public function <b>doSomething</b>(): string',
			(string)$x->get_method('doSomething')
		);
		self::assertEquals(
			'protected function <b>test2</b>(b): b',
			(string)$x->get_method('test2')
		);
		self::assertEquals(
			'public static function <b>mystatic</b>(): unknown',
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
			(string)PC_Obj_MultiType::create_int(74),
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
		$this->assertCall($calls[0],'b','get42',true);
		$this->assertCall($calls[1],'a','test',false);
		$this->assertCall($calls[2],'a','__construct',false);
		$this->assertCall($calls[3],'a','test2',false);
		$this->assertCall($calls[4],'x','__construct',false);
		$this->assertCall($calls[5],'x','test2',false);
		$this->assertCall($calls[6],'x','test2',false);
		$this->assertCall($calls[7],'b','test2',false);
		$this->assertCall($calls[8],'b','sdf',true);
		$this->assertCall($calls[9],'x','partest',false);
		$this->assertCall($calls[12],'a','test2',false);
		$this->assertCall($calls[13],'b','test2',false);
	}
	
	private function assertCall($call,$class,$method,$static)
	{
		self::assertEquals($class,$call->get_class());
		self::assertEquals($method,$call->get_function());
		self::assertEquals($static,$call->is_static());
	}
}
?>