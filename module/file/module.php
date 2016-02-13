<?php
/**
 * Contains the file-module
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
 * The file-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_file extends PC_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$renderer = $doc->use_default_renderer();
		$input = FWS_Props::get()->input();
		$line = $input->get_var('line','get',FWS_Input::INTEGER);
		$url = PC_URL::get_mod_url();
		if(isset($_GET['path']))
			$url->set('path',$_GET['path']);
		$url->set('line',$line);
		$renderer->add_breadcrumb('View file',$url->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		$line = $input->get_var('line','get',FWS_Input::INTEGER);
		
		if(!isset($_GET['path']))
		{
			$this->report_error();
			return;
		}
		
		$path = urldecode($_GET['path']);
		if(PC_DAO::get_classes()->get_count_for_file($path) == 0 &&
				PC_DAO::get_calls()->get_count_for($path) == 0)
		{
			$this->report_error();
			return;
		}
		
		$source = PC_Utils::highlight_file($path,$line);
		$tpl->add_variables(array('source' => $source));
	}
}
