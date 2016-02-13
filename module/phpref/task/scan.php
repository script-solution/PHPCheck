<?php
/**
 * Contains the phpref-task
 * 
 * @package			PHPCheck
 * @subpackage	module
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
 * The task to scan the php-reference
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_PHPRef_Task_Scan extends FWS_Object implements FWS_Progress_Task
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
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('phpref_files',array()));
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		$msgs = FWS_Props::get()->msgs();
		$user = FWS_Props::get()->user();
		
		$files = $user->get_session_data('phpref_files');
		$this->aliases = $user->get_session_data('phpref_aliases');
		$this->versions = $user->get_session_data('phpref_versions');
		$typecon = new PC_Engine_TypeContainer(PC_Project::PHPREF_ID,true,false);
		
		for($i = $pos, $end = min($pos + $ops,$this->get_total_operations()); $i < $end; $i++)
		{
			try
			{
				if(preg_match('/\/function\./',$files[$i]))
					$this->grab_function($typecon,$files[$i]);
				else
					$this->grab_class($typecon,$files[$i]);
			}
			catch(PC_PHPRef_Exception $e)
			{
				$msgs->add_error($e->getMessage().' in file "'.$files[$i].'"');
			}
		}
		
		// finally add aliases
		if($pos + $ops >= $this->get_total_operations())
		{
			$fin = new PC_PHPRef_Finalizer($this->aliases,$this->versions);
			$fin->finalize();
		}
		$user->set_session_data('phpref_aliases',$this->aliases);
		$user->set_session_data('phpref_versions',$this->versions);
	}
	
	/**
	 * Grabs a class from the given file
	 * 
	 * @param PC_Engine_TypeContainer $typecon the type-container
	 * @param string $file the file
	 */
	private function grab_class($typecon,$file)
	{
		$classp = new PC_PHPRef_Class($file);
		PC_DAO::get_classes()->create($classp->get_class(),PC_Project::PHPREF_ID);
	}
	
	/**
	 * Grabs a function from the given file
	 * 
	 * @param PC_Engine_TypeContainer $typecon the type-container
	 * @param string $file the file
	 */
	private function grab_function($typecon,$file)
	{
		$func = new PC_PHPRef_Function($file);
		$res = $func->get_method();
		if($res[0] == 'alias')
		{
			list(,$funcname,$aliasclass,$aliasfunc) = $res;
			$this->aliases[] = array($funcname,$aliasclass,$aliasfunc);
		}
		else if($res[0] != 'deprecated')
		{
			list(,$classname,$method) = $res;
				// save method-version-information for later use
			if($classname)
				$this->versions[] = array($classname,$method->get_name(),$method->get_since());
			else
					PC_DAO::get_functions()->create($method,0,PC_Project::PHPREF_ID);
		}
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
