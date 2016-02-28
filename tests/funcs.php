<?php
/**
 * Tests function-definitions and calls
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

class PC_Tests_Funcs extends PC_UnitTest
{
	public function test_funcs()
	{
		$code = '<?php
function a() {}
/**
 * @param string $a
 */
function b($a) {}

class myc2 extends myc {
	public static function mystatic() {}
	public function doit() {
		parent::doit();
		self::mystatic();
		$this->c(1,2);
	}
	/**
	 * @param array $a
	 * @param int $b
	 */
	protected function c($a,$b = 0) {}
	/**
	 * @param int $a
	 * @param string $b
	 * @param boolean $c
	 * @return int
	 */
	private function d($a = 0,$b = "a",$c = false) {
		$a = $b + $c;
		return $a;
	}
	/**
	 * @param int $d
	 */
	public function doit(MyClass $c,$d) {
		$c->test($d);
	}
}
abstract class myc {
	public abstract function doit();
}
?>';
	
		list($functions,$classes,,$calls,) = $this->analyze($code);
		
		$func = $functions['a'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('a',$func->get_name());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals((string)PC_Obj_MultiType::create_void(),(string)$func->get_return_type());
		
		$func = $functions['b'];
		self::assert_equals('b',$func->get_name());
		self::assert_equals(1,$func->get_param_count());
		self::assert_equals(1,$func->get_required_param_count());
		self::assert_equals((string)PC_Obj_MultiType::create_void(),(string)$func->get_return_type());
		self::assert_equals('string',(string)$func->get_param('a'));
		
		$class = $classes['myc2'];
		
		$func = $class->get_method('c');
		self::assert_equals('c',$func->get_name());
		self::assert_equals(2,$func->get_param_count());
		self::assert_equals(1,$func->get_required_param_count());
		self::assert_equals((string)PC_Obj_MultiType::create_void(),(string)$func->get_return_type());
		self::assert_equals('array',(string)$func->get_param('a'));
		self::assert_equals('integer?',(string)$func->get_param('b'));
		
		$func = $class->get_method('d');
		self::assert_equals('d',$func->get_name());
		self::assert_equals(3,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$func->get_return_type());
		self::assert_equals('integer?',(string)$func->get_param('a'));
		self::assert_equals('string?',(string)$func->get_param('b'));
		self::assert_equals('bool?',(string)$func->get_param('c'));
		
		$func = $class->get_method('doit');
		self::assert_equals('doit',$func->get_name());
		self::assert_equals(2,$func->get_param_count());
		self::assert_equals(2,$func->get_required_param_count());
		self::assert_equals((string)PC_Obj_MultiType::create_void(),(string)$func->get_return_type());
		self::assert_equals('MyClass',(string)$func->get_param('c'));
		self::assert_equals('integer',(string)$func->get_param('d'));
		
		self::assert_equals('myc->doit()',(string)$calls[0]->get_call(null,false));
		self::assert_equals('myc2::mystatic()',(string)$calls[1]->get_call(null,false));
	}
	
	public function test_nesting()
	{
		$code = '<?php
class A {
	function a() {
		$a = 1;
		function b() {
			$b = 2;
			function c() {
				$c = 3;
				function d() {
					$d = 4;
				}
				f3($c);
			}
			f2($b);
		}
		f1($a);
	}
}

function e() {
	$e = 5;
	function f() {
		$f = 6;
		class B {
			function g() {
				static $h = 4;
				$g = 7;
			}
		}
	}
}
?>';
		
		list(,,$vars,$calls,) = $this->analyze($code);
		
		self::assert_equals('f3(integer=3)',(string)$calls[0]->get_call(null,false));
		self::assert_equals('f2(integer=2)',(string)$calls[1]->get_call(null,false));
		self::assert_equals('f1(integer=1)',(string)$calls[2]->get_call(null,false));
		
		self::assert_equals((string)PC_Obj_MultiType::create_int(1),(string)$vars['A::a']['a']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(2),(string)$vars['b']['b']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(3),(string)$vars['c']['c']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(4),(string)$vars['d']['d']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(5),(string)$vars['e']['e']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(6),(string)$vars['f']['f']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(7),(string)$vars['B::g']['g']->get_type());
		self::assert_equals((string)PC_Obj_MultiType::create_int(4),(string)$vars['B::g']['h']->get_type());
	}
	
	public function test_anon()
	{
		$code = '<?php
$a = function() {};
$b = function($arg1) {};
$c = function(int $arg1,float $arg2) {};
$g = function() {};
$d = function($arg1,$arg2) { return 1; };
$e = function() use($a,$c) {
	$x = 1+1;
	return $a;
};
$f = function() use(&$b) { return $b; };

$b(function() {
	$x = 3.2;
});

class A {
	public function foo() {
		$a = function() {
			return 1;
		};
	}
}
?>';
		
		list($functions,$classes,$vars,$calls,$errors) = $this->analyze($code);

		self::assert_equals(0,count($errors));
		
		$func = $functions['#anon1'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon1',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals('void',(string)$func->get_return_type());
		
		$func = $functions['#anon2'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon2',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(1,$func->get_param_count());
		self::assert_equals(1,$func->get_required_param_count());
		self::assert_equals('unknown',(string)$func->get_param('arg1'));
		self::assert_equals('void',(string)$func->get_return_type());
		
		$x = $vars['#anon2'];
		// TODO actually, we could get the type from the outer scope
		self::assert_equals('unknown',(string)$x['arg1']->get_type());
		
		$func = $functions['#anon3'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon3',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(2,$func->get_param_count());
		self::assert_equals(2,$func->get_required_param_count());
		self::assert_equals('integer',(string)$func->get_param('arg1'));
		self::assert_equals('float',(string)$func->get_param('arg2'));
		self::assert_equals('void',(string)$func->get_return_type());
		
		$x = $vars['#anon3'];
		self::assert_equals('integer',(string)$x['arg1']->get_type());
		self::assert_equals('float',(string)$x['arg2']->get_type());
		
		$func = $functions['#anon4'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon4',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals('void',(string)$func->get_return_type());
		
		$func = $functions['#anon5'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon5',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(2,$func->get_param_count());
		self::assert_equals(2,$func->get_required_param_count());
		self::assert_equals('unknown',(string)$func->get_param('arg1'));
		self::assert_equals('unknown',(string)$func->get_param('arg2'));
		self::assert_equals('integer=1',(string)$func->get_return_type());
		
		$x = $vars['#anon5'];
		self::assert_equals('unknown',(string)$x['arg1']->get_type());
		self::assert_equals('unknown',(string)$x['arg2']->get_type());
		
		$func = $functions['#anon6'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon6',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals('callable',(string)$func->get_return_type());
		
		$x = $vars['#anon6'];
		self::assert_equals('callable',(string)$x['a']->get_type());
		self::assert_equals('callable',(string)$x['c']->get_type());
		self::assert_equals('integer=2',(string)$x['x']->get_type());
		
		$func = $functions['#anon7'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon7',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals('callable',(string)$func->get_return_type());
		
		$x = $vars['#anon7'];
		self::assert_equals('callable',(string)$x['b']->get_type());
		
		$func = $functions['#anon8'];
		/* @var $func PC_Obj_Method */
		self::assert_equals('#anon8',$func->get_name());
		self::assert_equals(true,$func->is_anonymous());
		self::assert_equals(0,$func->get_param_count());
		self::assert_equals(0,$func->get_required_param_count());
		self::assert_equals('void',(string)$func->get_return_type());
		
		$x = $vars['#anon8'];
		self::assert_equals('float=3.2',(string)$x['x']->get_type());
	}
	
	public function test_doc()
	{
		$code = '<?php
/**
 * @param int $a
 * @return int
 */
function a(array $a): string {
	return "";
}

/**
 * @param int|string $x
 * @param int $y
 */
function b(int $x,&$y) {
}

/**
 * @param int $a
 */
function c($a = "") {
}
?>';
		
		list($funcs,,,,$errors) = $this->analyze($code);
		
		self::assert_equals(3,count($funcs));
		
		self::assert_equals('function a(integer): string',$funcs['a']);
		self::assert_equals('function b(integer or string, &integer): void',$funcs['b']);
		self::assert_equals('function c(integer?): void',$funcs['c']);
		
		self::assert_equals(4,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_T_PARAM_DIFFERS_FROM_DOC,$error->get_type());
		self::assert_equals('PHPDoc (integer) does not match the parameter $a (array)',$error->get_msg());
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_T_RETURN_DIFFERS_FROM_DOC,$error->get_type());
		self::assert_equals('PHPDoc (integer) does not match the return type (string)',$error->get_msg());
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_T_PARAM_DIFFERS_FROM_DOC,$error->get_type());
		self::assert_equals('PHPDoc (integer or string) does not match the parameter $x (integer)',$error->get_msg());
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_T_PARAM_DIFFERS_FROM_DOC,$error->get_type());
		self::assert_equals('PHPDoc (integer) does not match the parameter $y (&unknown)',$error->get_msg());
	}
}
