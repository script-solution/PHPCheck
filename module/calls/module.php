<?php
/**
 * Contains the calls-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,PC_DAO::get_calls()->get_count());
		$pagination->populate_tpl(PC_URL::get_mod_url());
		$start = $pagination->get_start();
		
		$calls = array();
		foreach(PC_DAO::get_calls()->get_list($start,PC_ENTRIES_PER_PAGE) as $call)
		{
			/* @var $call PC_Call */
			$url = PC_URL::get_mod_url('file');
			$url->set('path',$call->get_file());
			$url->set('line',$call->get_line());
			$url->set_anchor('l'.$call->get_line());
			$calls[] = array(
				'call' => $call->get_call(),
				'file' => $call->get_file(),
				'line' => $call->get_line(),
				'url' => $url->to_url()
			);
		}
		
		$tpl->add_variables(array(
			'calls' => $calls
		));
	}
}
?>