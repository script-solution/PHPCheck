<?php
/**
 * Contains the error-class
 * 
 * @package			PHPCheck
 * @subpackage	src.obj
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Stores all properties of an error that has been found
 *
 * @package			PHPCheck
 * @subpackage	src.obj
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Obj_Error extends FWS_Object
{
	const R_TYPESCANNER													= 0;
	const R_STMTSCANNER													= 1;
	
	const E_S_METHOD_MISSING										= 0;
	const E_S_ABSTRACT_CLASS_INSTANTIATION			= 1;
	const E_S_STATIC_CALL												= 2;
	const E_S_NONSTATIC_CALL										= 3;
	const E_S_CLASS_MISSING											= 4;
	const E_S_CLASS_UNKNOWN											= 5;
	const E_S_FUNCTION_MISSING									= 6;
	const E_S_WRONG_ARGUMENT_COUNT							= 7;
	const E_S_WRONG_ARGUMENT_TYPE								= 8;
	const E_S_FINAL_CLASS_INHERITANCE						= 9;
	const E_S_CLASS_NOT_ABSTRACT								= 10;
	const E_S_INTERFACE_MISSING									= 11;
	const E_S_IF_IS_NO_IF												= 12;
	const E_S_CALLABLE_INVALID									= 13;
	const E_S_MIXED_RET_AND_NO_RET							= 14;
	const E_S_RETURNS_DIFFER_FROM_SPEC					= 15;
	const E_S_RET_SPEC_BUT_NO_RET								= 16;
	const E_S_RET_BUT_NO_RET_SPEC								= 17;
	const E_S_UNDEFINED_VAR											= 18;
	const E_S_NOT_EXISTING_FIELD								= 19;
	const E_S_DOC_WITHOUT_THROW									= 20;
	const E_S_THROW_NOT_IN_DOC									= 21;
	const E_S_THROW_INVALID											= 22;
	const E_S_CONSTR_RETURN											= 23;
	const E_S_VOID_ASSIGN												= 24;
	const E_S_METHOD_VISIBILITY									= 25;
	const E_S_VAR_UNUSED												= 26;
	const E_S_PARAM_UNUSED											= 27;
	const E_S_REQUIRES_NEWER										= 28;
	const E_S_REQUIRES_OLDER										= 29;
	
	const E_T_MAGIC_IS_STATIC										= 50;
	const E_T_DOC_WITHOUT_PARAM									= 51;
	const E_T_PARAM_WITHOUT_DOC									= 52;
	const E_T_MAGIC_METHOD_PARAMS_INVALID				= 53;
	const E_T_MAGIC_METHOD_RET_INVALID					= 54;
	const E_T_MAGIC_NOT_PUBLIC									= 55;
	const E_T_MAGIC_NOT_STATIC									= 56;
	const E_T_PARAM_DIFFERS_FROM_DOC						= 57;
	const E_T_RETURN_DIFFERS_FROM_DOC						= 58;
	
	/**
	 * Determines the name of the given type
	 *
	 * @param int $type the type
	 * @return string the name
	 */
	public static function get_type_name($type)
	{
		$types = self::get_types();
		return isset($types[$type]) ? $types[$type] : 'Unknown';
	}
	
	/**
	 * An array of all types: <code>array(<type> => <name>,...)</code>
	 *
	 * @return array all types
	 */
	public static function get_types()
	{
		static $types = array(
			self::E_S_METHOD_MISSING =>									'Method missing',
			self::E_S_ABSTRACT_CLASS_INSTANTIATION =>		'Abstract class instantiation',
			self::E_S_STATIC_CALL => 										'Static call',
			self::E_S_NONSTATIC_CALL =>									'Nonstatic call',
			self::E_S_CLASS_MISSING =>									'Class missing',
			self::E_S_CLASS_UNKNOWN =>									'Class unknown',
			self::E_S_FUNCTION_MISSING =>								'Function missing',
			self::E_S_WRONG_ARGUMENT_COUNT =>						'Wrong arg count',
			self::E_S_WRONG_ARGUMENT_TYPE =>						'Wrong arg type',
			self::E_S_FINAL_CLASS_INHERITANCE =>				'Final class inheritance',
			self::E_S_CLASS_NOT_ABSTRACT =>							'Class not abstract',
			self::E_S_INTERFACE_MISSING =>							'Interface missing',
			self::E_S_IF_IS_NO_IF =>										'Implemented class',
			self::E_S_CALLABLE_INVALID =>								'Callable is invalid',
			self::E_S_MIXED_RET_AND_NO_RET =>						'Mixed return',
			self::E_S_RETURNS_DIFFER_FROM_SPEC =>				'Returns differ from spec',
			self::E_S_RET_SPEC_BUT_NO_RET =>						'Return spec but no return',
			self::E_S_RET_BUT_NO_RET_SPEC =>						'Returns but no return spec',
			self::E_S_UNDEFINED_VAR =>									'Variable undefined',
			self::E_S_NOT_EXISTING_FIELD =>							'Not existing field',
			self::E_S_DOC_WITHOUT_THROW =>							'Throws spec but no throw',
			self::E_S_THROW_NOT_IN_DOC =>								'Throws but no throws spec',
			self::E_S_THROW_INVALID =>									'Invalid throw',
			self::E_S_CONSTR_RETURN =>									'Constructor returns value',
			self::E_S_VOID_ASSIGN =>										'Assignment of void',
			self::E_S_METHOD_VISIBILITY =>							'Method not visible',
			self::E_S_VAR_UNUSED =>											'Unused variable',
			self::E_S_PARAM_UNUSED =>										'Unused parameter',
			self::E_S_REQUIRES_NEWER =>									'Feature not yet available',
			self::E_S_REQUIRES_OLDER =>									'Feature no longer available',
			
			self::E_T_MAGIC_METHOD_PARAMS_INVALID =>		'Magic params invalid',
			self::E_T_MAGIC_METHOD_RET_INVALID =>				'Magic return invalid',
			self::E_T_MAGIC_NOT_PUBLIC =>								'Magic not public',
			self::E_T_MAGIC_NOT_STATIC =>								'Magic not static',
			self::E_T_MAGIC_IS_STATIC =>								'Magic is static',
			self::E_T_DOC_WITHOUT_PARAM =>							'Doc without param',
			self::E_T_PARAM_WITHOUT_DOC =>							'Param without doc',
			self::E_T_PARAM_DIFFERS_FROM_DOC =>					'Param differs from doc',
			self::E_T_RETURN_DIFFERS_FROM_DOC =>				'Return differs from doc',
		);
		return $types;
	}
	
	/**
	 * Returns all types that are reported by $reporter
	 * 
	 * @param int $reporter the reporter (self::R_*)
	 * @return array an array of the types
	 */
	public static function get_types_of($reporter)
	{
		static $rep2types = array(
			self::R_TYPESCANNER => array(
				self::E_T_DOC_WITHOUT_PARAM,
				self::E_T_PARAM_WITHOUT_DOC,
				self::E_T_MAGIC_METHOD_PARAMS_INVALID,
				self::E_T_MAGIC_METHOD_RET_INVALID,
				self::E_T_MAGIC_NOT_PUBLIC,
				self::E_T_MAGIC_NOT_STATIC,
				self::E_T_MAGIC_IS_STATIC,
				self::E_T_PARAM_DIFFERS_FROM_DOC,
				self::E_T_RETURN_DIFFERS_FROM_DOC,
			),
			self::R_STMTSCANNER => array(
				self::E_S_MIXED_RET_AND_NO_RET,
				self::E_S_RETURNS_DIFFER_FROM_SPEC,
				self::E_S_RET_SPEC_BUT_NO_RET,
				self::E_S_RET_BUT_NO_RET_SPEC,
				self::E_S_UNDEFINED_VAR,
				self::E_S_NOT_EXISTING_FIELD,
				self::E_S_DOC_WITHOUT_THROW,
				self::E_S_THROW_NOT_IN_DOC,
				self::E_S_THROW_INVALID,
				self::E_S_CONSTR_RETURN,
				self::E_S_VOID_ASSIGN,
				self::E_S_METHOD_VISIBILITY,
				self::E_S_VAR_UNUSED,
				self::E_S_PARAM_UNUSED,
				self::E_S_REQUIRES_NEWER,
				self::E_S_REQUIRES_OLDER,
				self::E_S_METHOD_MISSING,
				self::E_S_ABSTRACT_CLASS_INSTANTIATION,
				self::E_S_STATIC_CALL,
				self::E_S_NONSTATIC_CALL,
				self::E_S_CLASS_MISSING,
				self::E_S_CLASS_UNKNOWN,
				self::E_S_FUNCTION_MISSING,
				self::E_S_WRONG_ARGUMENT_COUNT,
				self::E_S_WRONG_ARGUMENT_TYPE,
				self::E_S_FINAL_CLASS_INHERITANCE,
				self::E_S_CLASS_NOT_ABSTRACT,
				self::E_S_CLASS_MISSING,
				self::E_S_INTERFACE_MISSING,
				self::E_S_IF_IS_NO_IF,
				self::E_S_CALLABLE_INVALID,
			)
		);
		return $rep2types[$reporter];
	}
	
	/**
	 * The error-id
	 * 
	 * @var int
	 */
	private $id = 0;
	
	/**
	 * The error-location
	 *
	 * @var PC_Obj_Location
	 */
	private $loc;
	
	/**
	 * The error-type
	 * 
	 * @var int
	 */
	private $type;
	
	/**
	 * The error-message
	 * 
	 * @var string
	 */
	private $msg;
	
	/**
	 * Constructor
	 *
	 * @param PC_Obj_Location $loc the location of the error
	 * @param string $msg the message to display
	 * @param int $type the error-type. See self::E_*
	 */
	public function __construct($loc,$msg,$type)
	{
		parent::__construct();
		
		if(!($loc instanceof PC_Obj_Location))
			FWS_Helper::def_error('instance','loc','PC_Obj_Location',$loc);
		if(!FWS_Helper::is_integer($type) || $type < 0)
			FWS_Helper::def_error('intge0','type',$type);
		
		$this->loc = $loc;
		$this->msg = $msg;
		$this->type = $type;
	}
	
	/**
	 * @return int the id
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * Sets the id
	 * 
	 * @param int $id the new value
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	
	/**
	 * @return PC_Obj_Location the error-location
	 */
	public function get_loc()
	{
		return $this->loc;
	}
	
	/**
	 * @return string the error-message
	 */
	public function get_msg()
	{
		return $this->msg;
	}
	
	/**
	 * @return int the error-type
	 */
	public function get_type()
	{
		return $this->type;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
	
	public function __ToString()
	{
		return '['.$this->loc->get_file().', '.$this->loc->get_line().'] #'.$this->type.' '.$this->msg;
	}
}
