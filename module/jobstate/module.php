<?php
/**
 * Contains the jobstate-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The jobstate-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_jobstate extends PC_Module
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$doc->use_raw_renderer();
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$doc = FWS_Props::get()->doc();
		$user = FWS_Props::get()->user();
		$renderer = $doc->use_raw_renderer();
		
		// read from file
		$mutex = new FWS_MutexFile(PC_CLI_MUTEX_FILE);
		$mutex->aquire();
		$data = unserialize($mutex->read());
		$mutex->close();
		
		/* @var $data PC_JobData */
		$res = $data->get_done().';';
		if(count($data->get_errors()))
		{
			$res .= '<div style="text-align: left; padding-left: 3em;"><ul>';
			foreach($data->get_errors() as $err)
				$res .= '<li>'.$err.'</li>';
			$res .= '</ul></div>';
		}
		
		$renderer->set_content($res);
	}
}
?>