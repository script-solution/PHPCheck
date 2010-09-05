<?php
/**
 * The entry-point for CLI-jobs
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

include_once('config/actions.php');
include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

if($argc < 2)
	exit("Invalid request\n");

$module = $argv[1];
if(preg_match('/^[a-z0-9]+$/i',$module) && is_file('cli/'.$module.'.php'))
{
	include_once('cli/'.$module.'.php');
	$classname = 'PC_CLI_'.$module;
	if(class_exists($classname))
	{
		$job = new $classname();
		$job->run(array_slice($argv,2));
		exit;
	}
}
exit("Invalid request\n");
?>