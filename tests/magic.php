<?php
/**
 * Tests magic methods
 *
 * @version			$Id: exprs.php 65 2010-09-06 09:32:49Z nasmussen $
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Magic extends PHPUnit_Framework_Testcase
{
	private function do_analyze($code)
	{
		$tscanner = new PC_Engine_TypeScannerFrontend();
		$tscanner->scan($code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
		return array($typecon->get_classes(),$typecon->get_errors());
	}
	
	private static function assertParamsEqual($expected,$actual)
	{
		$expstr = array();
		foreach($expected as $p)
			$expstr[] = (string)$p;
		$actstr = array();
		foreach($actual as $p)
			$actstr[] = (string)$p;
		self::assertEquals($expstr,$actstr);
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
	public function __set($name,$value);
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
		
		list($classes,$errors) = $this->do_analyze($code);
		
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string()),
			new PC_Obj_Parameter('value',new PC_Obj_MultiType())
		);
		
		self::assertParamsEqual($params,$classes['A']->get_method('__set')->get_params());
		// in B the param-count is wrong, therefore no correction
		self::assertParamsEqual($params,$classes['C']->get_method('__set')->get_params());
		self::assertParamsEqual($params,$classes['D']->get_method('__set')->get_params());
		
		self::assertEquals(4,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_NOT_PUBLIC,$error->get_type());
		self::assertRegExp(
			'/The magic method "#A#::__set" should be public/',
			$error->get_msg()
		);
		
		$error = $errors[1];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_METHOD_PARAMS_INVALID,$error->get_type());
		self::assertRegExp(
			'/The parameters of the magic-method "#B#::__set" are invalid'
			.' \(expected="string,unknown", found="unknown"\)/',
			$error->get_msg()
		);
		
		$error = $errors[2];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_METHOD_RET_INVALID,$error->get_type());
		self::assertRegExp(
			'/The magic method "#D#::__set" has a return-specification in PHPDoc, but should not return a value/',
			$error->get_msg()
		);
		
		$error = $errors[3];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_IS_STATIC,$error->get_type());
		self::assertRegExp(
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
		
		list($classes,$errors) = $this->do_analyze($code);
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string())
		);
		
		$m = $classes['A']->get_method('__get');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$m->get_return_type());
		
		$m = $classes['B']->get_method('__get');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)PC_Obj_MultiType::create_int(),(string)$m->get_return_type());
		
		self::assertEquals(0,count($errors));
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
		
		list($classes,$errors) = $this->do_analyze($code);
		$params = array(
			new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string())
		);
		
		$m = $classes['A']->get_method('__isset');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)PC_Obj_MultiType::create_bool(),(string)$m->get_return_type());
		
		$m = $classes['B']->get_method('__isset');
		self::assertParamsEqual($params,$m->get_params());
		
		self::assertEquals(1,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_METHOD_RET_INVALID,$error->get_type());
		self::assertRegExp(
			'/The return-type of the magic-method "#B#::__isset" is invalid \(expected="bool", found="integer"\)/',
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
		
		list($classes,$errors) = $this->do_analyze($code);
		$params = array();
		
		$m = $classes['A']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['B']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['C']->get_method('__sleep');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		$m = $classes['D']->get_method('__sleep');
		self::assertEquals((string)PC_Obj_MultiType::create_array(),(string)$m->get_return_type());
		
		self::assertEquals(1,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_METHOD_PARAMS_INVALID,$error->get_type());
		self::assertRegExp(
			'/The parameters of the magic-method "#D#::__sleep" are invalid \(expected="", found="unknown"\)/',
			$error->get_msg()
		);
	}
	
	public function test__set_state()
	{
		// public static mixed __set_state( array $props )
		$code = '<?php
class A {
	public function __set_state($arr);
}
class B {
	public static function __set_state($arr);
}
?>';
		
		list($classes,$errors) = $this->do_analyze($code);
		$params = array(
			new PC_Obj_Parameter('props',PC_Obj_MultiType::create_array())
		);
		
		$m = $classes['A']->get_method('__set_state');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$m->get_return_type());
		
		$m = $classes['B']->get_method('__set_state');
		self::assertParamsEqual($params,$m->get_params());
		self::assertEquals((string)new PC_Obj_MultiType(),(string)$m->get_return_type());
		
		self::assertEquals(1,count($errors));
		
		$error = $errors[0];
		self::assertEquals(PC_Obj_Error::E_T_MAGIC_IS_STATIC,$error->get_type());
		self::assertRegExp(
			'/The magic method "#B#::__set_state" should not be static/',
			$error->get_msg()
		);
	}
}
?>