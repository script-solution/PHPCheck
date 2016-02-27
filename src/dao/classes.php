<?php
/**
 * Contains the classes-dao-class
 * 
 * @package			PHPCheck
 * @subpackage	src.dao
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
 * The DAO-class for the classes-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Classes extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Classes the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of classes for the given project
	 *
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		return $this->get_count_for_file('',$pid);
	}
	
	/**
	 * Returns the number of items for the given file
	 *
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for_file($file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file = :file' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file',$file);
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the number of items for the given file and class
	 *
	 * @param string $class the class-name
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for($class = '',$file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND name LIKE :class' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the classes with given file in the given project
	 *
	 * @param string $file the file-name
	 * @param int $pid the project-id (default = current)
	 * @return array all found classes
	 */
	public function get_by_file($file,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($file))
			FWS_Helper::def_error('notempty','file',$file);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = ? AND file = ?'
		);
		$stmt->bind(0,PC_Utils::get_project_id($pid));
		$stmt->bind(1,$file);
		$classes = array();
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$classes[] = $this->build_class($row,$pid);
		return $classes;
	}
	
	/**
	 * Returns the class with given name in the given project
	 *
	 * @param string $name the class-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Obj_Class the class or null
	 */
	public function get_by_name($name,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = ? AND name = ?'
		);
		$stmt->bind(0,PC_Utils::get_project_id($pid));
		$stmt->bind(1,$name);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_class($row,$pid);
		return null;
	}
	
	/**
	 * Returns all classes
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $class the class-name
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return array all found classes
	 */
	public function get_list($start = 0,$count = 0,$class = '',$file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND (name LIKE :class OR superclass LIKE :class OR interfaces LIKE :class)' : '')
			.' ORDER BY name ASC
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		$rows = $db->get_rows($stmt->get_statement());
		return $this->build_complete_classes($rows,$pid);
	}
	
	/**
	 * Creates a new entry for given class
	 *
	 * @param PC_Obj_Class $class the class
	 * @param int $pid the project-id (default = current)
	 * @return int the used id
	 */
	public function create($class,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($class instanceof PC_Obj_Class))
			FWS_Helper::def_error('instance','class','PC_Obj_Class',$class);
		
		$pid = PC_Utils::get_project_id($pid);
		$cid = $db->insert(PC_TB_CLASSES,array(
			'project_id' => $pid,
			'file' => $class->get_file(),
			'line' => $class->get_line(),
			'name' => $class->get_name(),
			'abstract' => $class->is_abstract() ? 1 : 0,
			'final' => $class->is_final() ? 1 : 0,
			'interface' => $class->is_interface() ? 1 : 0,
			'superclass' => $class->get_super_class() === null ? '' : $class->get_super_class(),
			'interfaces' => implode(',',$class->get_interfaces()),
			'min_version' => serialize($class->get_version()->get_min()),
			'max_version' => serialize($class->get_version()->get_max())
		));
		
		// create constants
		foreach($class->get_constants() as $const)
			PC_DAO::get_constants()->create($const,$cid,$pid);
		
		// create fields
		foreach($class->get_fields() as $field)
			PC_DAO::get_classfields()->create($field,$cid,$pid);
		
		// create methods
		foreach($class->get_methods() as $method)
			PC_DAO::get_functions()->create($method,$cid,$pid);
		
		return $cid;
	}
	
	/**
	 * Deletes all classes from the project with given id
	 *
	 * @param int $id the project-id
	 * @return int the number of affected rows
	 */
	public function delete_by_project($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_CLASSES.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds the classes from given rows completely, i.e. without lazy-loading
	 * 
	 * @param array $rows the rows
	 * @param int $pid the project-id
	 * @return array the class-objects
	 */
	private function build_complete_classes($rows,$pid)
	{
		$classes = array();
		foreach($rows as $row)
			$classes[$row['id']] = $this->build_class($row,$pid,false);
		$cids = array_keys($classes);
		foreach(PC_DAO::get_constants()->get_list($cids,'','',$pid) as $const)
			$classes[$const->get_class()]->add_constant($const);
		foreach(PC_DAO::get_classfields()->get_all($cids,$pid) as $field)
			$classes[$field->get_class()]->add_field($field);
		foreach(PC_DAO::get_functions()->get_list($cids,0,0,'','',$pid) as $method)
			$classes[$method->get_class()]->add_method($method);
		return $classes;
	}
	
	/**
	 * Builds an instance of PC_Obj_Class from the given row
	 *
	 * @param array $row the row from the db
	 * @param int $pid the project-id
	 * @param bool $lazy wether to load it lazy
	 * @return PC_Obj_Class the class
	 */
	private function build_class($row,$pid,$lazy = true)
	{
		$c = new PC_Obj_Class($row['file'],$row['line'],$row['id'],$pid,$lazy);
		$c->set_name($row['name']);
		$c->set_super_class($row['superclass']);
		$c->set_abstract($row['abstract']);
		$c->set_interface($row['interface']);
		$c->set_final($row['final']);
		$c->get_version()->set(unserialize($row['min_version']),unserialize($row['max_version']));
		foreach(FWS_Array_Utils::advanced_explode(',',$row['interfaces']) as $if)
			$c->add_interface($if);
		return $c;
	}
}
