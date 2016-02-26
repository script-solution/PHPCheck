<?php
/**
 * Contains the errors-module
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
 * The errors-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_errors extends PC_Module
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
		$renderer->add_breadcrumb('Errors',PC_URL::build_mod_url());
		
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
		$msg = $input->get_var('msg',-1,FWS_Input::STRING);
		$types = $input->get_var('types',-1);
		if(!FWS_Array_Utils::is_numeric($types))
			$types = array();
		
		$url = PC_URL::get_mod_url();
		$url->set('file',$file);
		$url->set('msg',$msg);
		if(count($types))
			$url->set('types',$types);
		$surl = clone $url;
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_errors()->get_count_with($file,$msg,$types)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$errs = array();
		$errors = PC_DAO::get_errors()->get_list(
			PC_Project::CURRENT_ID,$start,PC_ENTRIES_PER_PAGE,$file,$msg,$types
		);
		foreach($errors as $err)
		{
			/* @var $err PC_Obj_Error */
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$err->get_loc()->get_file());
			$url->set('line',$err->get_loc()->get_line());
			$url->set_anchor('l'.$err->get_loc()->get_line());
			
			$errs[] = array(
				'id' => $err->get_id(),
				'type' => PC_Obj_Error::get_type_name($err->get_type()),
				'message' => $this->_get_msg($err),
				'file' => $err->get_loc()->get_file(),
				'line' => $err->get_loc()->get_line(),
				'fileurl' => $url->to_url()
			);
		}
		
		$this->request_formular();
		
		$typecbs = array();
		$row = -1;
		$i = 0;
		$error_types = PC_Obj_Error::get_types();
		asort($error_types);
		foreach($error_types as $type => $name)
		{
			if($i % 4 == 0)
				$typecbs[++$row] = array();
			$typecbs[$row][] = array(
				'name' => 'types['.$type.']',
				'selected' => count($types) == 0 || in_array($type,$types),
				'value' => $type,
				'text' => $name
			);
			$i++;
		}
		for($i = $i % 4;$i < 4;$i++)
			$typecbs[$row][] = array('name' => '');
		
		$callurl = PC_URL::get_mod_url('filepart');
		$callurl->set('id','__ID__');
		$callurl->set('type','error');
		
		$tpl->add_variables(array(
			'errors' => $errs,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('error_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'error_search',
			'file' => $file,
			'msg' => $msg,
			'typecbs' => $typecbs,
			'get_code_url' => $callurl->to_url()
		));
	}
	
	/**
	 * Builds the message
	 *
	 * @param PC_Obj_Error $err the error
	 * @return string the message
	 */
	private function _get_msg($err)
	{
		$msg = $err->get_msg();
		return preg_replace_callback(
			'/#(#?[a-zA-Z0-9_:]+?)#/',
			function($match)
			{
				$func = '';
				if(strstr($match[1],'::'))
				{
					list($class,$func) = explode('::',$match[1]);
					$func = '::'.$func;
				}
				else if($match[1] == PC_Obj_Variable::SCOPE_GLOBAL)
					return '<i>Global</i>';
				else
					$class = $match[1];
				
				$url = PC_URL::get_mod_url('class')->set('name',$class)->to_url();
				return "<a href=\"".$url."\">".$class."</a>".$func;
			},
			$msg
		);
	}
}
