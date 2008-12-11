<?php
/**
 * Contains the action-performer
 *
 * @version			$Id: performer.php 49 2008-07-30 12:35:41Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.action
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The action-performer. We overwrite it to provide a custom get_action_type()
 * method.
 *
 * @package			PHPCheck
 * @subpackage	src.action
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Action_Performer extends FWS_Actions_Performer
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->set_prefix('PC_Action_');
		$this->set_mod_folder('module/');
	}
	
	public function get_action_type()
	{
		$input = FWS_Props::get()->input();

		$action_type = $input->get_var('action_id','post',FWS_Input::INTEGER);
		if($action_type === null)
			$action_type = $input->get_var('aid','get',FWS_Input::INTEGER);

		return $action_type;
	}
}
?>