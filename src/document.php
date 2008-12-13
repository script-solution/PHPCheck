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
	 * @see FWS_Document::use_default_renderer
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
		$this->_module_name = FWS_Helper::get_module_name(
			'PC_Module_','module','typescan','module'
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