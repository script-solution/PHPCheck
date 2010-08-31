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
$b = 0;
$b[] = 4;
$d = array(0,array(1),2,3);
$d[1][0] = 2;
?>';
	
	public function testArrays()
	{
		$tscanner = new PC_Compile_TypeScannerFrontend();
		$tscanner->scan(self::$code);
		
		$typecon = new PC_Compile_TypeContainer(0,false);
		$typecon->add_classes($tscanner->get_classes());
		$typecon->add_functions($tscanner->get_functions());
		
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Compile_StatementScanner();
		$ascanner->scan(self::$code,$typecon);
		$vars = $ascanner->get_vars();
		$calls = $ascanner->get_calls();
		
		$args = $calls[0]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,1),(string)$args[0]);
		$args = $calls[1]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,2),(string)$args[0]);
		$args = $calls[2]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,3),(string)$args[0]);
		$args = $calls[3]->get_arguments();
		$type = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$type->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::STRING,'\'abc\''));
		$type->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,2));
		self::assertEquals((string)$type,(string)$args[0]);
		$args = $calls[4]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING,'\'abc\''),(string)$args[0]);
		$args = $calls[5]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,2),(string)$args[0]);
		$args = $calls[6]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$args[0]);
		$args = $calls[7]->get_arguments();
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$args[0]);
		
		$type = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$type->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::OBJECT,null,'a'));
		$type->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,4));
		$type->set_array_type(2,new PC_Obj_Type(PC_Obj_Type::INT,5));
		$type->set_array_type('"Abc"',new PC_Obj_Type(PC_Obj_Type::STRING,'"me"'));
		self::assertEquals((string)$type,(string)$vars[PC_Obj_Variable::SCOPE_GLOBAL]['$a']->get_type());
		$type = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$type->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,4));
		self::assertEquals((string)$type,(string)$vars[PC_Obj_Variable::SCOPE_GLOBAL]['$b']->get_type());
		
		$type = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$type->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,0));
		$subtype = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$subtype->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$type->set_array_type(1,$subtype);
		$type->set_array_type(2,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$type->set_array_type(3,new PC_Obj_Type(PC_Obj_Type::INT,3));
		self::assertEquals((string)$type,(string)$vars[PC_Obj_Variable::SCOPE_GLOBAL]['$d']->get_type());
	}
}
?>