<?php
/**
 * Contains the jobstate-module
 * 
 * @package			PHPCheck
 * @subpackage	module
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
 * The jobstate-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_jobstate extends PC_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$doc->use_raw_renderer();
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$doc = FWS_Props::get()->doc();
		$user = FWS_Props::get()->user();
		$renderer = $doc->use_raw_renderer();
		
		// read from file
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		$mutex->close();
		
		/* @var $data PC_JobData */
		$res = $data->get_done().';';
		if(count($data->get_errors()))
		{
			$res .= '<div style="text-align: left; padding-left: 3em;"><ul>';
			foreach($data->get_errors() as $err)
				$res .= '<li>'.$err.'</li>';
			$res .= '</ul></div>';
		}
		
		$renderer->set_content($res);
	}
}
