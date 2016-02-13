<?php
/**
 * Contains the vars-module
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
 * The vars-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_vars extends PC_Module
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
		$renderer->add_breadcrumb('Vars',PC_URL::build_mod_url());
		
		if(FWS_Props::get()->project() === null)
			$this->report_error(FWS_Document_Messages::ERROR,'Please create and select a project first!');
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,PC_DAO::get_vars()->get_count());
		$pagination->populate_tpl(PC_URL::get_mod_url());
		$start = $pagination->get_start();
		
		$vars = array();
		foreach(PC_DAO::get_vars()->get_list($start,PC_ENTRIES_PER_PAGE) as $var)
		{
			/* @var $var PC_Obj_Variable */
			$vars[] = array(
				'scope' => $var->get_scope(),
				'name' => '$'.$var->get_name(),
				'type' => $var->get_type()
			);
		}
		
		$tpl->add_variables(array(
			'vars' => $vars
		));
	}
}
