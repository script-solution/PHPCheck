<?php
/**
 * Contains the errors-dao-class
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
 * The DAO-class for the errors-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Errors extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Errors the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of errors for the given project
	 *
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		return $this->get_count_with('','',array(),$pid);
	}
	
	/**
	 * Returns the number of errors that contain the given file and message
	 *
	 * @param string $file the file
	 * @param string $msg the message
	 * @param array $types an array of types (numeric!)
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_with($file = '',$msg = '',$types = array(),$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_ERRORS.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($msg ? ' AND message LIKE :msg' : '')
				.(count($types) ? ' AND type IN (:types)' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($msg)
			$stmt->bind(':msg','%'.$msg.'%');
		if(count($types))
			$stmt->bind(':types',$types);
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Fetches the error with given id from db
	 * 
	 * @param int $id the error-id
	 * @return PC_Obj_Error the error or null
	 */
	public function get_by_id($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_ERRORS.' WHERE id = :id'
		);
		$stmt->bind(':id',$id);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_error($row);
		return null;
	}
	
	/**
	 * Returns all errors. Optionally you can filter the search by file and message
	 *
	 * @param int $pid the project-id (default = current)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $file the file
	 * @param string $msg the message
	 * @param array $types an array of types (numeric!)
	 * @return array all found errors
	 */
	public function get_list($pid = PC_Project::CURRENT_ID,$start = 0,$count = 0,$file = '',
		$msg = '',$types = array())
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Array_Utils::is_numeric($types))
			FWS_Helper::def_error('numarray','types',$types);
		
		$errs = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_ERRORS.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($msg ? ' AND message LIKE :msg' : '')
				.(count($types) ? ' AND type IN (:types)' : '')
				.' ORDER BY file ASC, line ASC'
				.($count > 0 ? ' LIMIT :start,:count' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($msg)
			$stmt->bind(':msg','%'.$msg.'%');
		if(count($types))
			$stmt->bind(':types',$types);
		if($count > 0)
		{
			$stmt->bind(':start',$start);
			$stmt->bind(':count',$count);
		}
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$errs[] = $this->build_error($row);
		return $errs;
	}
	
	/**
	 * Creates a new entry for given error
	 *
	 * @param PC_Obj_Error $error the error to create
	 * @param int $pid the project-id (default = current)
	 * @return int the used id
	 */
	public function create($error,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($error instanceof PC_Obj_Error))
			FWS_Helper::def_error('instance','error','PC_Obj_Error',$error);
		
		return $db->insert(PC_TB_ERRORS,array(
			'project_id' => PC_Utils::get_project_id($pid),
			'file' => $error->get_loc()->get_file(),
			'line' => $error->get_loc()->get_line(),
			'message' => $error->get_msg(),
			'type' => $error->get_type()
		));
	}
	
	/**
	 * Deletes all given error-types for given project
	 * 
	 * @param array $types an array of the types
	 * @param int $pid the project-id (default = current)
	 * @return int the number of affected rows
	 */
	public function delete_by_type($types,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Array_Utils::is_integer($types) || count($types) == 0)
			FWS_Helper::def_error('intarray>0','types',$types);
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_ERRORS.' WHERE project_id = :id AND type IN (:types)'
		);
		$stmt->bind(':id',PC_Utils::get_project_id($pid));
		$stmt->bind(':types',$types);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
	 * Deletes all errors from the project with given id
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
			'DELETE FROM '.PC_TB_ERRORS.' WHERE project_id = :id'
		);
		$stmt->bind(':id',$id);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds a PC_Obj_Error from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Error the error
	 */
	private function build_error($row)
	{
		$err = new PC_Obj_Error(new PC_Obj_Location($row['file'],$row['line']),$row['message'],$row['type']);
		$err->set_id($row['id']);
		return $err;
	}
}
