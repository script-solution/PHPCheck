<?php
/**
 * Contains the analyzer base class
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
 * The base class for all analyzers.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
abstract class PC_Analyzer extends FWS_Object
{
	/**
	 * The environment
	 *
	 * @var PC_Engine_Env
	 */
	protected $env;

	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Env $env the environment
	 */
	public function __construct($env)
	{
		$this->env = $env;
	}
	
	/**
	 * Reports the given error
	 * 
	 * @param PC_Obj_Location $locsrc an object from which the location will be copied (null = current)
	 * @param string $msg the error-message
	 * @param int $type the error-type
	 */
	protected function report($locsrc,$msg,$type)
	{
		$this->env->get_errors()->report($locsrc,$msg,$type);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
