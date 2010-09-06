<?php
/**
 * Contains the type-storage-implementation that writes it to the database
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The implementation of the type-storage to write changes in the finalizing-phase of the
 * type-scanner to the database
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_TypeStorage_DB implements PC_Engine_TypeStorage
{
	public function create_function($method,$classid)
	{
		return PC_DAO::get_functions()->create($method,$classid);
	}
	
	public function update_function($method,$classid)
	{
		PC_DAO::get_functions()->update($method,$classid);
	}
	
	public function create_field($field,$classid)
	{
		return PC_DAO::get_classfields()->create($field,$classid);
	}
	
	public function create_constant($const,$classid)
	{
		return PC_DAO::get_constants()->create($const,$classid);
	}
}
?>