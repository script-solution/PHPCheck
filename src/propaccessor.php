<?php
/**
 * Contains the property-accessor-class
 *
 * @version			$Id: propaccessor.php 52 2008-07-30 19:26:50Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The property-accessor for PHPCheck. We change and add some properties to the predefined
 * ones.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_PropAccessor extends FWS_PropAccessor
{
	/**
	 * @return FWS_MySQL the db-property
	 */
	public function db()
	{
		return $this->get('db');
	}
	
	/**
	 * @return PC_Project the current project
	 */
	public function project()
	{
		return $this->get('project');
	}
}
?>