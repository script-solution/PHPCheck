<?php
/**
 * Contains the constants-types-submodule
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
 * The constants submodule for module types
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_types_consts extends PC_SubModule
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
		$renderer->add_breadcrumb('Constants',PC_URL::build_submod_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		$cookies = FWS_Props::get()->cookies();
		
		$file = $input->get_var('file',-1,FWS_Input::STRING);
		$name = $input->get_var('name',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_submod_url();
		$url->set('file',$file);
		$url->set('name',$name);
		$surl = clone $url;
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_constants()->get_count_for(0,$file,$name)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$consts = array();
		$constants = PC_DAO::get_constants()->get_list(
			0,$file,$name,PC_Project::CURRENT_ID,$start,PC_ENTRIES_PER_PAGE
		);
		foreach($constants as $const)
		{
			$consts[] = array(
				'name' => $const->get_name(),
				'type' => (string)$const->get_type(),
				'file' => $const->get_file(),
				'line' => $const->get_line()
			);
		}
		
		$this->request_formular();
		$tpl->add_variables(array(
			'consts' => $consts,
			'file' => $file,
			'name' => $name,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('consts_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'consts_search',
		));
	}
}
