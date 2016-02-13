<?php
/**
 * Contains the type-storage-implementation that does nothing
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
 * The null-implementation of the type-storage to write changes in the finalizing-phase of the
 * type-scanner
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_TypeStorage_Null implements PC_Engine_TypeStorage
{
	public function create_function($method,$classid)
	{
		// do nothing
		return 0;
	}
	
	public function update_function($method,$classid)
	{
		// do nothing
	}
	
	public function create_field($field,$classid)
	{
		// do nothing
		return 0;
	}
	
	public function create_constant($const,$classid)
	{
		// do nothing
		return 0;
	}
}
