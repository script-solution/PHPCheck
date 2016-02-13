<?php
/**
 * The entry-point for CLI-jobs
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

include_once('config/actions.php');
include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

// set error-handling
error_reporting((E_ALL | E_STRICT) & ~E_DEPRECATED);

define('PC_UNITTESTS',0);

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
		// to report errors back to the user
		FWS_Error_Handler::get_instance()->set_logger(new PC_CLILogger());
		
		// this way, we can even report fatal errors
		function fatal_error_handler()
		{
			$last = error_get_last();
			if(($last['type'] & error_reporting()) != 0)
			{
				FWS_Error_Handler::get_instance()->handle_error(
					$last['type'],$last['message'],$last['file'],$last['line']
				);
			}
		}
		register_shutdown_function('fatal_error_handler');
		
		$job = new $classname();
		$job->run(array_slice($argv,2));
		exit;
	}
}
exit("Invalid request\n");
