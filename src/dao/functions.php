<?php
/**
 * Contains the functions-dao-class
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
 * The DAO-class for the functions-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Functions extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Functions the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of functions for the given project
	 *
	 * @param int $class the class-id (0 = free functions)
	 * @param int $pid the project-id (default = current)
	 * @param string $file the file-name to search for
	 * @param string $name the function-name to search for
	 * @return int the number
	 */
	public function get_count($class = 0,$pid = PC_Project::CURRENT_ID,$file = '',$name = '')
	{
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = :pid AND class = :class'
			 .($file ? ' AND file LIKE :file' : '')
			 .($name ? ' AND name LIKE :name' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':class',$class);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the function/method with given name and optionally in given class and project
	 *
	 * @param string $name the function-name
	 * @param int $pid the project-id (default = current)
	 * @param string $class the class in which the method is (default: empty, i.e. a free function)
	 * @return PC_Obj_Method the function or null
	 */
	public function get_by_name($name,$pid = PC_Project::CURRENT_ID,$class = '')
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$stmt = $db->get_prepared_statement(
			'SELECT f.* FROM '.PC_TB_FUNCTIONS.' f
			 LEFT JOIN '.PC_TB_CLASSES.' c ON f.class = c.id AND f.project_id = c.project_id
			 WHERE
			 	f.project_id = :pid AND
			 	((:class = "" AND f.class = 0) OR (:class != "" AND c.name = :class)) AND
			 	f.name = :funcname'
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':class',$class ? $class : '');
		$stmt->bind(':funcname',$name);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_func($row);
		return null;
	}
	
	/**
	 * Fetches the function with given id from db
	 * 
	 * @param int $id the function-id
	 * @return PC_Obj_Method the function or null
	 */
	public function get_by_id($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_FUNCTIONS.' WHERE id = :id'
		);
		$stmt->bind(':id',$id);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_func($row);
		return null;
	}
	
	/**
	 * Returns all functions
	 *
	 * @param array $cids the class ids (0 = free functions)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $file the file-name to search for
	 * @param string $name the function-name to search for
	 * @param int $pid the project-id (current by default)
	 * @return array all found functions
	 */
	public function get_list($cids,$start = 0,$count = 0,$file = '',$name = '',
		$pid = PC_PRoject::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Array_Utils::is_integer($cids))
			FWS_Helper::def_error('intarray','cids',$cids);
		
		if(count($cids) == 0)
			return array();
		
		$funcs = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = :pid AND class IN (:cids)
			 '.($file ? ' AND file LIKE :file' : '').'
			 '.($name ? ' AND name LIKE :name' : '').'
			 ORDER BY name
			 '.($count > 0 ? ' LIMIT :start,:count' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':cids',$cids);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		if($count > 0)
		{
			$stmt->bind(':start',$start);
			$stmt->bind(':count',$count);
		}
		$rows = $db->get_rows($stmt->get_statement());
		foreach($rows as $row)
			$funcs[] = $this->build_func($row);
		return $funcs;
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Obj_Method $function the function to create
	 * @param int $class the id of the class the function belongs to
	 * @param int $pid the project-id (-1 = current)
	 * @return int the used id
	 */
	public function create($function,$class = 0,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($function instanceof PC_Obj_Method))
			FWS_Helper::def_error('instance','function','PC_Obj_Method',$function);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		return $db->insert(PC_TB_FUNCTIONS,$this->get_fields($function,$class,$pid));
	}
	
	/**
	 * Updates the properties of the given function
	 *
	 * @param PC_Obj_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @return int the number of affected rows
	 */
	public function update($function,$class = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(!($function instanceof PC_Obj_Method))
			FWS_Helper::def_error('instance','function','PC_Obj_Method',$function);
		
		return $db->update(
			PC_TB_FUNCTIONS,' WHERE id = '.$function->get_id(),$this->get_fields($function,$class)
		);
	}
	
	/**
	 * Deletes all functions from the project with given id
	 *
	 * @param int $id the project-id
	 * @return int the number of affected rows
	 */
	public function delete_by_project($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_FUNCTIONS.' WHERE project_id = :id'
		);
		$stmt->bind(':id',$id);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds the fields to insert / update in the db
	 *
	 * @param PC_Obj_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @param int $pid the project-id (default = current)
	 * @return array all fields
	 */
	private function get_fields($function,$class,$pid = PC_Project::CURRENT_ID)
	{
		$params = serialize($function->get_params());
		return array(
			'project_id' => PC_UTils::get_project_id($pid),
			'file' => $function->get_file(),
			'line' => $function->get_line(),
			'class' => $class,
			'name' => $function->get_name(),
			'abstract' => $function->is_abstract() ? 1 : 0,
			'final' => $function->is_final() ? 1 : 0,
			'static' => $function->is_static() ? 1 : 0,
			'anonymous' => $function->is_anonymous() ? 1 : 0,
			'visibility' => $function->get_visibility(),
			'return_type' => serialize(array($function->has_return_doc(),$function->get_return_type())),
			'throws' => serialize($function->get_throws()),
			'params' => $params,
			'min_version' => serialize($function->get_version()->get_min()),
			'max_version' => serialize($function->get_version()->get_max())
		);
	}
	
	/**
	 * Builds a PC_Obj_Method from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Method the method
	 */
	private function build_func($row)
	{
		$c = new PC_Obj_Method($row['file'],$row['line'],$row['class'] == 0,$row['id'],$row['class']);
		$c->set_name($row['name']);
		$c->set_visibility($row['visibility']);
		$c->set_abstract($row['abstract']);
		$c->set_static($row['static']);
		$c->set_anonymous($row['anonymous']);
		$c->set_final($row['final']);
		$c->get_version()->set(unserialize($row['min_version']),unserialize($row['max_version']));
		foreach(unserialize($row['params']) as $param)
			$c->put_param($param);
		list($hasretdoc,$rettype) = unserialize($row['return_type']);
		$throws = unserialize($row['throws']);
		if(is_array($throws))
		{
			foreach($throws as $class => $type)
				$c->add_throw($class,$type);
		}
		$c->set_has_return_doc($hasretdoc);
		$c->set_return_type($rettype);
		return $c;
	}
}
