<?php
/**
 * Contains the cli-phpref-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
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
 * The cli-phpref-scan-module
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_CLI_PHPRef implements PC_CLIJob
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
	
	public function run($args)
	{
		$user = FWS_Props::get()->user();
		$errors = array();
		$options = new PC_Engine_Options();
		$options->set_pid(PC_Project::PHPREF_ID);
		$typecon = new PC_Engine_TypeContainer($options);
		foreach($args as $file)
		{
			try
			{
				if(preg_match('/\/class\./',$file))
					$this->grab_class($typecon,$file);
				else
					$this->grab_function($typecon,$file);
			}
			catch(PC_PHPRef_Exception $e)
			{
				$errors[] = $e->getMessage().' in file "'.$file.'"';
			}
		}
		
		// write stuff to shared data
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		/* @var $data PC_JobData */
		$data->add_errors($errors);
		$misc = $data->get_misc();
		$misc['versions'] = array_merge($misc['versions'],$this->versions);
		$misc['aliases'] = array_merge($misc['aliases'],$this->aliases);
		$data->set_misc($misc);
		$mutex->write(serialize($data));
		$mutex->close();
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
		if(count($res) == 0)
			return;
		
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
			{
				$this->versions[] = array(
					$classname,
					$method->get_name(),
					$method->get_version()
				);
			}
			else
				PC_DAO::get_functions()->create($method,0,PC_Project::PHPREF_ID);
		}
	}
}
