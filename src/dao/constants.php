<?php
/**
 * Contains the constants-dao-class
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
 * The DAO-class for the constants-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Constants extends FWS_Singleton
{
	const MAX_VALUE_LEN			= 2048;
	
	/**
	 * @return PC_DAO_Constants the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of constants for the given project
	 *
	 * @param int $class the class-id (0 = freestanding)
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($class = 0,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CONSTANTS.'
			 WHERE project_id = :pid AND class = :class'
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':class',$class);
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the number of constants for the given search
	 *
	 * @param int $class the class-id (0 = freestanding)
	 * @param string $file the file to search for
	 * @param string $name the name to search for
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for($class = 0,$file = '',$name = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CONSTANTS.'
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
		if($row)
			return $row['num'];
		return 0;
	}
	
	/**
	 * Returns the (free) constant with given name in the given project
	 *
	 * @param string $name the constant-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Obj_Constant the constant or null
	 */
	public function get_by_name($name,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CONSTANTS.'
			 WHERE project_id = :pid AND class = 0 AND name = :name'
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':name',$name);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_const($row);
		return null;
	}
	
	/**
	 * Fetches the constant with given id from db
	 * 
	 * @param int $id the constant-id
	 * @return PC_Obj_Constant the constant or null
	 */
	public function get_by_id($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CONSTANTS.' WHERE id = :id'
		);
		$stmt->bind(':id',$id);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_const($row);
		return null;
	}
	
	/**
	 * Returns all constants
	 *
	 * @param array $cids the class ids (0 = freestanding)
	 * @param string $file the file to search for
	 * @param string $name the name to search for
	 * @param int $pid the project-id (0 = current)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @return array all found constants
	 */
	public function get_list($cids,$file = '',$name = '',$pid = PC_Project::CURRENT_ID,
		$start = 0,$count = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Array_Utils::is_integer($cids))
			FWS_Helper::def_error('intarray','cids',$cids);
		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		if(count($cids) == 0)
			return array();
		
		$consts = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CONSTANTS.'
			 WHERE project_id = :pid AND class IN (:cids)'
			 .($file ? ' AND file LIKE :file' : '')
			 .($name ? ' AND name LIKE :name' : '')
			.($count > 0 ? ' LIMIT :start,:count' : '')
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
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$consts[] = $this->build_const($row);
		return $consts;
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Obj_Constant $constant the constant to create
	 * @param int $class the class-id (0 = freestanding)
	 * @param int $pid the project-id (-1 = current)
	 * @return int the used id
	 */
	public function create($constant,$class = 0,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($constant instanceof PC_Obj_Constant))
			FWS_Helper::def_error('instance','constant','PC_Obj_Constant',$constant);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$val = serialize($constant->get_type());
		if(strlen($val) > self::MAX_VALUE_LEN)
			$val = serialize(null);
		return $db->insert(PC_TB_CONSTANTS,array(
			'project_id' => PC_Utils::get_project_id($pid),
			'class' => $class,
			'file' => $constant->get_file(),
			'line' => $constant->get_line(),
			'name' => $constant->get_name(),
			'type' => $val
		));
	}
	
	/**
	 * Deletes all contants from the project with given id
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
			'DELETE FROM '.PC_TB_CONSTANTS.' WHERE project_id = :id'
		);
		$stmt->bind(':id',$id);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds a PC_Obj_Constant from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Constant the constant
	 */
	private function build_const($row)
	{
		$type = unserialize($row['type']);
		if($type === null)
			$type = new PC_Obj_MultiType();
		$const = new PC_Obj_Constant(
			$row['file'],$row['line'],$row['name'],$type,$row['class']
		);
		$const->set_id($row['id']);
		return $const;
	}
}
