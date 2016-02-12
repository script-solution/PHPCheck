<?php
/**
 * Contains the testsuite
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');

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
		$path = 'tests/'.FWS_String::strtolower($item).'.php';
		if(is_file($path))
		{
			include($path);
			return true;
		}
	}
	
	return false;
}

FWS_AutoLoader::register_loader('PC_UnitTest_autoloader');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

// set error-handling
error_reporting((E_ALL | E_STRICT) & ~E_DEPRECATED);

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

$tests = array(
	'PC_Tests_Vars',
	'PC_Tests_Funcs',
	'PC_Tests_OOP',
	'PC_Tests_Arrays',
	'PC_Tests_Exprs',
	'PC_Tests_Exprs2',
	'PC_Tests_Analyzer',
	'PC_Tests_CondsNLoops',
	'PC_Tests_Returns',
	'PC_Tests_Magic',
);

$succ = 0;
$fail = 0;
foreach($tests as $test)
{
	echo "-- ".$test.":\n";
	$t = new $test();
	foreach(get_class_methods($t) as $m)
	{
		if(FWS_String::starts_with($m,'test'))
		{
			try
			{
				echo "   - Testing method ".$m."...\n";
				$t->$m();
				$succ++;
			}
			catch(Exception $e)
			{
				echo $e."\n";
				$fail++;
			}
		}
	}
}

echo "\n";
echo "------------------------\n";
echo "Total: ".$succ." / ".($succ+$fail)." succeeded\n";
echo "------------------------\n";
?>
