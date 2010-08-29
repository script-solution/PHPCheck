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
		
		$ap = $this->get_action_performer();
		$ap->set_prefix('PC_Action_');
		$ap->set_mod_folder('module/');
		
		$tpl = FWS_Props::get()->tpl();
		
		$tpl->set_path('templates/');
		$tpl->set_cache_folder(FWS_Path::server_app().'cache/');
		
		// add the home-breadcrumb
		$url = new FWS_URL();
		$url->set('action','home');
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
		$doc = FWS_Props::get()->doc();

		$js = FWS_Javascript::get_instance();
		$js->set_cache_folder('cache');
		$tpl->add_global_ref('gjs',$js);
		$tpl->add_allowed_method('gjs','get_file');
		
		$url = new PC_URL();
		$tpl->add_global_ref('gurl',$url);
		$tpl->add_allowed_method('gurl','build_mod_url');
		$tpl->add_allowed_method('gurl','build_submod_url');
		
		// add redirect information
		$redirect = $doc->get_redirect();
		if($redirect)
			$tpl->add_variable_ref('redirect',$redirect,'inc_header.htm');
		
		// notify the template if an error has occurred
		$tpl->add_global('module_error',$doc->get_module()->error_occurred());
		
		$action_result = $this->get_action_result();
		$tpl->add_global('action_result',$action_result);
	}

	/**
	 * @see FWS_Document_Renderer_HTML_Default::header()
	 */
	protected function header()
	{
		$tpl = FWS_Props::get()->tpl();
		
		$this->get_action_performer()->add_action(new PC_Action_chgproject(PC_ACTION_CHG_PROJECT));
		$this->perform_action();
		
		$projects = PC_DAO::get_projects()->get_all();
		$pronames = array();
		foreach($projects as $project)
			$pronames[$project->get_id()] = $project->get_name();
		
		$form = new FWS_HTML_Formular();
		$tpl->set_template('inc_header.htm');
		$tpl->add_allowed_method('form','*');
		$tpl->add_variables(array(
			'location' => $this->get_breadcrumb_links(),
			'form' => $form,
			'projects' => $pronames,
			'chg_project_aid' => PC_ACTION_CHG_PROJECT,
			'project' => (FWS_Props::get()->project() !== null) ? FWS_Props::get()->project()->get_id() : 0
		));
		$tpl->restore_template();
	}

	/**
	 * @see FWS_Document_Renderer_HTML_Default::footer()
	 */
	protected function footer()
	{
		$db = FWS_Props::get()->db();
		$locale = FWS_Props::get()->locale();
		$doc = FWS_Props::get()->doc();
		$tpl = FWS_Props::get()->tpl();
		$profiler = $doc->get_profiler();
		
		$mem = FWS_StringHelper::get_formated_data_size(
			$profiler->get_memory_usage(),$locale->get_thousands_separator(),
			$locale->get_dec_separator()
		);
		
		$tpl->set_template('inc_footer.htm');
		$tpl->add_variables(array(
			'version' => PC_VERSION,
			'time' => $profiler->get_time(),
			'queries' => $db->get_query_count(),
			'memory' => $mem
		));
		$tpl->restore_template();
	}
}
?>