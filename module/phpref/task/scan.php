<?php
/**
 * Contains the phpref-task
 *
 * @version			$Id: scan.php 34 2010-08-27 14:30:53Z nasmussen $
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
		$aliases = $user->get_session_data('phpref_aliases');
		
		$classes = array();
		$typecon = new PC_Compile_TypeContainer(PC_Project::PHPREF_ID);
		for($i = $pos, $end = min($pos + $ops,$this->get_total_operations()); $i < $end; $i++)
		{
			try
			{
				$func = new PC_PHPRef_Function($files[$i]);
				$res = $func->get_method();
				if($res[0] == 'alias')
				{
					list(,$funcname,$aliasclass,$aliasfunc) = $res;
					$aliases[] = array($funcname,$aliasclass,$aliasfunc);
				}
				else if($res[0] != 'deprecated')
				{
					list(,$classname,$method) = $res;
					if($classname)
					{
						// collect class-methods; they're added with the class later on
						$class = $typecon->get_class($classname);
						if($class === null)
						{
							$class = new PC_Obj_Class('',0);
							$class->set_name($classname);
							$typecon->add_classes(array($class));
							$class->add_method($method);
							$classes[] = $class;
						}
						// if the class does already exist, create the function now
						else
							PC_DAO::get_functions()->create($method,$class->get_id(),PC_Project::PHPREF_ID);
					}
					else
							PC_DAO::get_functions()->create($method,0,PC_Project::PHPREF_ID);
				}
			}
			catch(PC_PHPRef_Exception $e)
			{
				$msgs->add_error($e->getMessage());
			}
		}
		
		// create collected classes
		foreach($classes as $class)
				PC_DAO::get_classes()->create($class,PC_Project::PHPREF_ID);
		
		// finally add aliases
		if($pos + $ops >= $this->get_total_operations())
		{
			foreach($aliases as $alias)
			{
				list($funcname,$aliasclass,$aliasfunc) = $alias;
				$aliasf = PC_DAO::get_functions()->get_by_name($aliasfunc,PC_Project::PHPREF_ID,$aliasclass);
				if($aliasf === null)
					$msgs->add_error('Alias "'.$aliasclass.'::'.$aliasfunc.'" for "'.$funcname.'" not found');
				else
				{
					$aliasf->set_name($funcname);
					PC_DAO::get_functions()->create($aliasf,0,PC_Project::PHPREF_ID);
				}
			}
		}
		$user->set_session_data('phpref_aliases',$aliases);
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
?>