<?php
/**
 * Contains the constants-types-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The constants submodule for module types
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_types_consts extends PC_SubModule
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
		$renderer->add_breadcrumb('Constants',PC_URL::build_submod_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,PC_DAO::get_constants()->get_count());
		$pagination->populate_tpl(PC_URL::get_submod_url());
		$start = $pagination->get_start();
		
		$consts = array();
		foreach(PC_DAO::get_constants()->get_list($start,PC_ENTRIES_PER_PAGE) as $const)
		{
			$consts[] = array(
				'name' => $const->get_name(),
				'type' => (string)$const->get_type(),
				'file' => $const->get_file(),
				'line' => $const->get_line()
			);
		}
		
		$tpl->add_variables(array(
			'consts' => $consts
		));
	}
}
?>