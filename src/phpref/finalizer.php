<?php
/**
 * Contains the finalizer-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Finalizes the phpref regarding version-information and aliases
 * 
 * @package			PHPCheck
 * @subpackage	src
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
	 * 
	 * @return PC_Obj_Method the method that was found
	 * @throws PC_PHPRef_Exception if it failed
	 */
	public function finalize()
	{
		$typecon = new PC_Compile_TypeContainer(PC_Project::PHPREF_ID,false,false);
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
					$func->set_since($version);
					PC_DAO::get_functions()->update($func,$class->get_id());
				}
			}
		}
		
		foreach($this->aliases as $alias)
		{
			list($funcname,$aliasclass,$aliasfunc) = $alias;
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
?>