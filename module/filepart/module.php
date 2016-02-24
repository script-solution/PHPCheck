<?php
/**
 * Contains the filepart-module
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
 * The filepart-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_filepart extends PC_Module
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
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		$doc = FWS_Props::get()->doc();
		
		$id = $input->get_var('id','get',FWS_Input::INTEGER);
		$type = $input->correct_var('type','get',FWS_Input::STRING,array('call','var','error'),'call');
		if($id === null)
		{
			$this->report_error();
			return;
		}
		
		$loc = null;
		switch($type)
		{
			case 'call':
				$loc = PC_DAO::get_calls()->get_by_id($id);
				break;
			case 'var':
				$loc = PC_DAO::get_vars()->get_by_id($id);
				break;
			case 'error':
				$loc = PC_DAO::get_errors()->get_by_id($id);
				if($loc === null)
				{
					$this->report_error();
					return;
				}
				$loc = $loc->get_loc();
				break;
		}
		
		if(!is_file($loc->get_file()))
		{
			$this->report_error();
			return;
		}
		
		$lines = explode("\n",file_get_contents($loc->get_file()));
		$start_line = max(1,$loc->get_line() - 4);
		$end_line = min(count($lines),$loc->get_line() + 2);
		$code = '';
		for($i = $start_line; $i <= $end_line; $i++)
			$code .= $lines[$i - 1]."\n";
		$code = PC_Utils::highlight_string($code,$start_line,$loc->get_line(),false);
		
		$renderer = $doc->use_raw_renderer();
		$renderer->set_content($code);
	}
}
