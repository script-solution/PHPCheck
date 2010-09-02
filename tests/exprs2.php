<?php
/**
 * Tests more expressions
 *
 * @version			$Id: arrays.php 47 2010-08-31 20:23:10Z nasmussen $
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Exprs2 extends PHPUnit_Framework_Testcase
{
	private static $code = '<?php
/** @return float */
function getfloat() {}
/** @return int */
function getint() {}
/** @return bool */
function getbool() {}

// unknown -> float|int
$a = $_;
$a += 1;

// unknown -> string
$b = $_;
$b .= "foo";

// unknown -> int
$c = $_;
$c |= 1;

// float -> float
$d = getfloat();
$d *= 2;

// int -> int
$e = getint();
$e *= 2;

// float x int -> float
$f = $d * $e;

// string(mystr) + unknown -> string
$g = "mystr" . $_;
$g .= "foo";

// int -> int
$h = getint();
$h++;
++$h;
$h--;
--$h;

// float -> float
$i = getfloat();
$i++;
++$i;
$i--;
--$i;

// bool -> bool
$j = getbool();
$j = $j || true;

// unknown -> bool
$k = $_;
$k = ($k and false);

// unknown -> bool
$l = $_ === $__;
$m = $_ > 3;

// unknown -> type by cast
$n = (int)$_;
$o = (double)$_;
$p = (string)$_;
$q = (array)$_;
$r = (object)$_;
$s = (bool)$_;
$t = (unset)$_;

$u = `foo`;

// we can even say the type/value of variable variables if we can figure out the variable name ^^
$v = "u";
$w = ${$v};
$x = ${"unknown var"};
$y = ${123};

// array-union works when all values are known
$z = array("a" => 1,"c" => 123,"d" => 5) + array("a" => 2,"b" => 4);
$aa = array(array($_,1)) + array(2);
// comparison is just bool
$ab = array(1) === array(1);
$ac = array(1,"2") == array(1,2);
$ad = array(1) <> array($_);
?>';
	
	public function testExprs2()
	{
		$tscanner = new PC_Compile_TypeScannerFrontend();
		$tscanner->scan(self::$code);
		
		$typecon = new PC_Compile_TypeContainer(0,false);
		$typecon->add_classes($tscanner->get_classes());
		$typecon->add_functions($tscanner->get_functions());
		
		$fin = new PC_Compile_TypeFinalizer($typecon,new PC_Compile_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Compile_StmtScannerFrontend();
		$ascanner->scan(self::$code,$typecon);
		$vars = $ascanner->get_vars();
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$global['a']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['b']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT),(string)$global['c']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT),(string)$global['d']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT),(string)$global['e']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT),(string)$global['f']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['g']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT),(string)$global['h']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT),(string)$global['i']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['j']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['k']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['l']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['m']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::INT),(string)$global['n']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::FLOAT),(string)$global['o']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['p']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::TARRAY),(string)$global['q']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$global['r']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['s']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$global['t']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['u']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::STRING),(string)$global['w']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$global['x']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::UNKNOWN),(string)$global['y']->get_type());
		self::assertEquals(
			(string)PC_Obj_Type::get_type_by_value(array("a" => 1,"c" => 123,"d" => 5,"b" => 4)),
			(string)$global['z']->get_type()
		);
		self::assertEquals(
			(string)new PC_Obj_Type(PC_Obj_Type::TARRAY),
			(string)$global['aa']->get_type()
		);
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['ab']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['ac']->get_type());
		self::assertEquals((string)new PC_Obj_Type(PC_Obj_Type::BOOL),(string)$global['ad']->get_type());
	}
}
?>