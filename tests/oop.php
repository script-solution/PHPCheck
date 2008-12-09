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
  const c = 0;
  private $f = "abc";
  protected $p = array(1,2,3);
  public $pub;
  public function __construct() {}
  private function test() {}
  /** @return a */
  protected function test2(a $arg) {
  	return $arg;
  }
}

abstract class b extends a {
	/** @return b */
	protected function test2(b $arg) {
		return $arg;
	}
}

interface i {
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
?>';
	
	public function testOOP()
	{
		global $code;
		$tscanner = new PC_TypeScanner();
		$tscanner->scan(self::$code);
		$tscanner->finish();
			
		$functions = $tscanner->get_functions();
		$classes = $tscanner->get_classes();
		$constants = $tscanner->get_constants();
		
		// scan files for function-calls and variables
		$ascanner = new PC_ActionScanner();
		$ascanner->scan(self::$code,$functions,$classes,$constants);
		$vars = $ascanner->get_vars();
		$calls = $ascanner->get_calls();
		
		$a = $classes['a'];
		/* @var $a PC_Class */
		self::assertEquals(false,$a->is_abstract());
		self::assertEquals(false,$a->is_interface());
		self::assertEquals(false,$a->is_final());
		self::assertEquals(null,$a->get_super_class());
		self::assertEquals(array(),$a->get_interfaces());
		self::assertEquals((string)new PC_Type(PC_Type::INT,0),(string)$a->get_constant('c'));
		self::assertEquals(
			(string)new PC_Field('$f',new PC_Type(PC_Type::STRING,'"abc"'),PC_Field::V_PRIVATE),
			(string)$a->get_field('$f')
		);
		$array = new PC_Type(PC_Type::TARRAY);
		/*$array->set_array_type(0,1);
		$array->set_array_type(1,2);
		$array->set_array_type(2,3);*/
		self::assertEquals(
			(string)new PC_Field('$p',$array,PC_Field::V_PROTECTED),
			(string)$a->get_field('$p')
		);
		self::assertEquals(
			'public function <b>__construct</b>()',
			(string)$a->get_method('__construct')
		);
		self::assertEquals(
			'private function <b>test</b>()',
			(string)$a->get_method('test')
		);
		self::assertEquals(
			'protected function <b>test2</b>(a)',
			(string)$a->get_method('test2')
		);
		
		$b = $classes['b'];
		/* @var $b PC_Class */
		self::assertEquals(true,$b->is_abstract());
		self::assertEquals(false,$b->is_interface());
		self::assertEquals(false,$b->is_final());
		self::assertEquals('a',$b->get_super_class());
		self::assertEquals(array(),$b->get_interfaces());
		self::assertEquals((string)new PC_Type(PC_Type::INT,0),(string)$b->get_constant('c'));
		self::assertEquals(null,$b->get_field('$f'));
		self::assertEquals(
			(string)new PC_Field('$p',$array,PC_Field::V_PROTECTED),
			(string)$b->get_field('$p')
		);
		
		$i = $classes['i'];
		/* @var $i PC_Class */
		self::assertEquals(false,$i->is_abstract());
		self::assertEquals(true,$i->is_interface());
		self::assertEquals(false,$i->is_final());
		self::assertEquals(
			'public function <b>doSomething</b>()',
			(string)$i->get_method('doSomething')
		);
		
		$x = $classes['x'];
		/* @var $x PC_Class */
		self::assertEquals(false,$x->is_abstract());
		self::assertEquals(false,$x->is_interface());
		self::assertEquals(true,$x->is_final());
		self::assertEquals('b',$x->get_super_class());
		self::assertEquals(array('i'),$x->get_interfaces());
		self::assertEquals(
			'public function <b>doSomething</b>()',
			(string)$x->get_method('doSomething')
		);
		self::assertEquals(
			'protected function <b>test2</b>(b)',
			(string)$x->get_method('test2')
		);
		self::assertEquals(
			'public static function <b>mystatic</b>()',
			(string)$x->get_method('mystatic')
		);
		$field = new PC_Field('$var',new PC_Type(PC_Type::INT,4),PC_Field::V_PRIVATE);
		$field->set_static(true);
		self::assertEquals(
			(string)$field,
			(string)$x->get_field('$var')
		);
		
		$global = $vars[PC_ActionScanner::SCOPE_GLOBAL];
		self::assertEquals('integer=0',(string)$global['$a']);
		self::assertEquals('a',(string)$global['$b']);
		self::assertEquals('a',(string)$global['$c']);
		self::assertEquals('x',(string)$global['$d']);
		self::assertEquals('b',(string)$global['$e']);
	}
}
?>