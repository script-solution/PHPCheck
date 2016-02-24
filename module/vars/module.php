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
		$input = FWS_Props::get()->input();
		$cookies = FWS_Props::get()->cookies();
		
		$scope = $input->get_var('scope',-1,FWS_Input::STRING);
		$name = $input->get_var('name',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_mod_url();
		$url->set('scope',$scope);
		$url->set('name',$name);
		$surl = clone $url;
		
		$total = PC_DAO::get_vars()->get_count_for($scope,$name);
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,$total);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$vars = array();
		foreach(PC_DAO::get_vars()->get_list($start,PC_ENTRIES_PER_PAGE,$scope,$name) as $var)
		{
			/* @var $var PC_Obj_Variable */
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$var->get_file());
			$url->set('line',$var->get_line());
			$url->set_anchor('l'.$var->get_line());
			
			$vscope = $var->get_scope();
			if($vscope == PC_Obj_Variable::SCOPE_GLOBAL)
				$vscope = '<i>Global</i>';
			
			$vars[] = array(
				'id' => $var->get_id(),
				'scope' => $vscope,
				'name' => '$'.$var->get_name(),
				'type' => $var->get_type(),
				'file' => $var->get_file(),
				'line' => $var->get_line(),
				'url' => $url->to_url(),
			);
		}
		
		$callurl = PC_URL::get_mod_url('filepart');
		$callurl->set('id','__ID__');
		$callurl->set('type','var');
		
		$this->request_formular();
		$tpl->add_variables(array(
			'vars' => $vars,
			'get_code_url' => $callurl->to_url(),
			'scope' => $scope,
			'name' => $name,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('vars_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'vars_search',
		));
	}
}
