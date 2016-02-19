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
	private function do_analyze($code)
	{
		$tscanner = new PC_Engine_TypeScannerFrontend();
		$tscanner->scan($code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
		
		// scan files for function-calls and variables
		$ascanner = new PC_Engine_StmtScannerFrontend($typecon);
		$ascanner->scan($code);
		return array(
			$typecon->get_functions(),$typecon->get_classes(),$typecon->get_calls(),$ascanner->get_vars()
		);
	}
	
	public function testFuncs()
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
	
		list($functions,$classes,$calls) = $this->do_analyze($code);
		
		$func = $functions['a'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('a',$func->get_name());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		
		$func = $functions['b'];
		self::assertEquals('b',$func->get_name());
		self::assertEquals(1,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('string',(string)$func->get_param('a'));
		
		$class = $classes['myc2'];
		
		$func = $class->get_method('c');
		self::assertEquals('c',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('array',(string)$func->get_param('a'));
		self::assertEquals('integer?',(string)$func->get_param('b'));
		
		$func = $class->get_method('d');
		self::assertEquals('d',$func->get_name());
		self::assertEquals(3,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals((string)PC_Obj_MultiType::create_int(),(string)$func->get_return_type());
		self::assertEquals('integer?',(string)$func->get_param('a'));
		self::assertEquals('string?',(string)$func->get_param('b'));
		self::assertEquals('bool?',(string)$func->get_param('c'));
		
		$func = $class->get_method('doit');
		self::assertEquals('doit',$func->get_name());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(2,$func->get_required_param_count());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$func->get_return_type());
		self::assertEquals('MyClass',(string)$func->get_param('c'));
		self::assertEquals('integer',(string)$func->get_param('d'));
		
		self::assertEquals('myc->doit()',(string)$calls[0]->get_call(false,false));
		self::assertEquals('myc2::mystatic()',(string)$calls[1]->get_call(false,false));
	}
	
	public function testNesting()
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
				$g = 7;
			}
		}
	}
}
?>';
		
		list(,,$calls,$vars) = $this->do_analyze($code);
		
		self::assertEquals('f3(integer=3)',(string)$calls[0]->get_call(false,false));
		self::assertEquals('f2(integer=2)',(string)$calls[1]->get_call(false,false));
		self::assertEquals('f1(integer=1)',(string)$calls[2]->get_call(false,false));
		
		self::assertEquals((string)PC_Obj_MultiType::create_int(1),(string)$vars['A::a']['a']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(2),(string)$vars['b']['b']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(3),(string)$vars['c']['c']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(4),(string)$vars['d']['d']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(5),(string)$vars['e']['e']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(6),(string)$vars['f']['f']->get_type());
		self::assertEquals((string)PC_Obj_MultiType::create_int(7),(string)$vars['B::g']['g']->get_type());
	}
	
	public function testAnon()
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
?>';
		
		list($functions,$classes,$calls,$vars) = $this->do_analyze($code);
		
		$func = $functions['#anon1'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon1',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		
		$func = $functions['#anon2'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon2',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(1,$func->get_param_count());
		self::assertEquals(1,$func->get_required_param_count());
		self::assertEquals('unknown',(string)$func->get_param('arg1'));
		
		$x = $vars['#anon2'];
		// TODO actually, we could get the type from the outer scope
		self::assertEquals('unknown',(string)$x['arg1']->get_type());
		
		$func = $functions['#anon3'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon3',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(2,$func->get_required_param_count());
		self::assertEquals('int',(string)$func->get_param('arg1'));
		self::assertEquals('float',(string)$func->get_param('arg2'));
		
		$x = $vars['#anon3'];
		self::assertEquals('int',(string)$x['arg1']->get_type());
		self::assertEquals('float',(string)$x['arg2']->get_type());
		
		$func = $functions['#anon4'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon4',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		
		$func = $functions['#anon5'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon5',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(2,$func->get_param_count());
		self::assertEquals(2,$func->get_required_param_count());
		self::assertEquals('unknown',(string)$func->get_param('arg1'));
		self::assertEquals('unknown',(string)$func->get_param('arg2'));
		self::assertEquals('integer=1',(string)$func->get_return_type());
		
		$x = $vars['#anon5'];
		self::assertEquals('unknown',(string)$x['arg1']->get_type());
		self::assertEquals('unknown',(string)$x['arg2']->get_type());
		
		$func = $functions['#anon6'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon6',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals('callable',(string)$func->get_return_type());
		
		$x = $vars['#anon6'];
		self::assertEquals('callable',(string)$x['a']->get_type());
		self::assertEquals('callable',(string)$x['c']->get_type());
		self::assertEquals('integer=2',(string)$x['x']->get_type());
		
		$func = $functions['#anon7'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon7',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals('callable',(string)$func->get_return_type());
		
		$x = $vars['#anon7'];
		self::assertEquals('callable',(string)$x['b']->get_type());
		
		$func = $functions['#anon8'];
		/* @var $func PC_Obj_Method */
		self::assertEquals('#anon8',$func->get_name());
		self::assertEquals(true,$func->is_anonymous());
		self::assertEquals(0,$func->get_param_count());
		self::assertEquals(0,$func->get_required_param_count());
		self::assertEquals('unknown',(string)$func->get_return_type());
		
		$x = $vars['#anon8'];
		self::assertEquals('float=3.2',(string)$x['x']->get_type());
	}
}
?>