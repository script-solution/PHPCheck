<?php
/**
 * Contains the error-container class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Collects all errors that occur during the analysis.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_ErrorContainer extends FWS_Object
{
	/**
	 * The container for all errors / warnings
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * @return array all found errors
	 */
	public function get()
	{
		return $this->errors;
	}
	
	/**
	 * Reports the given error
	 * 
	 * @param PC_Obj_Location $locsrc an object from which the location will be copied (null = current)
	 * @param string $msg the error-message
	 * @param int $type the error-type
	 */
	public function report($locsrc,$msg,$type)
	{
		if(!($locsrc instanceof PC_Obj_Location))
			FWS_Helper::def_error('instance','locsrc','PC_Obj_Location',$locsrc);
		
		$locsrc = new PC_Obj_Location($locsrc->get_file(),$locsrc->get_line());
		$this->errors[] = new PC_Obj_Error($locsrc,$msg,$type);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
