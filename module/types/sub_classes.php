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
		$cookies = FWS_Props::get()->cookies();
		
		$file = $input->get_var('file',-1,FWS_Input::STRING);
		$class = $input->get_var('class',-1,FWS_Input::STRING);
		
		$url = PC_URL::get_submod_url();
		$url->set('file',$file);
		$url->set('class',$class);
		$surl = clone $url;
		
		$pagination = new PC_Pagination(
			PC_ENTRIES_PER_PAGE,PC_DAO::get_classes()->get_count_for($class,$file)
		);
		$pagination->populate_tpl($url);
		$start = $pagination->get_start();
		
		$classes = array();
		foreach(PC_DAO::get_classes()->get_list($start,PC_ENTRIES_PER_PAGE,$class,$file) as $c)
		{
			$classes[] = array(
				'name' => $c->get_name(),
				'file' => $c->get_file(),
				'line' => $c->get_line(),
				'url' => PC_URL::get_mod_url('class')->set('name',$c->get_name())->to_url()
			);
		}
		
		$this->request_formular();
		$tpl->add_variables(array(
			'classes' => $classes,
			'file' => $file,
			'class' => $class,
			'search_target' => $surl->to_url(),
			'display_search' => $cookies->get_cookie('classes_search') ? 'block' : 'none',
			'cookie_name' => $cookies->get_prefix().'classes_search',
		));
	}
}
?>