<?php
/**
 * Tests variable-definitions
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

class PC_Tests_Vars extends PC_UnitTest
{
	private static $code = '<?php
define("MYCONST",123);
$i1 = +1;
$i2 = -412;
$i3 = MYCONST;
$i4 = (int)"abc";
$f1 = 0.5;
$f2 = 0.123;
$f3 = 1.0;
$f4 = (float)(string)2;
$s1="my\'str";
$s2
= \'str2\';
$s3 = "ab $b c\'a\\\\\""."bla";
$s4 = "ab c\'a\\\\\""."bla";
$b1 = true;
$b2 = false;
$a1 = array();
$a2 = array(1);
$a3 = ARRAY(1,2,3);
$a4 = array(1 => 2,3 => 4,5 => 6);
$a5 = array(\'a\' => 1,2,3,\'4\');
$a6 = array(array(array(1,2,3),4),5);
$a7 = (array)1;
$a8 = 4;
unset($a8);
$a9 = "foo";
$a10 = 123;
unset($a9,$a10);

/**
 * @param array $a
 * @return int
 */
function x($a,MyClass $b) {
	global $b1;
	$i1 = $a;
	$i2 = $i1;
	return $a;
}
?>';
	
	public function testVars()
	{
		list(,,$vars,,$errors,) = $this->analyze(self::$code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['i1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(-412),(string)$global['i2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(123),(string)$global['i3']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(0),(string)$global['i4']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(0.5),(string)$global['f1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(0.123),(string)$global['f2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(1.0),(string)$global['f3']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_float(2.0),(string)$global['f4']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string('my\'str'),(string)$global['s1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string('str2'),(string)$global['s2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['s3']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string('ab c\'a\\\\\"bla'),(string)$global['s4']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$global['b1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(false),(string)$global['b2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_array(array()),(string)$global['a1']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value(array(1)));
		self::assert_equals((string)$array,(string)$global['a2']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value(array(1,2,3)));
		self::assert_equals((string)$array,(string)$global['a3']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value(array(1 => 2,3 => 4,5 => 6)));
		self::assert_equals((string)$array,(string)$global['a4']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value(array('a' => 1,2,3,'4')));
		self::assert_equals((string)$array,(string)$global['a5']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value(array(array(array(1,2,3),4),5)));
		self::assert_equals((string)$array,(string)$global['a6']->get_type());
		$array = new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value((array)1));
		self::assert_equals((string)$array,(string)$global['a7']->get_type());
		self::assert_equals(false,isset($global['a8']));
		self::assert_equals(false,isset($global['a9']));
		self::assert_equals(false,isset($global['a10']));
		
		$x = $vars['x'];
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$x['a']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_object('MyClass'),(string)$x['b']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$x['i1']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$x['i2']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(true),(string)$x['b1']->get_type());
	}
}
