<?php
/**
 * Contains the errors-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		foreach(PC_DAO::get_errors()->get_list(0,$start,PC_ENTRIES_PER_PAGE,$file,$msg,$types) as $err)
		{
			/* @var $err PC_Error */
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$err->get_loc()->get_file());
			$url->set('line',$err->get_loc()->get_line());
			$url->set_anchor('l'.$err->get_loc()->get_line());
			
			$errs[] = array(
				'type' => PC_Error::get_type_name($err->get_type()),
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
		foreach(PC_Error::get_types() as $type => $name)
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
		
		$tpl->add_variables(array(
			'errors' => $errs,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('error_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'error_search',
			'file' => $file,
			'msg' => $msg,
			'typecbs' => $typecbs
		));
	}
	
	/**
	 * Builds the message
	 *
	 * @param PC_Error $err the error
	 * @return string the message
	 */
	private function _get_msg($err)
	{
		$msg = $err->get_msg();
		return preg_replace(
			'/#(.+?)#/e',
			'"<a href=\"".PC_URL::get_mod_url(\'class\')->set(\'name\',\'\\1\')->to_url()."\">\\1</a>"',
			$msg
		);
	}
}
?>