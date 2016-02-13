<?php
/**
 * Contains the cli-call-analyzer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
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
 * The cli-call-analyzer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_AnalyzeCalls implements PC_CLIJob
{
	public function run($args)
	{
		$project = FWS_Props::get()->project();
		$an = new PC_Engine_Analyzer(
			$project !== null ? $project->get_report_mixed() : false,
			$project !== null ? $project->get_report_unknown() : false
		);
		$types = new PC_Engine_TypeContainer();
		// we need all classes in the type-container to be able to search for sub-classes and interface-
		// implementations
		$types->add_classes(PC_DAO::get_classes()->get_list());
		
		// analyze calls
		$calls = PC_DAO::get_calls()->get_list();
		$an->analyze_calls($types,$calls);
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
	}
}
