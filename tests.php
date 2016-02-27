<?php
/**
 * Contains the testsuite
 * 
 * @package			PHPCheck
 * @subpackage	main
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

include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');

/**
 * The autoloader for the test-cases
 * 
 * @param string $item the item to load
 * @return boolean whether the file has been loaded
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

define('PC_UNITTESTS',1);

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

$suite = new FWS_Test_Suite();

if($argc > 1)
{
	for($i = 1; $i < $argc; $i++)
		$suite->add($argv[$i]);
}
else
{
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
		'PC_Tests_TryCatch',
		'PC_Tests_Versions',
	);

	foreach($tests as $test)
		$suite->add($test);
}

$suite->run();
?>
