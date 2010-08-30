<?php
/**
 * Contains the visible-interface
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The interface for all objects that have a visibility
 * 
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
interface PC_Obj_Visible
{
	/**
	 * Represents a visibility of 'public'
	 */
	const V_PUBLIC = 'public';
	
	/**
	 * Represents a visibility of 'protected'
	 */
	const V_PROTECTED = 'protected';
	
	/**
	 * Represents a visibility of 'private'
	 */
	const V_PRIVATE = 'private';
	
	
	/**
	 * @return string the visibility of the method (see self::V_*)
	 */
	public function get_visibility();
	
	/**
	 * Sets the visiblity of the method
	 *
	 * @param string $visibility the new value (see self::V_*)
	 */
	public function set_visibility($visibility);
} 
?>