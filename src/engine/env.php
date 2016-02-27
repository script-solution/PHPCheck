<?php
/**
 * Contains the environment class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
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
 * Contains all the containers and the options that are used during parsing and analyzing.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_Env extends FWS_Object
{
	/**
	 * The container for all errors / warnings
	 *
	 * @var PC_Engine_ErrorContainer
	 */
	private $errors;
	/**
	 * The known types
	 * 
	 * @var PC_Engine_TypeContainer
	 */
	private $types;
	/**
	 * The options
	 *
	 * @var PC_Engine_Options
	 */
	private $options;
	/**
	 * The storage-implementation
	 *
	 * @var PC_Engine_TypeStorage
	 */
	private $storage;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Options $options the options
	 * @param PC_Engine_TypeContainer $types the type-container
	 * @param PC_Engine_TypeStorage $storage the storage that handles the changes
	 * @param PC_Engine_ErrorContainer $errors the error container
	 */
	public function __construct($options = null,$types = null,$storage = null,$errors = null)
	{
		if($options && !($options instanceof PC_Engine_Options))
			FWS_Helper::def_error('instance','options','PC_Engine_Options',$options);
		if($types && !($types instanceof PC_Engine_TypeContainer))
			FWS_Helper::def_error('instance','types',PC_Engine_TypeContainer,$types);
		if($storage && !($storage instanceof PC_Engine_TypeStorage))
			FWS_Helper::def_error('instance','storage','PC_Engine_TypeStorage',$storage);
		if($errors && !($errors instanceof PC_Engine_ErrorContainer))
			FWS_Helper::def_error('instance','errors','PC_Engine_ErrorContainer',$errors);
		
		$this->options = $options ? $options : new PC_Engine_Options();
		$this->types = $types ? $types : new PC_Engine_TypeContainer($this->options);
		$this->storage = $storage ? $storage : new PC_Engine_TypeStorage_Null();
		$this->errors = $errors ? $errors : new PC_Engine_ErrorContainer();
	}
	
	/**
	 * @return PC_Engine_ErrorContainer the error container
	 */
	public function get_errors()
	{
		return $this->errors;
	}
	
	/**
	 * @return PC_Engine_TypeContainer the type container
	 */
	public function get_types()
	{
		return $this->types;
	}
	
	/**
	 * @return PC_Engine_Options the options
	 */
	public function get_options()
	{
		return $this->options;
	}
	
	/**
	 * @return PC_Engine_TypeStorage the type storage
	 */
	public function get_storage()
	{
		return $this->storage;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
