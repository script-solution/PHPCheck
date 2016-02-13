<?php
/**
 * Contains the property-accessor-class
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
 * The property-accessor for PHPCheck. We change and add some properties to the predefined
 * ones.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_PropAccessor extends FWS_PropAccessor
{
	/**
	 * @return FWS_DB_MySQL_Connection the db-property
	 */
	public function db()
	{
		return $this->get('db');
	}
	
	/**
	 * @return PC_Project the current project
	 */
	public function project()
	{
		return $this->get('project');
	}
}
