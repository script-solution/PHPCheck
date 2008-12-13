<?php
/**
 * Contains the file-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The file-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_file extends PC_Module
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
		$input = FWS_Props::get()->input();
		$path = $input->get_var('path','get',FWS_Input::STRING);
		$renderer->add_breadcrumb('View file',PC_URL::get_mod_url()->set('path',$path)->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		$line = $input->get_var('line','get',FWS_Input::INTEGER);
		
		if(!isset($_GET['path']))
		{
			$this->report_error();
			return;
		}
		
		$path = urldecode($_GET['path']);
		if(PC_DAO::get_classes()->get_count_for_file($path) == 0 &&
				PC_DAO::get_calls()->get_count_for_file($path) == 0)
		{
			$this->report_error();
			return;
		}
		
		$source = PC_Utils::highlight_file($path,$line);
		$tpl->add_variables(array('source' => $source));
	}
}
?>