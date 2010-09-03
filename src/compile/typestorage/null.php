<?php
/**
 * Contains the type-storage-implementation that does nothing
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The null-implementation of the type-storage to write changes in the finalizing-phase of the
 * type-scanner
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Compile_TypeStorage_Null implements PC_Compile_TypeStorage
{
	public function create_function($method,$classid)
	{
		// do nothing
		return 0;
	}
	
	public function update_function($method,$classid)
	{
		// do nothing
	}
	
	public function create_field($field,$classid)
	{
		// do nothing
		return 0;
	}
	
	public function create_constant($const,$classid)
	{
		// do nothing
		return 0;
	}
}
?>