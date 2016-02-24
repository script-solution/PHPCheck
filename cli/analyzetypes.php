<?php
/**
 * Contains the cli-type-analyzer-module
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
 * The cli-type-analyzer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_AnalyzeTypes implements PC_CLIJob
{
	public function run($args)
	{
		$project = FWS_Props::get()->project();
		$options = new PC_Engine_Options();
		if($project !== null)
		{
			$options->set_report_mixed($project->get_report_mixed());
			$options->set_report_unknown($project->get_report_unknown());
		}
		
		$an = new PC_Engine_Analyzer($options);
		$types = new PC_Engine_TypeContainer($options);
		
		$classes = PC_DAO::get_classes()->get_list();
		$an->analyze_classes($types,$classes);
		
		// insert errors
		foreach($an->get_errors() as $error)
			PC_DAO::get_errors()->create($error);
	}
}
