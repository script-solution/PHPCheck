<?php
/**
 * Contains the finalizer-class
 * 
 * @package			PHPCheck
 * @subpackage	src.phpref
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
 * Finalizes the phpref regarding version-information and aliases
 * 
 * @package			PHPCheck
 * @subpackage	src.phpref
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PHPRef_Finalizer extends FWS_Object
{
	/**
	 * Collected aliases
	 * 
	 * @var array
	 */
	private $aliases = array();
	/**
	 * Version-info for methods
	 * 
	 * @var array
	 */
	private $versions = array();
	
	/**
	 * Constructor
	 * 
	 * @param array $aliases the collected aliases
	 * @param array $versions the collected versions
	 */
	public function __construct($aliases,$versions)
	{
		parent::__construct();
		$this->aliases = $aliases;
		$this->versions = $versions;
	}
	
	/**
	 * Fetches the page from the specified file and parses it for information about the function
	 */
	public function finalize()
	{
		$typecon = new PC_Engine_TypeContainer(PC_Project::PHPREF_ID,false,false);
		// fetch all classes and functions because we will probably need many of them
		$typecon->add_classes(PC_DAO::get_classes()->get_list(0,0,'','',PC_Project::PHPREF_ID));
		$typecon->add_functions(PC_DAO::get_functions()->get_list(0,0,0,'','',PC_Project::PHPREF_ID));
		
		foreach($this->versions as $vinfo)
		{
			list($classname,$funcname,$version) = $vinfo;
			$class = $typecon->get_class($classname);
			if($class !== null)
			{
				$func = $class->get_method($funcname);
				if($func !== null)
				{
					if($func->get_version()->is_empty())
						$func->get_version()->set($version->get_min(),$version->get_max());
					PC_DAO::get_functions()->update($func,$class->get_id());
				}
			}
		}
		
		// inherit version info to methods, if still empty
		foreach($typecon->get_classes() as $c)
		{
			$version = $c->get_version();
			foreach($c->get_methods() as $m)
			{
				if($m->get_version()->is_empty())
				{
					$m->get_version()->set($version->get_min(),$version->get_max());
					PC_DAO::get_functions()->update($m,$c->get_id());
				}
			}
		}
		
		foreach($this->aliases as $alias)
		{
			list($funcname,$aliasclass,$aliasfunc) = $alias;
			$aliasf = null;
			if($aliasclass)
			{
				$class = $typecon->get_class($aliasclass);
				if($class !== null)
					$aliasf = $class->get_method($aliasfunc);
			}
			else
				$aliasf = $typecon->get_function($aliasfunc);
			if($aliasf !== null)
			{
				$aliasf->set_name($funcname);
				PC_DAO::get_functions()->create($aliasf,0,PC_Project::PHPREF_ID);
			}
		}
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
