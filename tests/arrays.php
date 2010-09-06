<?php
/**
 * Tests arrays
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Arrays extends PHPUnit_Framework_Testcase
{
	private static $code = '<?php
$x = array();
$x[] = 4;
$x[] = 5;
$y = $x;
$x = array();
$z = clone $y;
$z[] = 6;
$c = array(1,2,3,array(array(\'abc\',2)));
func($c[0]);
func($c[1]);
func($c[2]);
func($c[3][0]);
func($c[3][0][0]);
func($c[3][0][1]);
func($c[4]);
func($c[3][0][1][0]);
$a = array();
$a[] = new a(1);
$a[] = 4;
$a[] = 5;
$a["Abc"] = "me";
$d = array(0,array(1),2,3);
$d[1][0] = 2;
?>';
	
	public function testArrays()
	{
		$tscanner = new PC_Engine_TypeScannerFrontend();
		$tscanner->scan(self::$code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Engine_StmtScannerFrontend($typecon);
		$ascanner->scan(self::$code);
		$vars = $ascanner->get_vars();
		$calls = $typecon->get_calls();
		
		$args = $calls[0]->get_arguments();
		self::assertEquals((string)PC_Obj_MultiType::create_int(1),(string)$args[0]);
		$args = $calls[1]->get_arguments();
		self::assertEquals((string)PC_Obj_MultiType::create_int(2),(string)$args[0]);
		$args = $calls[2]->get_arguments();
		self::assertEquals((string)PC_Obj_MultiType::create_int(3),(string)$args[0]);
		$args = $calls[3]->get_arguments();
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_string('abc'));
		$type->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(2));
		self::assertEquals((string)$type,(string)$args[0]);
		$args = $calls[4]->get_arguments();
		self::assertEquals((string)PC_Obj_MultiType::create_string('abc'),(string)$args[0]);
		$args = $calls[5]->get_arguments();
		self::assertEquals((string)PC_Obj_MultiType::create_int(2),(string)$args[0]);
		$args = $calls[6]->get_arguments();
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$args[0]);
		$args = $calls[7]->get_arguments();
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$args[0]);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assertEquals((string)PC_Obj_Type::get_type_by_value(array()),(string)$global['x']->get_type());
		self::assertEquals((string)PC_Obj_Type::get_type_by_value(array(4,5)),(string)$global['y']->get_type());
		self::assertEquals((string)PC_Obj_Type::get_type_by_value(array(4,5,6)),(string)$global['z']->get_type());
		
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_object('a'));
		$type->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(4));
		$type->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(5));
		$type->get_first()->set_array_type('Abc',PC_Obj_MultiType::create_string('me'));
		self::assertEquals((string)$type,(string)$global['a']->get_type());
		
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(0));
		$subtype = PC_Obj_MultiType::create_array();
		$subtype->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(2));
		$type->get_first()->set_array_type(1,$subtype);
		$type->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(2));
		$type->get_first()->set_array_type(3,PC_Obj_MultiType::create_int(3));
		self::assertEquals((string)$type,(string)$global['d']->get_type());
	}
}
?>