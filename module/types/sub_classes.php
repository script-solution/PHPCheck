<?php
/**
 * Contains the classes-types-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The classes submodule for module types
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_types_classes extends PC_SubModule
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
		$renderer->add_breadcrumb('Classes',PC_URL::build_submod_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		
		$pagination = new PC_Pagination(PC_ENTRIES_PER_PAGE,PC_DAO::get_classes()->get_count());
		$pagination->populate_tpl(PC_URL::get_submod_url());
		$start = $pagination->get_start();
		
		$classes = array();
		foreach(PC_DAO::get_classes()->get_list($start,PC_ENTRIES_PER_PAGE) as $class)
		{
			$classes[] = array(
				'name' => $class->get_name(),
				'file' => $class->get_file(),
				'line' => $class->get_line(),
				'url' => PC_URL::get_mod_url('class')->set('class',$class->get_name())->to_url()
			);
		}
		
		$tpl->add_variables(array(
			'classes' => $classes
		));
	}
}
?>