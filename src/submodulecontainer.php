<?php
/**
 * Contains the sub-module-container-class
 * 
 * @package			PHPCheck
 * @subpackage	src
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
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
	protected $sub;
	
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
		$this->sub = new $classname();
	}

	/**
	 * @see FWS_Module::error_occurred()
	 *
	 * @return boolean
	 */
	public function error_occurred()
	{
		return parent::error_occurred() || $this->sub->error_occurred();
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
		
		$classname = get_class($this->sub);
		$lastus = strrpos($classname,'_');
		$prevlastus = strrpos(FWS_String::substr($classname,0,$lastus),'_');
		$renderer->set_template(FWS_String::strtolower(FWS_String::substr($classname,$prevlastus + 1)).'.htm');
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$this->sub->run();
	}
}
