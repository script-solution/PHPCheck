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
		
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,PC_DAO::get_errors()->get_count());
		$pagination->populate_tpl(PC_URL::get_mod_url());
		$start = $pagination->get_start();
		
		$errs = array();
		foreach(PC_DAO::get_errors()->get_list(0,$start,PC_ENTRIES_PER_PAGE) as $err)
		{
			/* @var $call PC_Call */
			$errs[] = array(
				'type' => PC_Error::get_type_name($err->get_type()),
				'message' => $this->_get_msg($err),
				'file' => $err->get_loc()->get_file(),
				'line' => $err->get_loc()->get_line()
			);
		}
		
		$tpl->add_variables(array(
			'errors' => $errs
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