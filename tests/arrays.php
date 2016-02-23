<?php
/**
 * Tests arrays
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

class PC_Tests_Arrays extends PC_UnitTest
{
	public function testArrays()
	{
		$code = '<?php
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
$e = array();
$e{1} = 4;
$e{"foo"} = 5;

class foo {
	public function bar() {
		$a = array(
			0 => 4,
			self::R_TYPESCANNER => array(),
		);
	}
}
?>';
		
		list(,,$vars,$calls,,) = $this->analyze($code);
		
		$args = $calls[0]->get_arguments();
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$args[0]);
		$args = $calls[1]->get_arguments();
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$args[0]);
		$args = $calls[2]->get_arguments();
		self::assert_equals((string)PC_Obj_MultiType::create_int(3),(string)$args[0]);
		$args = $calls[3]->get_arguments();
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_string('abc'));
		$type->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(2));
		self::assert_equals((string)$type,(string)$args[0]);
		$args = $calls[4]->get_arguments();
		self::assert_equals((string)PC_Obj_MultiType::create_string('abc'),(string)$args[0]);
		$args = $calls[5]->get_arguments();
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$args[0]);
		$args = $calls[6]->get_arguments();
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$args[0]);
		$args = $calls[7]->get_arguments();
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$args[0]);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_Type::get_type_by_value(array()),(string)$global['x']->get_type());
		self::assert_equals((string)PC_Obj_Type::get_type_by_value(array(4,5)),(string)$global['y']->get_type());
		self::assert_equals((string)PC_Obj_Type::get_type_by_value(array(4,5,6)),(string)$global['z']->get_type());
		
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_object('a'));
		$type->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(4));
		$type->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(5));
		$type->get_first()->set_array_type('Abc',PC_Obj_MultiType::create_string('me'));
		self::assert_equals((string)$type,(string)$global['a']->get_type());
		
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(0));
		$subtype = PC_Obj_MultiType::create_array();
		$subtype->get_first()->set_array_type(0,PC_Obj_MultiType::create_int(2));
		$type->get_first()->set_array_type(1,$subtype);
		$type->get_first()->set_array_type(2,PC_Obj_MultiType::create_int(2));
		$type->get_first()->set_array_type(3,PC_Obj_MultiType::create_int(3));
		self::assert_equals((string)$type,(string)$global['d']->get_type());
		
		$bar = $vars['foo::bar'];
		self::assert_equals('array',(string)$bar['a']->get_type());
		
		$type = PC_Obj_MultiType::create_array();
		$type->get_first()->set_array_type(1,PC_Obj_MultiType::create_int(4));
		$type->get_first()->set_array_type("foo",PC_Obj_MultiType::create_int(5));
		self::assert_equals((string)$type,(string)$global['e']->get_type());
	}
	
	public function testList()
	{
		$code = '<?php
$a = array(1,2,3);
$b = list($a1,$a2,$a3) = $a;
$a[] = 2;

$c = list($c1,$c2,list($c3,$c4,list($c5)),$c6) = array(
	1,2,array(3,4,array(5)),6
);
?>';
		
		list(,,$vars,$calls,,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(array(1,2,3,2)),
			(string)$global['a']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(array(1,2,3)),
			(string)$global['b']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(1),
			(string)$global['a1']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(2),
			(string)$global['a2']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(3),
			(string)$global['a3']->get_type()
		);
		
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(array(1,2,array(3,4,array(5)),6)),
			(string)$global['c']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(1),
			(string)$global['c1']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(2),
			(string)$global['c2']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(3),
			(string)$global['c3']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(4),
			(string)$global['c4']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(5),
			(string)$global['c5']->get_type()
		);
		self::assert_equals(
			(string)PC_Obj_Type::get_type_by_value(6),
			(string)$global['c6']->get_type()
		);
	}
}
