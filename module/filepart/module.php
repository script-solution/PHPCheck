<?php
/**
 * Contains the filepart-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The filepart-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_filepart extends PC_Module
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
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		$doc = FWS_Props::get()->doc();
		
		$id = $input->get_var('id','get',FWS_Input::INTEGER);
		$type = $input->correct_var('type','get',FWS_Input::STRING,array('call','error'),'call');
		if($id === null)
			return $this->report_error();
		
		$loc = null;
		switch($type)
		{
			case 'call':
				$loc = PC_DAO::get_calls()->get_by_id($id);
				break;
			case 'error':
				$loc = PC_DAO::get_errors()->get_by_id($id);
				if($loc === null)
					return $this->report_error();
				$loc = $loc->get_loc();
				break;
		}
		
		if(!is_file($loc->get_file()))
			return $this->report_error();
		
		$lines = explode("\n",file_get_contents($loc->get_file()));
		$start_line = max(1,$loc->get_line() - 4);
		$end_line = min(count($lines),$loc->get_line() + 2);
		$code = '';
		for($i = $start_line; $i <= $end_line; $i++)
			$code .= $lines[$i - 1]."\n";
		$code = PC_Utils::highlight_string($code,$start_line,$loc->get_line(),false);
		
		$renderer = $doc->use_raw_renderer();
		$renderer->set_content($code);
	}
}
?>