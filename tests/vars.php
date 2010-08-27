<?php
/**
 * Tests variable-definitions
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Vars extends PHPUnit_Framework_Testcase
{
	private static $code = '<?php
define("MYCONST",123);
$i1 = +1;
$i2 = -412;
$i3 = MYCONST;
$i4 = (int)"abc";
$f1 = .5;
$f2 = 0.123;
$f3 = 1.0;
$f4 = (float)(string)2;
$s1="my\'str";
$s2
= \'str2\';
$s3 = "ab $b c\'a\\\\\""."bla";
$b1 = true;
$b2 = false;
$a1 = array();
$a2 = array(1);
$a3 = ARRAY(1,2,3);
$a4 = array(1 => 2,3 => 4,5 => 6);
$a5 = array(\'a\' => 1,2,3,\'4\');
$a6 = array(array(array(1,2,3),4),5);
$a7 = (array)1;

/**
 * @param array $a
 * @return int
 */
function x($a,MyClass $b) {
	$i1 = $a;
	$i2 = $i1;
	return $a;
}
?>';
	
	public function testVars()
	{
		$tscanner = new PC_Compile_TypeScanner();
		$tscanner->scan(self::$code);
		
		$typecon = new PC_Compile_TypeContainer(0,false);
		$typecon->add_classes($tscanner->get_classes());
		$typecon->add_functions($tscanner->get_functions());
		$typecon->add_constants($tscanner->get_constants());
		
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Compile_StatementScanner();
		$ascanner->scan(self::$code,$typecon);
		$vars = $ascanner->get_vars();
		$calls = $ascanner->get_calls();
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,1),(string)$global['$i1']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,-412),(string)$global['$i2']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,123),(string)$global['$i3']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT,0),(string)$global['$i4']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT,0.5),(string)$global['$f1']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT,0.123),(string)$global['$f2']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT,1.0),(string)$global['$f3']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT,2.0),(string)$global['$f4']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING,'"my\'str"'),(string)$global['$s1']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING,'\'str2\''),(string)$global['$s2']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['$s3']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL,true),(string)$global['$b1']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL,false),(string)$global['$b2']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$global['$a1']->get_type());
		$array = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$array->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,1));
		self::assertEquals((string)$array,(string)$global['$a2']->get_type());
		$array = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$array->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,1));
		$array->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$array->set_array_type(2,new PC_Obj_Type(PC_Obj_Type::INT,3));
		self::assertEquals((string)$array,(string)$global['$a3']->get_type());
		$array = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$array->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$array->set_array_type(3,new PC_Obj_Type(PC_Obj_Type::INT,4));
		$array->set_array_type(5,new PC_Obj_Type(PC_Obj_Type::INT,6));
		self::assertEquals((string)$array,(string)$global['$a4']->get_type());
		$array = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$array->set_array_type("'a'",new PC_Obj_Type(PC_Obj_Type::INT,1));
		$array->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$array->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,3));
		$array->set_array_type(2,new PC_Obj_Type(PC_Obj_Type::STRING,"'4'"));
		self::assertEquals((string)$array,(string)$global['$a5']->get_type());
		$array = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$subarray = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$subsubarray = new PC_Obj_Type(PC_Obj_Type::TARRAY);
		$subsubarray->set_array_type(0,new PC_Obj_Type(PC_Obj_Type::INT,1));
		$subsubarray->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,2));
		$subsubarray->set_array_type(2,new PC_Obj_Type(PC_Obj_Type::INT,3));
		$subarray->set_array_type(0,$subsubarray);
		$subarray->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,4));
		$array->set_array_type(0,$subarray);
		$array->set_array_type(1,new PC_Obj_Type(PC_Obj_Type::INT,5));
		self::assertEquals((string)$array,(string)$global['$a6']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$global['$a7']->get_type());
		
		$x = $vars['x'];
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$x['$a']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::OBJECT,null,'MyClass'),(string)$x['$b']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$x['$i1']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$x['$i2']->get_type());
	}
}
?>