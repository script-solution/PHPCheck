<?php
/**
 * Contains the testsuite
 *
 * @version			$Id$
 * @package			FrameWorkSolution
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

define('ROOT',dirname(__FILE__).'/../');
define('FWS_PATH',ROOT.'../FrameWorkSolution/');

// init the framework
include_once(FWS_PATH.'init.php');

FWS_Path::set_server_app(ROOT);

/**
 * The autoloader for the test-cases
 * 
 * @param string $item the item to load
 * @return boolean wether the file has been loaded
 */
function PC_UnitTest_autoloader($item)
{
	if(FWS_String::starts_with($item,'PC_Tests_'))
	{
		$item = FWS_String::substr($item,FWS_String::strlen('PC_Tests_'));
		$path = dirname(__FILE__).'/'.FWS_String::strtolower($item).'.php';
		if(is_file($path))
		{
			include($path);
			return true;
		}
	}
	
	return false;
}

FWS_AutoLoader::register_loader('PC_UnitTest_autoloader');
include_once('../src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

/**
 * Static test suite.
 * 
 * @package			FrameWorkSolution
 * @subpackage	tests
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class testsSuite extends PHPUnit_Framework_TestSuite
{
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		$this->setName('testsSuite');
		$this->addTestSuite('PC_Tests_Vars');
		$this->addTestSuite('PC_Tests_Funcs');
		$this->addTestSuite('PC_Tests_OOP');
		$this->addTestSuite('PC_Tests_Arrays');
	}
	
	/**
	 * We overwrite this method to autoload the class
	 * 
	 * @param string $name the class-name
	 */
	public function addTestSuite($name)
	{
		new $name();
		parent::addTestSuite($name);
	}

	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		$suite = new self();
		return $suite;
	}
}
?>