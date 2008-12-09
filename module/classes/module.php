<?php
/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

final class PC_Module_Classes extends FWS_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		$renderer = $doc->use_default_renderer();
		$url = new FWS_URL();
		$url->set('module','classes');
		$renderer->add_breadcrumb('Classes',$url->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		list(,,$classes) = PC_Utils::get_data();
		
		$url = new FWS_URL();
		$url->set('module','class');
		
		$tplclasses = array();
		ksort($classes);
		foreach($classes as $class)
		{
			$tplclasses[] = array(
				'name' => $class->get_name(),
				'file' => $class->get_file(),
				'line' => $class->get_line(),
				'url' => $url->set('name',$class->get_name())->to_url()
			);
		}
		$tpl->add_variable_ref('classes',$tplclasses);
	}
}
?>