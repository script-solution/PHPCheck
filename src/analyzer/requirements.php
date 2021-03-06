<?php
/**
 * Contains the requirements-analyzer class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Is responsible for analyzing violations of the targeted minimum and maximum requirements.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Analyzer_Requirements extends PC_Analyzer
{
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Env $env the environment
	 */
	public function __construct($env)
	{
		parent::__construct($env);
	}
	
	/**
	 * Analyzes the requirements for given object
	 *
	 * @param PC_Obj_Location $object the object to convert to string for the error message
	 * @param array $need_min the minimally needed versions
	 * @param array $need_max the maximally needed versions
	 */
	public function analyze($object,$need_min,$need_max)
	{
		$tgtmin = $this->env->get_options()->get_min_req();
		$tgtmax = $this->env->get_options()->get_max_req();
		
		foreach($need_min as $nv)
		{
			list($nname,$nversion) = $this->get_version($nv);
			
			if(!isset($tgtmin[$nname]))
			{
				$this->report(
					$object,
					$object.' requires '.$nname.' >= '.$nversion,
					PC_Obj_Error::E_S_REQUIRES_NEWER
				);
			}
			else
			{
				$minv = $tgtmin[$nname];
				if($this->compare_versions($nversion,$minv) > 0)
				{
					$this->report(
						$object,
						$object.' requires '.$nname.' >= '.$nversion.', but you target '.$nname.' >= '.$minv,
						PC_Obj_Error::E_S_REQUIRES_NEWER
					);
				}
			}
		}

		if(count($tgtmax) > 0)
		{
			foreach($need_max as $mv)
			{
				list($nname,$nversion) = $this->get_version($mv);
				
				if(isset($tgtmax[$nname]))
				{
					$maxv = $tgtmax[$nname];
					if($this->compare_versions($nversion,$maxv) < 0)
					{
						$this->report(
							$object,
							$object.' exists only till '.$nname.' '.$nversion.', but you target '.$nname.' < '.$maxv,
							PC_Obj_Error::E_S_REQUIRES_OLDER
						);
					}
				}
			}
		}
	}
	
	/**
	 * Compares the two given versions.
	 *
	 * @param string $vers1 the first version
	 * @param string $vers2 the second version
	 * @return int the result: -1 if $vers1 < $vers2, 1 if $vers1 > $vers2, 0 otherwise
	 */
	private function compare_versions($vers1,$vers2)
	{
		$v1 = explode('.',$vers1);
		$v2 = explode('.',$vers2);
		if(count($v1) < count($v2))
		{
			for($i = count($v1); $i < count($v2); $i++)
				$v1[] = 0;
		}
		else if(count($v2) < count($v1))
		{
			for($i = count($v2); $i < count($v1); $i++)
				$v2[] = 0;
		}
		
		for($i = 0; $i < count($v1); $i++)
		{
			if($v1[$i] < $v2[$i])
				return -1;
			if($v1[$i] > $v2[$i])
				return 1;
		}
		return 0;
	}
	
	/**
	 * Parses the given version information
	 *
	 * @param string $name the version name
	 * @return array an array with the component name and the version number
	 */
	private function get_version($name)
	{
		if(FWS_String::starts_with($name,'PHP '))
			return explode(' ',$name);

		list($name1,$name2,$version) = explode(' ',$name);
		return array($name1.' '.$name2,$version);
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
