<?php
/**
 * Tests more expressions
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

class PC_Tests_Exprs2 extends PC_UnitTest
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

$ba = 1 ? 0 : 2;
$bb = 4 ? "bla" : "blub";
$bc = $_ ? 1 : 2;
$bd = $_ ? "foo" : 1;
$be = (1) ? ((2) ? "a" : "b") : "c";
$bf = 1 ?: -1;
$bg = "f" ?: "g";
?>';
	
	public function test_exprs2()
	{
		list(,,$vars,,,) = $this->analyze(self::$code);
		
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
