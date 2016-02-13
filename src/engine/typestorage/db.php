<?php
/**
 * Contains the type-storage-implementation that writes it to the database
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
 * The implementation of the type-storage to write changes in the finalizing-phase of the
 * type-scanner to the database
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_TypeStorage_DB implements PC_Engine_TypeStorage
{
	public function create_function($method,$classid)
	{
		return PC_DAO::get_functions()->create($method,$classid);
	}
	
	public function update_function($method,$classid)
	{
		PC_DAO::get_functions()->update($method,$classid);
	}
	
	public function create_field($field,$classid)
	{
		return PC_DAO::get_classfields()->create($field,$classid);
	}
	
	public function create_constant($const,$classid)
	{
		return PC_DAO::get_constants()->create($const,$classid);
	}
}
