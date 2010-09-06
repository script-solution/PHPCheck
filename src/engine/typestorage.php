<?php
/**
 * Contains the type-storage-interface
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The interface to write changes in the finalizing-phase of the type-scanner to an arbitrary
 * storage.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
interface PC_Engine_TypeStorage
{
	/**
	 * Creates the given function for the given class-id
	 *
	 * @param PC_Obj_Method $method the method
	 * @param int $classid the class-id
	 * @return int the used id
	 */
	public function create_function($method,$classid);
	
	/**
	 * Updates the given function for the given class-id
	 *
	 * @param PC_Obj_Method $method the method
	 * @param int $classid the class-id
	 */
	public function update_function($method,$classid);
	
	/**
	 * Creates the given field for the given class-id
	 *
	 * @param PC_Obj_Field $field the field
	 * @param int $classid the class-id
	 * @return int the used id
	 */
	public function create_field($field,$classid);
	
	/**
	 * Creates the given constant for the given class-id
	 *
	 * @param PC_Obj_Constant $const the constant
	 * @param int $classid the class-id
	 * @return int the used id
	 */
	public function create_constant($const,$classid);
}
?>