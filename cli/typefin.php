<?php
/**
 * Contains the cli-type-finalizer-module
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
 * The cli-type-finalizer-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_TypeFin implements PC_CLIJob
{
	public function run($args)
	{
		$project = FWS_Props::get()->project();
		
		$options = new PC_Engine_Options();
		$options->add_project($project->get_id());
		foreach($project->get_project_deps() as $pid)
			$options->add_project($pid);
		
		$types = new PC_Engine_TypeContainer($options);
		$storage = new PC_Engine_TypeStorage_DB();
		$env = new PC_Engine_Env($options,$types,$storage);

		$types->prefetch();

		$fin = new PC_Engine_TypeFinalizer($env);
		$fin->finalize();
	}
}
