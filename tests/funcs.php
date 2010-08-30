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
abstract class myc {
	public abstract function doit();
}
class myc2 extends myc {
	public function doit() {
		parent::doit();
	}
}
?>';
	
	public function testFuncs()
	{
		$tscanner = new PC_Compile_TypeScanner();
		$tscanner->scan(self::$code);
		
		$typecon = new PC_Compile_TypeContainer(0,false);
		$typecon->add_classes($tscanner->get_classes());
		$typecon->add_functions($tscanner->get_functions());
		$typecon->add_constants($tscanner->get_constants());
		
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_Null());
		$fin->finalize();
		
		$functions = $tscanner->get_functions();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Compile_StatementScanner();
		$ascanner->scan(self::$code,$typecon);
		
		$func = $functions['a'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('a',$func->get_name());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals(PC_Obj_Type::UNKNOWN,$func->get_return_type()->get_type());
		
		$func = $functions['b'];
		self::assertEquals('b',$func->get_name());
		self::assertEquals(1,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals(PC_Obj_Type::UNKNOWN,$func->get_return_type()->get_type());
		self::assertEquals('string',(string)$func->get_param('$a'));
		
		$func = $functions['c'];
		self::assertEquals('c',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals(PC_Obj_Type::UNKNOWN,$func->get_return_type()->get_type());
		self::assertEquals('array',(string)$func->get_param('$a'));
		self::assertEquals('integer?',(string)$func->get_param('$b'));
		
		$func = $functions['d'];
		self::assertEquals('d',$func->get_name());
		self::assertEquals(3,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals(PC_Obj_Type::INT,$func->get_return_type()->get_type());
		self::assertEquals('integer?',(string)$func->get_param('$a'));
		self::assertEquals('string?',(string)$func->get_param('$b'));
		self::assertEquals('bool?',(string)$func->get_param('$c'));
		
		$func = $functions['doit'];
		self::assertEquals('doit',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(2,$func->get_required_param_count());
		self::assertEquals(PC_Obj_Type::UNKNOWN,$func->get_return_type()->get_type());
		self::assertEquals('MyClass',(string)$func->get_param('$c'));
		self::assertEquals('integer',(string)$func->get_param('$d'));
		
		$calls = $ascanner->get_calls();
		$an = new PC_Compile_Analyzer();
		$an->analyze_calls($typecon,$calls);
		$errors = $an->get_errors();
		self::assertEquals(PC_Obj_Error::E_T_ABSTRACT_METHOD_CALL,$errors[1]->get_type());
	}
}
?>