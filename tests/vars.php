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
		
		$global = $vars[PC_ActionScanner::SCOPE_GLOBAL];
		self::assertEquals((string)new PC_Type(PC_Type::INT,1),(string)$global['$i1']);
		self::assertEquals((string)new PC_Type(PC_Type::INT,-412),(string)$global['$i2']);
		self::assertEquals((string)new PC_Type(PC_Type::INT,123),(string)$global['$i3']);
		self::assertEquals((string)new PC_Type(PC_Type::INT,0),(string)$global['$i4']);
		self::assertEquals((string)new PC_Type(PC_Type::FLOAT,0.5),(string)$global['$f1']);
		self::assertEquals((string)new PC_Type(PC_Type::FLOAT,0.123),(string)$global['$f2']);
		self::assertEquals((string)new PC_Type(PC_Type::FLOAT,1.0),(string)$global['$f3']);
		self::assertEquals((string)new PC_Type(PC_Type::FLOAT,2.0),(string)$global['$f4']);
		self::assertEquals((string)new PC_Type(PC_Type::STRING,'"my\'str"'),(string)$global['$s1']);
		self::assertEquals((string)new PC_Type(PC_Type::STRING,'\'str2\''),(string)$global['$s2']);
		self::assertEquals((string)new PC_Type(PC_Type::STRING),(string)$global['$s3']);
		self::assertEquals((string)new PC_Type(PC_Type::BOOL,true),(string)$global['$b1']);
		self::assertEquals((string)new PC_Type(PC_Type::BOOL,false),(string)$global['$b2']);
		self::assertEquals((string)new PC_Type(PC_Type::TARRAY),(string)$global['$a1']);
		$array = new PC_Type(PC_Type::TARRAY);
		$array->set_array_type(0,new PC_Type(PC_Type::INT,1));
		self::assertEquals((string)$array,(string)$global['$a2']);
		$array = new PC_Type(PC_Type::TARRAY);
		$array->set_array_type(0,new PC_Type(PC_Type::INT,1));
		$array->set_array_type(1,new PC_Type(PC_Type::INT,2));
		$array->set_array_type(2,new PC_Type(PC_Type::INT,3));
		self::assertEquals((string)$array,(string)$global['$a3']);
		$array = new PC_Type(PC_Type::TARRAY);
		$array->set_array_type(1,new PC_Type(PC_Type::INT,2));
		$array->set_array_type(3,new PC_Type(PC_Type::INT,4));
		$array->set_array_type(5,new PC_Type(PC_Type::INT,6));
		self::assertEquals((string)$array,(string)$global['$a4']);
		$array = new PC_Type(PC_Type::TARRAY);
		$array->set_array_type("'a'",new PC_Type(PC_Type::INT,1));
		$array->set_array_type(0,new PC_Type(PC_Type::INT,2));
		$array->set_array_type(1,new PC_Type(PC_Type::INT,3));
		$array->set_array_type(2,new PC_Type(PC_Type::STRING,"'4'"));
		self::assertEquals((string)$array,(string)$global['$a5']);
		$array = new PC_Type(PC_Type::TARRAY);
		$subarray = new PC_Type(PC_Type::TARRAY);
		$subsubarray = new PC_Type(PC_Type::TARRAY);
		$subsubarray->set_array_type(0,new PC_Type(PC_Type::INT,1));
		$subsubarray->set_array_type(1,new PC_Type(PC_Type::INT,2));
		$subsubarray->set_array_type(2,new PC_Type(PC_Type::INT,3));
		$subarray->set_array_type(0,$subsubarray);
		$subarray->set_array_type(1,new PC_Type(PC_Type::INT,4));
		$array->set_array_type(0,$subarray);
		$array->set_array_type(1,new PC_Type(PC_Type::INT,5));
		self::assertEquals((string)$array,(string)$global['$a6']);
		self::assertEquals((string)new PC_Type(PC_Type::TARRAY),(string)$global['$a7']);
		
		$x = $vars['x'];
		self::assertEquals((string)new PC_Type(PC_Type::TARRAY),(string)$x['$a']);
		self::assertEquals((string)new PC_Type(PC_Type::OBJECT,null,'MyClass'),(string)$x['$b']);
		self::assertEquals((string)new PC_Type(PC_Type::TARRAY),(string)$x['$i1']);
		self::assertEquals((string)new PC_Type(PC_Type::TARRAY),(string)$x['$i2']);
	}
}
?>