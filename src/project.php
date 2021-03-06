<?php
/**
 * Contains the project-class
 * 
 * @package			PHPCheck
 * @subpackage	src
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
 * Represents a project
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Project extends FWS_Object
{
	/**
	 * The id of the php-reference-types
	 * 
	 * @var int
	 */
	const PHPREF_ID			= 0;
	/**
	 * The id that tells functions to use the current project-id
	 * 
	 * @var int
	 */
	const CURRENT_ID		= -1;
	
	/**
	 * The project-id
	 *
	 * @var int
	 */
	private $id;
	
	/**
	 * The project-name
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The created-date
	 *
	 * @var int
	 */
	private $created;
	
	/**
	 * A newline-separated list of folders for the type-scanner
	 *
	 * @var string
	 */
	private $type_folders;
	
	/**
	 * A newline-separated list of excluded items for the type-scanner
	 *
	 * @var string
	 */
	private $type_exclude;
	
	/**
	 * A newline-separated list of folders for the statement-scanner
	 *
	 * @var string
	 */
	private $stmt_folders;
	
	/**
	 * A newline-separated list of excluded items for the statement-scanner
	 *
	 * @var string
	 */
	private $stmt_exclude;
	
	/**
	 * Report an error if only one possible type of arguments/returns violates the spec.
	 * 
	 * @var boolean
	 */
	private $report_argret_strictly;
	
	/**
	 * The other projects it depends on.
	 *
	 * @var array
	 */
	private $proj_deps;
	
	/**
	 * The lower and upper bounds for versions.
	 *
	 * @var array
	 */
	private $req;
	
	/**
	 * Constructor
	 *
	 * @param int $id the project-id
	 * @param string $name the name
	 * @param int $created the timestamp
	 * @param string $type_folders the folders for the type-scanner
	 * @param string $type_exclude the excluded items for the type-scanner
	 * @param string $stmt_folders the folders for the statement-scanner
	 * @param string $stmt_exclude the excluded items for the statement-scanner
	 * @param boolean $report_argret_strictly report errors strictly?
	 */
	public function __construct($id,$name,$created,$type_folders,$type_exclude,$stmt_folders,
		$stmt_exclude,$report_argret_strictly)
	{
		parent::__construct();
		
		if(!FWS_Helper::is_integer($id) || $id < 0)
			FWS_Helper::def_error('intgt0','id',$id);
		if(!FWS_Helper::is_integer($created) || $created < 0)
			FWS_Helper::def_error('intge0','created',$created);
		
		$this->id = $id;
		$this->name = $name;
		$this->created = $created;
		$this->type_folders = $type_folders;
		$this->type_exclude = $type_exclude;
		$this->stmt_folders = $stmt_folders;
		$this->stmt_exclude = $stmt_exclude;
		$this->report_argret_strictly = $report_argret_strictly;
		$this->req = array();
		$this->set_project_deps(array());
	}
	
	/**
	 * @return int the project-id
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	/**
	 * @return string the project-name
	 */
	public function get_name()
	{
		return $this->name;
	}
	
	/**
	 * Sets the project-name
	 *
	 * @param string $name the new name
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return int the created-timestamp
	 */
	public function get_created()
	{
		return $this->created;
	}
	
	/**
	 * Sets the created timestamp.
	 *
	 * @param int $time the timestamp
	 */
	public function set_created($time)
	{
		$this->created = $time;
	}
	
	/**
	 * @return string a newline-separated list of folders for the type-scanner
	 */
	public function get_type_folders()
	{
		return $this->type_folders;
	}
	
	/**
	 * Sets the type-folders
	 *
	 * @param string $str the new value
	 */
	public function set_type_folders($str)
	{
		$this->type_folders = $str;
	}
	
	/**
	 * @return string a newline-separated list of exclude-items for the type-scanner
	 */
	public function get_type_exclude()
	{
		return $this->type_exclude;
	}
	
	/**
	 * Sets the type-exclude
	 *
	 * @param string $str the new value
	 */
	public function set_type_exclude($str)
	{
		$this->type_exclude = $str;
	}
	
	/**
	 * @return string a newline-separated list of folders for the statement-scanner
	 */
	public function get_stmt_folders()
	{
		return $this->stmt_folders;
	}
	
	/**
	 * Sets the statement-folders
	 *
	 * @param string $str the new value
	 */
	public function set_stmt_folders($str)
	{
		$this->stmt_folders = $str;
	}
	
	/**
	 * @return string a newline-separated list of exclude-items for the statement-scanner
	 */
	public function get_stmt_exclude()
	{
		return $this->stmt_exclude;
	}
	
	/**
	 * Sets the statement-exclude
	 *
	 * @param string $str the new value
	 */
	public function set_stmt_exclude($str)
	{
		$this->stmt_exclude = $str;
	}
	
	/**
	 * @return boolean whether to report an error if only one possible type violates the spec.
	 */
	public function get_report_argret_strictly()
	{
		return $this->report_argret_strictly;
	}
	
	/**
	 * Sets whether to report an error if only one possible type violates the spec.
	 *
	 * @param boolean $b the new value
	 */
	public function set_report_argret_strictly($b)
	{
		$this->report_argret_strictly = $b;
	}
	
	/**
	 * @return array an array with the project ids this project depends on
	 */
	public function get_project_deps()
	{
		return $this->proj_deps;
	}
	
	/**
	 * Sets the given projects as dependencies.
	 *
	 * @param array $deps the project ids
	 */
	public function set_project_deps($deps)
	{
		// add the PHP builtins as implicit dependencies (and ensure that they come first)
		$deps = array_merge(array(self::PHPREF_ID),$deps);
		$this->proj_deps = $deps;
	}
	
	/**
	 * Adds the given project id as a dependency to this one.
	 *
	 * @param int $id the id
	 */
	public function add_project_dep($id)
	{
		assert(!in_array($id,$this->proj_deps));
		$this->proj_deps[] = $id;
	}
	
	/**
	 * @return array the requirements
	 */
	public function get_req()
	{
		return $this->req;
	}
	
	/**
	 * Sets the requirements.
	 *
	 * @param array $req the new requirements
	 */
	public function set_req($req)
	{
		$this->req = $req;
	}
	
	/**
	 * Adds the given requirement to the list
	 *
	 * @param int $id the id
	 * @param string $type the type: min or max
	 * @param string $name the name of the component
	 * @param string $version the version number
	 */
	public function add_req($id,$type,$name,$version)
	{
		$this->req[] = array(
			'id' => $id,
			'type' => $type,
			'name' => $name,
			'version' => $version
		);
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
