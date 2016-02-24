<?php
/**
 * Contains the vars-dao-class
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
 * The DAO-class for the vars-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Vars extends FWS_Singleton
{
	const MAX_VALUE_LEN			= 2048;
	
	/**
	 * @return PC_DAO_Vars the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of vars for the given project
	 *
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		return $this->get_count_for('','',$pid);
	}
	
	/**
	 * Returns the number of items for the given scope and variable name
	 *
	 * @param string $scope the scope
	 * @param string $name the variable-name
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for($scope = '',$name = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_VARS.' WHERE project_id = :pid'
				.($scope ? ' AND scope LIKE :scope' : '')
				.($name ? ' AND name LIKE :name' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($scope)
			$stmt->bind(':scope','%'.$scope.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		$set = $db->execute($stmt->get_statement());
		$row = $set->current();
		return $row['num'];
	}
	
	/**
	 * Fetches the variable with given id from db
	 * 
	 * @param int $id the variable-id
	 * @return PC_Obj_Variable the variable or null
	 */
	public function get_by_id($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_VARS.' WHERE id = :id'
		);
		$stmt->bind(':id',$id);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->_build_var($row);
		return null;
	}
	
	/**
	 * Returns all vars
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $scope the scope
	 * @param string $name the variable-name
	 * @param int $pid the project-id (default = current)
	 * @return array all found vars
	 */
	public function get_list($start = 0,$count = 0,$scope = '',$name = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$vars = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_VARS.'
			 WHERE project_id = :pid'
				.($scope ? ' AND scope LIKE :scope' : '')
				.($name ? ' AND name LIKE :name' : '')
			 .' ORDER BY id ASC
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($scope)
			$stmt->bind(':scope','%'.$scope.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$vars[] = $this->_build_var($row);
		return $vars;
	}
	
	/**
	 * Creates a new entry for given var
	 *
	 * @param PC_Obj_Variable $var the variable
	 * @return int the used id
	 */
	public function create($var)
	{
		$db = FWS_Props::get()->db();

		if(!($var instanceof PC_Obj_Variable))
			FWS_Helper::def_error('instance','var','PC_Obj_Variable',$var);
		
		$project = FWS_Props::get()->project();
		$type = serialize($var->get_type());
		if(strlen($type) > self::MAX_VALUE_LEN)
		{
			$clone = clone $var->get_type();
			$clone->clear_values();
			$type = serialize($clone);
		}
		return $db->insert(PC_TB_VARS,array(
			'project_id' => PC_Utils::get_project_id(PC_Project::CURRENT_ID),
			'file' => $var->get_file(),
			'line' => $var->get_line(),
			'name' => $var->get_name(),
			'scope' => $var->get_scope(),
			'type' => $type
		));
	}
	
	/**
	 * Deletes all vars from the project with given id
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
			'DELETE FROM '.PC_TB_VARS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Obj_Variable from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Obj_Variable the var
	 */
	private function _build_var($row)
	{
		$var = PC_Obj_Variable::create_from_scope(
			$row['file'],$row['line'],$row['name'],unserialize($row['type']),$row['scope']
		);
		$var->set_id($row['id']);
		return $var;
	}
}
