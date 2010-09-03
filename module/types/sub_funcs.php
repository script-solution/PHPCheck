<?php
/**
 * Contains the functions-types-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		foreach(PC_DAO::get_functions()->get_list(0,$start,PC_ENTRIES_PER_PAGE,$file,$func) as $f)
		{
			$funcs[] = array(
				'func' => (string)$f,
				'file' => $f->get_file(),
				'line' => $f->get_line(),
				'since' => $f->get_since()
			);
		}
		
		$this->request_formular();
		$tpl->add_variables(array(
			'funcs' => $funcs,
			'file' => $file,
			'func' => $func,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('funcs_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'funcs_search',
		));
	}
}
?>