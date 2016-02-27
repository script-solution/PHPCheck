<?php
/**
 * Tests conditions and loops
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

class PC_Tests_CondsNLoops extends PC_UnitTest
{	
	public function test_conditions()
	{
		$code = '<?php
$a = 2;
if($_)
	$a = 3;
else
	$a = 4;
// now, we know that $a is an int, but dont know its value

$b = 1;
if($_)
	$b = "str";
else if($_)
	$b = 12.3;
elseif($_)
	$b = array();
else
	$b = true;
// now, $b is an string, float, array or bool. since all blocks assign a value to $b and we have
// an else-block, we know that its one of these values and not the one from the previous layer

if($_)
	$c = true;
else
	$c = 12 + 3;
// now $c is a bool or an int because it didnt exist before but its assigned in all blocks and
// we have an else-block

if($_)
{
	$d = true;
	$e = false;
}
else
	$f = 12;
// all 3 are unknown since they are not assigned in all blocks and we didnt know them before

$g = 1;
if($_)
	$g++;
else
	;
// we know that $g is an int since its present before and the type doesnt change in any block

$h = 2;
if($_)
	$h = $h - 2;
// same here, without else

$i = 1;
if($_)
{
	$i++;
	$i--;
}
else
	;
// here it exists before and the type and value doesnt change in any block. so we even know
// that its still an integer with value 1
?>';
		
		list(,,$vars,$calls,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['a']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::BOOL,true),
			new PC_Obj_Type(PC_Obj_Type::STRING,"str"),
			new PC_Obj_Type(PC_Obj_Type::FLOAT,12.3),
			new PC_Obj_Type(PC_Obj_Type::TARRAY,array())
		));
		self::assert_equals((string)$type,(string)$global['b']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::INT,15),
			new PC_Obj_Type(PC_Obj_Type::BOOL,true)
		));
		self::assert_equals((string)$type,(string)$global['c']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['d']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['e']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['f']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['g']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['h']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$global['i']->get_type());
	}
	
	public function test_loops()
	{
		$code = '<?php
$a = 0;
while(true)
	$a++;
// now we know that its still an integer, but dont know the value
// because we dont know how often the loop is executed; therefore, when changing a variable in a
// loop, we can never say its value after the loop

$b = "str";
foreach(array() as $_ => $_)
	$b .= "foo";
// same here, string without value

for($c = 0; $c < 10; $c++)
	;
// $c wasnt known before, therefore unknown.
// TODO actually we would know that its an integer because $c = 0 and $c < 10 is always executed

$d = 12;
do {
	$d = true;
}
while(1);
// $d was known before, therefore int or bool

while(true)
{
	$e = 4;
	$e = 5;
}
// $d wasnt known before, therefore unknown.

do {
	$f = 4;
}
while(1);
// $f wasnt known before, therefore unknown.
?>';
		
		list(,,$vars,$calls,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['a']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_string(),(string)$global['b']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['c']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::BOOL,true),
			new PC_Obj_Type(PC_Obj_Type::INT,12)
		));
		self::assert_equals((string)$type,(string)$global['d']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['e']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['f']->get_type());
	}
	
	public function test_nesting()
	{
		$code = '<?php
$a = 0;
while(true)
{
	if($_)
		$a++;
	else
		$a--;
}
// now we know that its still an integer, but dont know the value

foreach($_ as $_)
{
	while(true)
	{
		do {
			$b = 4;
		}
		while(true);
	}
}
// $b is unknown since we didnt know it before

$c = "str";
foreach($_ as $_)
{
	while(true)
	{
		do {
			$c = 1;
		}
		while(true);
	}
}
// $c is an int or string since we knew it before

$d = 1;
if($_)
{
	if($_)
	{
		if($_)
		{
			if($_)
			{
				$d = "str";
			}
		}
	}
}
// $d is an int or string since we knew it before

$e = 1;
if($_)
{
	$e = 2;
	if($_)
	{
		$e = 12.3;
		if($_)
		{
			$e = "str";
			if($_)
			{
				$e = true;
				func1($e);
			}
			func2($e);
		}
		func3($e);
	}
	func4($e);
}
func5($e);
// $e is an int, string, float or bool. note that we even know the value except for the int

if($_)
{
	$f = 0;
	if($_)
	{
		if($_)
			$f = 2;
		else
			$f = 4;
		// here we know that f is an int
		func6($f);
	}
	// here we still know that because $f was assigned in this block before
	func7($f);
}
// here we dont know that anymore since it didnt exist before
?>';
		
		list(,,$vars,$calls,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$global['a']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['b']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::INT,1),
			new PC_Obj_Type(PC_Obj_Type::STRING,"str")
		));
		self::assert_equals((string)$type,(string)$global['c']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::STRING,"str"),
			new PC_Obj_Type(PC_Obj_Type::INT,1)
		));
		self::assert_equals((string)$type,(string)$global['d']->get_type());
		$type = new PC_Obj_MultiType(array(
			new PC_Obj_Type(PC_Obj_Type::BOOL,true),
			new PC_Obj_Type(PC_Obj_Type::STRING,"str"),
			new PC_Obj_Type(PC_Obj_Type::FLOAT,12.3),
			new PC_Obj_Type(PC_Obj_Type::INT)
		));
		self::assert_equals((string)$type,(string)$global['e']->get_type());
		self::assert_equals((string)new PC_Obj_MultiType(),(string)$global['f']->get_type());
		
		self::assert_equals(
			'func1(bool=1)',
			(string)$calls[0]->get_call(null,false)
		);
		self::assert_equals(
			'func2(bool=1 or string=str)',
			(string)$calls[1]->get_call(null,false)
		);
		self::assert_equals(
			'func3(bool=1 or string=str or float=12.3)',
			(string)$calls[2]->get_call(null,false)
		);
		self::assert_equals(
			'func4(bool=1 or string=str or float=12.3 or integer=2)',
			(string)$calls[3]->get_call(null,false)
		);
		self::assert_equals(
			'func5(bool=1 or string=str or float=12.3 or integer)',
			(string)$calls[4]->get_call(null,false)
		);
		self::assert_equals(
			'func6(integer)',
			(string)$calls[5]->get_call(null,false)
		);
		self::assert_equals(
			'func7(integer)',
			(string)$calls[6]->get_call(null,false)
		);
	}
	
	public function test_foreach()
	{
		$code = '<?php
foreach(array(1,2,3) as $k => $v)
	f1($k,$v);

$a = array("foo","bar","test");
foreach($a as &$v)
	f2($v);

foreach(array() as $v)
	f3($v);

foreach(array(1,"str",12.3) as $v)
	f4($v);

foreach(array(0 => 1,"a" => 2,12 => 3) as $k => $v)
	f5($k,$v);

foreach(array(0 => 1,2 => "2",12 => 3) as $k => $v)
	f6($k,$v);

foreach($_ as $k => $v)
	f7($k,$v);

$b = array(
	array(1,2,3),
	array(2,3,4),
	array(3,4,5),
);
foreach($b as list($x,$y,$z))
	f8($x,$y,$z);
?>';
		
		list(,,$vars,$calls,) = $this->analyze($code);
		
		$global = $vars[PC_Obj_Variable::SCOPE_GLOBAL];
		
		$type = new PC_Obj_MultiType();
		self::assert_equals((string)$type,(string)$global['k']->get_type());
		self::assert_equals((string)$type,(string)$global['v']->get_type());
		
		self::assert_equals(
			'f1(integer, integer)',
			(string)$calls[0]->get_call(null,false)
		);
		self::assert_equals(
			'f2(string)',
			(string)$calls[1]->get_call(null,false)
		);
		self::assert_equals(
			'f3(unknown)',
			(string)$calls[2]->get_call(null,false)
		);
		self::assert_equals(
			'f4(unknown)',
			(string)$calls[3]->get_call(null,false)
		);
		self::assert_equals(
			'f5(unknown, integer)',
			(string)$calls[4]->get_call(null,false)
		);
		self::assert_equals(
			'f6(integer, unknown)',
			(string)$calls[5]->get_call(null,false)
		);
		self::assert_equals(
			'f7(unknown, unknown)',
			(string)$calls[6]->get_call(null,false)
		);
		self::assert_equals(
			'f8(unknown, unknown, unknown)',
			(string)$calls[7]->get_call(null,false)
		);
	}
}
