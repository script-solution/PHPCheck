<?php
/**
 * Tests arrays
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Tests_Arrays extends PHPUnit_Framework_Testcase
{
	private static $code = '<?php
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
$b = 0;
$b[] = 4;
?>';
	
	public function testArrays()
	{
		global $code;
		$tscanner = new PC_TypeScanner();
		$tscanner->scan(self::$code);
		$tscanner->finish();
		
		$functions = $tscanner->get_functions();
		$classes = $tscanner->get_classes();
		$constants = $tscanner->get_constants();
		
		// scan files for function-calls and variables
		$ascanner = new PC_ActionScanner();
		$ascanner->scan(self::$code,$functions,$classes,$constants);
		$vars = $ascanner->get_vars();
		$calls = $ascanner->get_calls();
		
		$args = $calls[0]->get_arguments();
		self::assertEquals('integer=1',(string)$args[0]);
		$args = $calls[1]->get_arguments();
		self::assertEquals('integer=2',(string)$args[0]);
		$args = $calls[2]->get_arguments();
		self::assertEquals('integer=3',(string)$args[0]);
		$args = $calls[3]->get_arguments();
		self::assertEquals('array={0 = string=\'abc\';1 = integer=2;}',(string)$args[0]);
		$args = $calls[4]->get_arguments();
		self::assertEquals('string=\'abc\'',(string)$args[0]);
		$args = $calls[5]->get_arguments();
		self::assertEquals('integer=2',(string)$args[0]);
		$args = $calls[6]->get_arguments();
		self::assertEquals('unknown',(string)$args[0]);
		$args = $calls[7]->get_arguments();
		self::assertEquals('unknown',(string)$args[0]);
		
		self::assertEquals(
			'array={0 = a;1 = integer=4;2 = integer=5;"Abc" = string="me";}',
			(string)$vars[PC_ActionScanner::SCOPE_GLOBAL]['$a']
		);
		self::assertEquals(
			'array={0 = integer=4;}',
			(string)$vars[PC_ActionScanner::SCOPE_GLOBAL]['$b']
		);
	}
}
?>