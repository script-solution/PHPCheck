<?php
/**
 * Contains the calls-dao-class
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
 * The DAO-class for the calls-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Calls extends FWS_Singleton
{
	const MAX_ARGS_LEN			= 8192;
	
	/**
	 * @return PC_DAO_Calls the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Returns the number of calls for the given project
	 *
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		return $this->get_count_for('','','',$pid);
	}
	
	/**
	 * Returns the number of items for the given file
	 *
	 * @param string $file the file
	 * @param string $class the class-name
	 * @param string $function the function-name
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for($file = '',$class = '',$function = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CALLS.' WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND class LIKE :class' : '')
				.($function ? ' AND function LIKE :func' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		if($function)
			$stmt->bind(':func','%'.$function.'%');
		$set = $db->execute($stmt->get_statement());
		$row = $set->current();
		return $row['num'];
	}
	
	/**
	 * Fetches the call with given id from db
	 * 
	 * @param int $id the call-id
	 * @return PC_Obj_Call the call or null
	 */
	public function get_by_id($id)
	{
		$db = FWS_Props::get()->db();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CALLS.' WHERE id = :id'
		);
		$stmt->bind(':id',$id);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->build_call($row);
		return null;
	}
	
	/**
	 * Returns all calls
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param int $pid the project-id (default = current)
	 * @param string $file the file
	 * @param string $class the class-name
	 * @param string $function the function-name
	 * @return array all found calls
	 */
	public function get_list($start = 0,$count = 0,$file = '',$class = '',$function = '',
		$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$calls = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CALLS.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND class LIKE :class' : '')
				.($function ? ' AND function LIKE :func' : '')
			 .' ORDER BY id ASC
			'.($count > 0 ? 'LIMIT :start,:count' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		if($function)
			$stmt->bind(':func','%'.$function.'%');
		if($count > 0)
		{
			$stmt->bind(':start',$start);
			$stmt->bind(':count',$count);
		}
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$calls[] = $this->build_call($row);
		return $calls;
	}
	
	/**
	 * Creates a new entry for given call
	 *
	 * @param PC_Obj_Call $call the call
	 * @return int the used id
	 */
	public function create($call)
	{
		$db = FWS_Props::get()->db();

		if(!($call instanceof PC_Obj_Call))
			FWS_Helper::def_error('instance','call','PC_Obj_Call',$call);
		
		$project = FWS_Props::get()->project();
		return $db->insert(PC_TB_CALLS,$this->build_fields($call,$project));
	}
	
	/**
	 * Creates the given calls
	 * 
	 * @param array $calls an array of PC_Obj_Call
	 */
	public function create_bulk($calls)
	{
		$db = FWS_Props::get()->db();
		
		$project = FWS_Props::get()->project();
		$rows = array();
		foreach($calls as $call)
			$rows[] = $this->build_fields($call,$project);
		$db->insert_bulk(PC_TB_CALLS,$rows);
	}
	
	/**
	 * Deletes all calls from the project with given id
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
			'DELETE FROM '.PC_TB_CALLS.' WHERE project_id = :id'
		);
		$stmt->bind(':id',$id);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Obj_Call from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Obj_Call the call
	 * @throws Exception if the arguments can't be serialized
	 */
	private function build_call($row)
	{
		$c = new PC_Obj_Call($row['file'],$row['line']);
		$c->set_id($row['id']);
		$c->set_class($row['class']);
		$c->set_function($row['function']);
		$c->set_static($row['static']);
		$c->set_object_creation($row['objcreation']);
		$args = $row['arguments'] ? unserialize($row['arguments']) : array();
		if($args === false)
			throw new Exception("Unable to unserialize '".$row['arguments']."'");
		foreach($args as $arg)
			$c->add_argument($arg);
		return $c;
	}
	
	/**
	 * Builds the fields to insert for the given call and project
	 * 
	 * @param PC_Obj_Call $call the call
	 * @param PC_Project $project the project
	 * @return array an associative array with the fields
	 */
	private function build_fields($call,$project)
	{
		$args = serialize($call->get_arguments());
		if(strlen($args) > self::MAX_ARGS_LEN)
		{
			$arglist = array();
			foreach($call->get_arguments() as $arg)
			{
				$arg->clear_values();
				$arglist[] = $arg;
			}
			$args = serialize($arglist);
		}
		return array(
			'project_id' => $project !== null ? $project->get_id() : 0,
			'file' => $call->get_file(),
			'line' => $call->get_line(),
			'function' => $call->get_function(),
			'class' => $call->get_class() === null ? null : $call->get_class(),
			'static' => $call->is_static() ? 1 : 0,
			'objcreation' => $call->is_object_creation() ? 1 : 0,
			'arguments' => $args
		);
	}
}
