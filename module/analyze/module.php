<?php
/**
 * Contains the analyze-module
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The analyze-module
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_analyze extends PC_Module implements FWS_Progress_Listener
{
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		$renderer = $doc->use_default_renderer();
		$renderer->add_breadcrumb('Analyzing...',PC_URL::build_mod_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$user = FWS_Props::get()->user();

		$storage = new FWS_Progress_Storage_Session('panalyze_');
		$this->_pm = new FWS_Progress_Manager($storage);
		$this->_pm->set_ops_per_cycle(PC_ANALYZE_ITEMS_PER_CYCLE);
		$this->_pm->add_listener($this);
		
		$task = new PC_Module_Analyze_Task_Scan();
		$this->_pm->run_task($task);
	}

	/**
	 * @see FWS_Progress_Listener::cycle_finished()
	 *
	 * @param int $pos
	 * @param int $total
	 */
	public function cycle_finished($pos,$total)
	{
		$this->_populate_template();
	}

	/**
	 * @see FWS_Progress_Listener::progress_finished()
	 */
	public function progress_finished()
	{
		$this->_populate_template();
	}
	
	/**
	 * Adds the variables to the template
	 */
	private function _populate_template()
	{
		$tpl = FWS_Props::get()->tpl();
		$user = FWS_Props::get()->user();
		
		$tpl->add_variables(array(
			'not_finished' => !$this->_pm->is_finished(),
			'percent' => round($this->_pm->get_percentage(),1),
			'message' => '',
			'target_url' => PC_URL::build_mod_url(0,'&')
		));
	}
}
?>