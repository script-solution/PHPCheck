<?php
/**
 * Contains the autoloader
 * 
 * @package			PHPCheck
 * @subpackage	src
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
