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
		$nitem = substr($item,3);
		$parts = explode('_',$nitem);
		if(!FWS_String::starts_with($item,'PC_Module_'))
			array_unshift($parts,'src');
		$nitem = implode('/',$parts);
		$nitem = str_replace('_','/',$nitem);
		$nitem = strtolower($nitem);
		$nitem .= '.php';
		$path = FWS_Path::server_app().$nitem;
		if(is_file($path))
		{
			include($path);
			return true;
		}
	}
	
	return false;
}
?>