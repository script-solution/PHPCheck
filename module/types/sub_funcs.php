<?php
/**
 * Contains the functions-types-submodule
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
 * The functions submodule for module types
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_types_funcs extends PC_SubModule
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
		$renderer->add_breadcrumb('Functions',PC_URL::build_submod_url());
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
		$func = $input->get_var('func',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_submod_url();
		$url->set('file',$file);
		$url->set('func',$func);
		$surl = clone $url;
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_functions()->get_count(0,PC_Project::CURRENT_ID,$file,$func)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$funcs = array();
		foreach(PC_DAO::get_functions()->get_list(array(0),$start,PC_ENTRIES_PER_PAGE,$file,$func) as $f)
		{
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$f->get_file());
			$url->set('line',$f->get_line());
			$url->set_anchor('l'.$f->get_line());
			
			$funcs[] = array(
				'id' => $f->get_id(),
				'url' => $url->to_url(),
				'func' => (string)$f,
				'file' => $f->get_file(),
				'line' => $f->get_line(),
				'since' => implode(', ',$f->get_version()->get_min()),
				'till' => implode(', ',$f->get_version()->get_max()),
			);
		}
		
		$callurl = PC_URL::get_mod_url('filepart');
		$callurl->set('id','__ID__');
		$callurl->set('type','func');
		
		$this->request_formular();
		$tpl->add_variables(array(
			'get_code_url' => $callurl->to_url(),
			'funcs' => $funcs,
			'file' => $file,
			'func' => $func,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('funcs_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'funcs_search',
		));
	}
}
