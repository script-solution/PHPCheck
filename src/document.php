<?php
/**
 * Contains the document-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The document for phpcheck
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Document extends FWS_Document
{
	/**
	 * Returns the default renderer. If it is already set the instance will be returned. Otherwise
	 * it will be created, set and returned.
	 *
	 * @return PC_Renderer_HTML
	 */
	public function use_default_renderer()
	{
		$renderer = $this->get_renderer();
		if($renderer instanceof PC_Renderer_HTML)
			return $renderer;
		
		$renderer = new PC_Renderer_HTML();
		$this->set_renderer($renderer);
		return $renderer;
	}

	/**
	 * @see FWS_Document::load_module()
	 *
	 * @return PC_Module
	 */
	protected function load_module()
	{
		$this->_module_name = FWS_Document::load_module_def(
			'PC_Module_','module','home','module'
		);
		$class = 'PC_Module_'.$this->_module_name;
		return new $class();
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
?>