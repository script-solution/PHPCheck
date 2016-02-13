<?php
/**
 * Contains the classes-types-submodule
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
 * The classes submodule for module types
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_types_classes extends PC_SubModule
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
		$renderer->add_breadcrumb('Classes',PC_URL::build_submod_url());
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
		$class = $input->get_var('class',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_submod_url();
		$url->set('file',$file);
		$url->set('class',$class);
		$surl = clone $url;
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_classes()->get_count_for($class,$file)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$classes = array();
		foreach(PC_DAO::get_classes()->get_list($start,PC_ENTRIES_PER_PAGE,$class,$file) as $c)
		{
			$sig = $c->get_signature(true);
			$sig = preg_replace_callback(
				'/#(.+?)#/',
				function($match) {
					return "<a href=\"".PC_URL::get_mod_url('class')->set('name',$match[1])->to_url()."\">".$match[1]."</a>";
				},
				$sig
			);
			$classes[] = array(
				'name' => $sig,
				'file' => $c->get_file(),
				'line' => $c->get_line()
			);
		}
		
		$this->request_formular();
		$tpl->add_variables(array(
			'classes' => $classes,
			'file' => $file,
			'class' => $class,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('classes_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'classes_search',
		));
	}
}
