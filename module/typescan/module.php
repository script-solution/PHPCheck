<?php
/**
 * Contains the typescan-module
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module.typescan
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The typescan-module
 *
 * @package			PHPCheck
 * @subpackage	module.typescan
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_TypeScan extends PC_SubModuleContainer
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct('typescan',array('default','scan'),'default');
	}
	
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$renderer = $doc->use_default_renderer();
		$renderer->add_breadcrumb('Type scanner',PC_URL::build_mod_url('typescan'));
		
		// init submodule
		$this->_sub->init($doc);
	}
}
?>