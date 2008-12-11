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
		$folder = strtolower(strtok($nitem,'_'));
		$subfolder = 'src/';
		if(!FWS_String::starts_with($item,'PC_Module_'))
			$folder = '';
		else
		{
			$subfolder = '';
			$folder .= '/';
			$nitem = substr($nitem,strlen($folder));
		}
		
		$nitem = str_replace('_','/',$nitem);
		$nitem = strtolower($nitem);
		$nitem .= '.php';
		$path = FWS_Path::server_app().$folder.$subfolder.$nitem;
		if(is_file($path))
		{
			include($path);
			return true;
		}
	}
	
	return false;
}
?>