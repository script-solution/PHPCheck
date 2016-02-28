<?php
/**
 * Tests expressions
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

class PC_Tests_Exprs extends PC_UnitTest
{
	public function test_ops()
	{
		$code = '<?php
$a = 4;
$a += 1;

$b = 3;
$b -= 1;

$c = 12;
$c *= 2;

$d = 4;
$d /= 2;

$e = "foo";
$e .= "bar";

$f = 5;
$f %= 4;

$g = 0x00FF;
$g &= 0xF;

$h = 0x00FF;
$h |= 0xFF00;

$i = 0x00FF;
$i ^= 0xFF00;

$j = 0x00FF;
$j <<= 4;

$k = 0xFF00;
$k >>= 4;

$l1 = 0;
$l2 = $l1++;

$m1 = 0;
$m2 = ++$m1;

$n1 = 1;
$n2 = $n1--;

$o1 = 1;
$o2 = --$o1;

$p = true and true;
$q = false or false;
$r = true && false;
$s = false || true;
$t = true xor false;

$u = 0x0F | 0xF0;
$v = 0xFF & 0xF0;
$w = 0x0F | 0xF0;
$x = "foo" . "bar";
$y = 12 + 13;
$z = 12 - 11;
$aa = 3 * 2;
$ab = 4 / 2;
$ac = 12 % 5;
$ad = 0x0F << 4;
$ae = 0xF0 >> 4;

$af = +(-1 * 2);
$ag = -(4 - 2);
$ah = !true;
$ai = ~0xF0;

$aj = 1 === 1;
$ak = 4 !== "4";
$al = 1 == "1";
$am = 3 != "4";
$an = 123 < 234;
$ao = 123 <= 5;
$ap = 5 > 3;
$aq = 4 >= 1;

class A {}
class B extends A {}
interface I {}
interface J {}
interface K extends I,J {}
class C extends B implements K {}

$A = new A();
$B = new B();
$C = new C();
$ar = $A instanceof A;
$as = $A instanceof b;
$at = $B instanceof A;
$au = $B instanceof B;
$av = $B instanceof i;
$aw = $C instanceof A;
$ax = $C instanceof B;
$ay = $C instanceof I;
$az = $C instanceof j;
$ba = $C instanceof K;

$bb = (int)\'123\';
$bc = (double)1;
$bd = (string)12;
$be = (array)4;
$bf = (object)5;
$bg = (bool)1234;
$bh = (unset)1;

$ca = isset($foo);
$cb = empty($bar);
$cc = <<<EOF
foobar
EOF;

$da = 1 or die();
$db = 2 or exit;
$dc = 0 or die;

$bi = array(
	array(
		"a" => array(1),
		2 => 3
	),
	4 => 5
);
?>';
		
		list(,,$vars,,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(5),(string)$global['a']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$global['b']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(24),(string)$global['c']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$global['d']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string("foobar"),(string)$global['e']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['f']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xF),(string)$global['g']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xFFFF),(string)$global['h']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xFFFF),(string)$global['i']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0x0FF0),(string)$global['j']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0x0FF0),(string)$global['k']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['l1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['l2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['m1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['m2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['n1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['n2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['o1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['o2']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['p']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['q']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['r']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['s']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['t']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xFF),(string)$global['u']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xF0),(string)$global['v']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xFF),(string)$global['w']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string("foobar"),(string)$global['x']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(25),(string)$global['y']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['z']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(6),(string)$global['aa']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$global['ab']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$global['ac']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0xF0),(string)$global['ad']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0x0F),(string)$global['ae']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_int(-2),(string)$global['af']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(-2),(string)$global['ag']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['ah']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(~0xF0),(string)$global['ai']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['aj']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ak']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['al']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['am']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['an']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['ao']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ap']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['aq']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ar']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['as']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['at']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['au']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['av']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['aw']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ax']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ay']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['az']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['ba']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_int(123),(string)$global['bb']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(1),(string)$global['bc']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string("12"),(string)$global['bd']->get_type());
		$ar = PC_Obj_MultiType::create_array(array());
		$ar->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(4));
		self::assert_equals((string)$ar,(string)$global['be']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['bf']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['bg']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['bh']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['ca']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['cb']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['cc']->get_type());
		
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(array(array("a" => array(1),2 => 3),4 => 5)),
			(string)$global['bi']->get_type()
		);
	}
	
	public function test_conversion()
	{
		$code = '<?php
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
?>';
		
		list(,,$vars,,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['a']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['b']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['c']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(),(string)$global['d']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['e']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(),(string)$global['f']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['g']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['h']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(),(string)$global['i']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['j']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['k']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['l']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['m']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['n']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(),(string)$global['o']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['p']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$global['q']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['r']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['s']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['t']->get_type());
	}
	
	public function test_special()
	{
		$code = '<?php
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

$ba = 1 ? 0 : 2;
$bb = 4 ? "bla" : "blub";
$bc = $_ ? 1 : 2;
$bd = $_ ? "foo" : 1;
$be = (1) ? ((2) ? "a" : "b") : "c";
$bf = 1 ?: -1;
$bg = "f" ?: "g";
?>';
		
		list(,,$vars,,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['u']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['w']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['x']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['y']->get_type());
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(array("a" => 1,"c" => 123,"d" => 5,"b" => 4)),
			(string)$global['z']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_MultiType::create_array(),
			(string)$global['aa']->get_type()
		);
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['ab']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['ac']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$global['ad']->get_type());
		
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['ba']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string("bla"),(string)$global['bb']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['bc']->get_type());
		self::assert_equals(
			(string)new PC_Obj_MultiType(array(
				new PC_Obj_Type(PC_Obj_Type::STRING,"foo"),
				new PC_Obj_Type(PC_Obj_Type::INT,1),
			)),
			(string)$global['bd']->get_type()
		);
		self::assert_equals((string)PC_Obj_MultiType::create_string("a"),(string)$global['be']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['bf']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(-1),(string)$global['bg']->get_type());
	}
}
