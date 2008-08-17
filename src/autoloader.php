<?php
/**
 * Contains the autoloader
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The autoloader for the phpcheck src-files
 * 
 * @param string $item the item to load
 * @return boolean wether the file has been loaded
 */
function PC_autoloader($item)
{
	if(FWS_String::starts_with($item,'PC_'))
	{
		$item = FWS_String::substr($item,3);
		$item = str_replace('_','/',$item);
		$item = FWS_String::strtolower($item);
		$item .= '.php';
		$path = FWS_Path::server_app().'src/'.$item;
		if(is_file($path))
		{
			include($path);
			return true;
		}
	}
	
	return false;
}
?>