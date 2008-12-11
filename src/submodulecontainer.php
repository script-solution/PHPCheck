<?php
/**
 * Contains the sub-module-container-class
 * 
 * @version			$Id: submodulecontainer.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The module-base class for all sub-module-containers. That means a module
 * that consists of sub-modules.
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
abstract class PC_SubModuleContainer extends PC_Module
{
	/**
	 * The sub-module
	 *
	 * @var PC_SubModule
	 */
	protected $_sub;
	
	/**
	 * Constructor
	 * 
	 * @param string $module your module-name
	 * @param array $subs the sub-module names that are possible
	 * @param string $default the default sub-module
	 */
	public function __construct($module,$subs = array(),$default = 'default')
	{
		$input = FWS_Props::get()->input();

		if(count($subs) == 0)
			FWS_Helper::error('Please provide the possible submodules of this module!');
		
		$sub = $input->correct_var('sub','get',FWS_Input::STRING,$subs,$default);
		
		// include the sub-module and create it
		include_once(FWS_Path::server_app().'module/'.$module.'/sub_'.$sub.'.php');
		$classname = 'PC_SubModule_'.$module.'_'.$sub;
		$this->_sub = new $classname();
	}

	/**
	 * @see FWS_Module::error_occurred()
	 *
	 * @return boolean
	 */
	public function error_occurred()
	{
		return parent::error_occurred() || $this->_sub->error_occurred();
	}

	/**
	 * @see FWS_Module::init($doc)
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$renderer = $doc->use_default_renderer();
		
		$classname = get_class($this->_sub);
		$lastus = strrpos($classname,'_');
		$prevlastus = strrpos(FWS_String::substr($classname,0,$lastus),'_');
		$renderer->set_template(FWS_String::strtolower(FWS_String::substr($classname,$prevlastus + 1)).'.htm');
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$this->_sub->run();
	}
}
?>