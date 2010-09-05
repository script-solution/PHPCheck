<?php
/**
 * Contains the phpref-scanner-submodule
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The scan submodule for module phpref-scanner
 * 
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_SubModule_PHPRef_scan extends PC_SubModule implements FWS_Progress_Listener
{
	/**
	 * The process manager
	 *
	 * @var FWS_Progress_Manager
	 */
	private $_pm;
	
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$renderer = $doc->use_default_renderer();
		$renderer->add_breadcrumb('Scanning...');
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$user = FWS_Props::get()->user();

		$storage = new FWS_Progress_Storage_Session('pphprefscan_');
		$this->_pm = new FWS_Progress_Manager($storage);
		$this->_pm->set_ops_per_cycle(PC_PHPREF_PAGES_PER_CYCLE);
		$this->_pm->add_listener($this);
		
		$task = new PC_Module_PHPRef_Task_Scan();
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
		$user = FWS_Props::get()->user();
		$user->delete_session_data('phpref_files');
		$user->delete_session_data('phpref_aliases');
		$user->delete_session_data('phpref_versions');
	}
	
	/**
	 * Adds the variables to the template
	 */
	private function _populate_template()
	{
		$tpl = FWS_Props::get()->tpl();
		$user = FWS_Props::get()->user();
		
		$total = count($user->get_session_data('phpref_files',array()));
		$message = sprintf(
			'Scanned %d of %d pages',$this->_pm->is_finished() ? $total : $this->_pm->get_position(),$total
		);
		$tpl->add_variables(array(
			'not_finished' => !$this->_pm->is_finished(),
			'percent' => round($this->_pm->get_percentage(),1),
			'message' => $message,
			'target_url' => PC_URL::build_submod_url(0,'scan','&')
		));
	}
}
?>