<?php
/**
 * Tests function-definitions and calls
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Funcs extends PHPUnit_Framework_Testcase
{
	private static $code = '<?php
function a() {}
/**
 * @param string $a
 */
function b($a) {}

class myc2 extends myc {
	public static function mystatic() {}
	public function doit() {
		parent::doit();
		self::mystatic();
		$this->c(1,2);
	}
	/**
	 * @param array $a
	 * @param int $b
	 */
	protected function c($a,$b = 0) {}
	/**
	 * @param int $a
	 * @param string $b
	 * @param boolean $c
	 * @return int
	 */
	private function d($a = 0,$b = "a",$c = false) {
		$a = $b + $c;
		return $a;
	}
	/**
	 * @param int $d
	 */
	public function doit(MyClass $c,$d) {
		$c->test($d);
	}
}
abstract class myc {
	public abstract function doit();
}
?>';
	
	public function testFuncs()
	{
		$tscanner = new PC_Compile_TypeScannerFrontend();
		$tscanner->scan(self::$code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_Null());
		$fin->finalize();
		
		$functions = $typecon->get_functions();
		$classes = $typecon->get_classes();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Compile_StmtScannerFrontend($typecon);
		$ascanner->scan(self::$code);
		
		$func = $functions['a'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('a',$func->get_name());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		
		$func = $functions['b'];
		self::assertEquals('b',$func->get_name());
		self::assertEquals(1,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('string',(string)$func->get_param('$a'));
		
		$class = $classes['myc2'];
		
		$func = $class->get_method('c');
		self::assertEquals('c',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('array',(string)$func->get_param('$a'));
		self::assertEquals('integer?',(string)$func->get_param('$b'));
		
		$func = $class->get_method('d');
		self::assertEquals('d',$func->get_name());
		self::assertEquals(3,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals((string)PC_Obj_MultiType::create_int(),(string)$func->get_return_type());
		self::assertEquals('integer?',(string)$func->get_param('$a'));
		self::assertEquals('string?',(string)$func->get_param('$b'));
		self::assertEquals('bool?',(string)$func->get_param('$c'));
		
		$func = $class->get_method('doit');
		self::assertEquals('doit',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(2,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('MyClass',(string)$func->get_param('$c'));
		self::assertEquals('integer',(string)$func->get_param('$d'));
		
		$calls = $typecon->get_calls();
		self::assertEquals('myc->doit()',(string)$calls[0]->get_call(false,false));
		self::assertEquals('myc2::mystatic()',(string)$calls[1]->get_call(false,false));
	}
}
?>