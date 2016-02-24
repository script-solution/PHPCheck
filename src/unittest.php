<?php
/**
 * Contains the base-class for all unittests.
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
 * Base-class for all unittests.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */	
class PC_UnitTest extends FWS_Test_Case
{
	/**
	 * Performs an analysis of the given code.
	 * 
	 * @param string $code the code to analyze
	 * @param PC_Engine_Options $options the options
	 * @return array the following array: array(
	 *  0 => <functions>,
	 *  1 => <classes>,
	 *  2 => <vars>,
	 *  3 => <calls>,
	 *  4 => <type errors>,
	 *  5 => <analyzer errors>
	 * )
	 */
	protected function analyze($code,$options = null)
	{
		if($options === null)
		{
			$options = new PC_Engine_Options();
			$options->set_use_db(false);
			$options->set_use_phpref(false);
		}
		
		$tscanner = new PC_Engine_TypeScannerFrontend($options);
		$tscanner->scan($code);
		
		$typecon = $tscanner->get_types();
		$fin = new PC_Engine_TypeFinalizer($typecon,new PC_Engine_TypeStorage_Null());
		$fin->finalize();
		
		$stmt = new PC_Engine_StmtScannerFrontend($typecon,$options);
		$stmt->scan($code);
		
		$an = new PC_Engine_Analyzer($options);
		$an->analyze_classes($typecon,$typecon->get_classes());
		$an->analyze_calls($typecon,$typecon->get_calls());
		
		return array(
			$typecon->get_functions(),
			$typecon->get_classes(),
			$stmt->get_vars(),
			$typecon->get_calls(),
			$typecon->get_errors(),
			$an->get_errors(),
		);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>
