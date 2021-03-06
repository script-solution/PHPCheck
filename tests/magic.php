<?php
/**
 * Tests magic methods
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

class PC_Tests_Magic extends PC_UnitTest
{
	private static function assertParamsEqual($expected,$actual)
	{
		$expstr = array();
		foreach($expected as $p)
			$expstr[] = (string)$p;
		$actstr = array();
		foreach($actual as $p)
			$actstr[] = (string)$p;
		self::assert_equals($expstr,$actstr);
	}
	
	public function test__set()
	{
		// public void __set ( string $name , mixed $value )
		$code = '<?php
class A {
	private function __set($name,$value);
}
class B {
	public function __set($name);
}
class C {
	/**
	 * @param int $name
	 */
	public function __set($name,$value);
}
class D {
	/**
	 * @return float
	 */
	public function __set($name,$value) {
		return 1.1;
	}
}
class E {
	public function __set($name,$value);
}
class F {
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name,$value);
}
class G {
  /**
   * @param string $value
   */
	public function __set($name,$value);
}
class H {
	public static function __set($name,$value);
}
?>';
		
		list(,$classes,,,$errors) = $this->analyze($code);
		
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string()),
			new PC_Obj_Parameter('value',new PC_Obj_MultiType())
		);
		
		self::assertParamsEqual($params,$classes['a']->get_method('__set')->get_params());
		// in B the param-count is wrong, therefore no correction
		self::assertParamsEqual($params,$classes['c']->get_method('__set')->get_params());
		self::assertParamsEqual($params,$classes['d']->get_method('__set')->get_params());
		
		self::assert_equals(4,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_NOT_PUBLIC,$error->get_type());
		self::assert_regex(
			'/The magic method "#A#::__set" should be public/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_METHOD_PARAMS_INVALID,$error->get_type());
		self::assert_regex(
			'/The parameters of the magic-method "#B#::__set" are invalid'
			.' \(expected="string,unknown", found="unknown"\)/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_METHOD_RET_INVALID,$error->get_type());
		self::assert_regex(
			'/The return-type of the magic-method "#D#::__set" is invalid \(expected="void", found="float"\)/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_IS_STATIC,$error->get_type());
		self::assert_regex(
			'/The magic method "#H#::__set" should not be static/',
			$error->get_msg()
		);
	}
	
	public function test__get()
	{
		// public mixed __get ( string $name )
		$code = '<?php
class A {
	public function __get($name);
}
class B {
	/**
	 * @return int
	 */
	public function __get($name);
}
?>';
		
		list(,$classes,,,$errors) = $this->analyze($code);
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string())
		);

		self::assert_equals(2,count($errors));
		
		$m = $classes['a']->get_method('__get');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_void(),(string)$m->get_return_type());
		
		$m = $classes['b']->get_method('__get');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_int(),(string)$m->get_return_type());
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#A#::__get" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#B#::__get" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
	}
	
	public function test__isset()
	{
		// public bool __isset ( string $name )
		$code = '<?php
class A {
	public function __isset($name);
}
class B {
	/**
	 * @return int
	 */
	public function __isset($name);
}
?>';
		
		list(,$classes,,,$errors) = $this->analyze($code);
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string())
		);
		
		$m = $classes['a']->get_method('__isset');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_bool(),(string)$m->get_return_type());
		
		$m = $classes['b']->get_method('__isset');
		self::assertParamsEqual($params,$m->get_params());
		
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#A#::__isset" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_METHOD_RET_INVALID,$error->get_type());
		self::assert_regex(
			'/The return-type of the magic-method "#B#::__isset" is invalid \(expected="bool", found="integer"\)/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#B#::__isset" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
	}
	
	public function test__sleep()
	{
		// * array __sleep( void )
		$code = '<?php
class A {
	private function __sleep();
}
class B {
	protected function __sleep();
}
class C {
	public function __sleep();
}
class D {
	public function __sleep($foo);
}
?>';
		
		list(,$classes,,,$errors) = $this->analyze($code);
		$params = array();
		
		$m = $classes['a']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['b']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['c']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['d']->get_method('__sleep');
		self::assert_equals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		self::assert_equals(5,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#A#::__sleep" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#B#::__sleep" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#C#::__sleep" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_METHOD_PARAMS_INVALID,$error->get_type());
		self::assert_regex(
			'/The parameters of the magic-method "#D#::__sleep" are invalid \(expected="", found="unknown"\)/',
			$error->get_msg()
		);
		
		$error = $errors[4];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#D#::__sleep" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
	}
	
	public function test__set_state()
	{
		// public static object __set_state( array $props )
		$code = '<?php
class A {
	public function __set_state($arr);
}
class B {
	public static function __set_state($arr);
}
?>';
		
		list(,$classes,,,$errors) = $this->analyze($code);
		$params = array(
			new PC_Obj_Parameter('props',PC_Obj_MultiType::create_array())
		);
		
		$m = $classes['a']->get_method('__set_state');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_object(),(string)$m->get_return_type());
		
		$m = $classes['b']->get_method('__set_state');
		self::assertParamsEqual($params,$m->get_params());
		self::assert_equals((string)PC_Obj_MultiType::create_object(),(string)$m->get_return_type());
		
		self::assert_equals(3,count($errors));
		
		$error = $errors[0];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#A#::__set_state" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assert_equals(PC_Obj_Error::E_T_MAGIC_IS_STATIC,$error->get_type());
		self::assert_regex(
			'/The magic method "#B#::__set_state" should not be static/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assert_equals(PC_Obj_Error::E_S_RET_SPEC_BUT_NO_RET,$error->get_type());
		self::assert_regex(
			'/The function\/method "#B#::__set_state" has a return-specification in PHPDoc, but does not return a value/',
			$error->get_msg()
		);
	}
}
