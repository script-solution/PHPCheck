<?php
/**
 * Contains the renderer-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src.document
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The renderer for this project
 * 
 * @package			PHPCheck
 * @subpackage	src.document
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Renderer_HTML extends FWS_Document_Renderer_HTML_Default
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$tpl = FWS_Props::get()->tpl();
		
		$tpl->set_path('templates/');
		$tpl->set_cache_folder(FWS_Path::server_app().'cache/');
		
		// add the home-breadcrumb
		$url = new FWS_URL();
		$url->set('action','start');
		$this->add_breadcrumb('PHP-Check',$url->to_url());
	}
	
	/**
	 * @see FWS_Document_Renderer_HTML_Default::before_start()
	 */
	protected function before_start()
	{
		parent::before_start();
		
		$doc = FWS_Props::get()->doc();
		
		// set the default template if not already done
		$template = '';
		if($this->get_template() === null)
		{
			$classname = get_class($doc->get_module());
			$prefixlen = FWS_String::strlen('PC_Module_');
			$template = FWS_String::strtolower(FWS_String::substr($classname,$prefixlen)).'.htm';
			$this->set_template($template);
		}
	}
	
	/**
	 * @see FWS_Document_Renderer_HTML_Default::before_render()
	 */
	protected function before_render()
	{
		$tpl = FWS_Props::get()->tpl();

		$js = FWS_Javascript::get_instance();
		$js->set_cache_folder('cache');
		$tpl->add_global_ref('js',$js);
		$tpl->add_allowed_method('js','get_file');
	}

	/**
	 * @see FWS_Document_Renderer_HTML_Default::footer()
	 */
	protected function footer()
	{
		// nothing to do yet
	}

	/**
	 * @see FWS_Document_Renderer_HTML_Default::header()
	 */
	protected function header()
	{
		$tpl = FWS_Props::get()->tpl();
		
		$tpl->set_template('inc_header.htm');
		$tpl->add_variables(array(
			'location' => $this->get_breadcrumbs()
		));
		$tpl->restore_template();
	}
}
?>