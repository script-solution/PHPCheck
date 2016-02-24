<?php
/**
 * Contains the calls-module
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
 * The calls-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_calls extends PC_Module
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
		$renderer->add_breadcrumb('Calls',PC_URL::build_mod_url());
		
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
		
		$file = $input->get_var('file',-1,FWS_Input::STRING);
		$class = $input->get_var('class',-1,FWS_Input::STRING);
		$function = $input->get_var('function',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_mod_url();
		$url->set('file',$file);
		$url->set('class',$class);
		$url->set('function',$function);
		$surl = clone $url;
		
		$typecon = new PC_Engine_TypeContainer(new PC_Engine_Options());
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_calls()->get_count_for($file,$class,$function)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$calls = array();
		foreach(PC_DAO::get_calls()->get_list($start,PC_ENTRIES_PER_PAGE,$file,$class,$function) as $call)
		{
			/* @var $call PC_Obj_Call */
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$call->get_file());
			$url->set('line',$call->get_line());
			$url->set_anchor('l'.$call->get_line());
			
			$func = $typecon->get_method_or_func($call->get_class(),$call->get_function());
			$calls[] = array(
				'id' => $call->get_id(),
				'call' => $call->get_call($typecon),
				'file' => $call->get_file(),
				'line' => $call->get_line(),
				'url' => $url->to_url()
			);
		}
		
		$callurl = PC_URL::get_mod_url('filepart');
		$callurl->set('id','__ID__');
		$callurl->set('type','call');
		
		$this->request_formular();
		$tpl->add_variables(array(
			'calls' => $calls,
			'get_code_url' => $callurl->to_url(),
			'file' => $file,
			'class' => $class,
			'function' => $function,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('calls_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'calls_search',
		));
	}
}
