<?php
/**
 * Contains the cli-phpref-scan-module
 * 
 * @version			$Id: module.php 57 2010-09-03 23:13:08Z nasmussen $
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		$typecon = new PC_Compile_TypeContainer(PC_Project::PHPREF_ID);
		foreach($args as $file)
		{
			try
			{
				if(preg_match('/\/function\./',$file))
					$this->grab_function($typecon,$file);
				else
					$this->grab_class($typecon,$file);
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
	 * @param PC_Compile_TypeContainer $typecon the type-container
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
	 * @param PC_Compile_TypeContainer $typecon the type-container
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
}
?>