<?php
/**
 * Contains the projects-dao-class
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
 * The DAO-class for the projects-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Projects extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Projects the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * @return array an array of all projects
	 */
	public function get_all()
	{
		$db = FWS_Props::get()->db();
		$res = array();
		$rows = $db->get_rows('SELECT * FROM '.PC_TB_PROJECTS);
		foreach($rows as $row)
			$res[] = $this->build_project($row,false);
		return $res;
	}
	
	/**
	 * Returns the project with given id
	 *
	 * @param int $id the id
	 * @return PC_Project the project
	 */
	public function get_by_id($id)
	{
		$res = $this->get_by_ids(array($id));
		return $res[0];
	}
	
	/**
	 * Returns the projects with given ids
	 *
	 * @param array $ids the ids
	 * @return array the projects
	 */
	public function get_by_ids($ids)
	{
		if(!FWS_Array_Utils::is_numeric($ids) || count($ids) == 0)
			FWS_Helper::def_error('numarray>0','ids',$ids);
		
		$db = FWS_Props::get()->db();
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_PROJECTS.' WHERE id IN (:ids)'
		);
		$stmt->bind(':ids',$ids);
		$rows = $db->get_rows($stmt->get_statement());
		$res = array();
		foreach($rows as $row)
			$res[] = $this->build_project($row,true);
		return $res;
	}
	
	/**
	 * @return PC_Project the current project
	 */
	public function get_current()
	{
		$db = FWS_Props::get()->db();
		$row = $db->get_row('SELECT * FROM '.PC_TB_PROJECTS.' WHERE current = 1');
		return $this->build_project($row,true);
	}
	
	/**
	 * Sets the project with given id to the current one
	 *
	 * @param int $id the project-id
	 */
	public function set_current($id)
	{
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db = FWS_Props::get()->db();
		$db->update(PC_TB_PROJECTS,'',array(
			'current' => 0
		));
		$db->update(PC_TB_PROJECTS,'WHERE id = '.$id,array(
			'current' => 1
		));
	}
	
	/**
	 * Creates a new project
	 *
	 * @param PC_Project $project the project
	 * @return int the id
	 */
	public function create($project)
	{
		$db = FWS_Props::get()->db();
		
		if(!($project instanceof PC_Project))
			FWS_Helper::def_error('instance','project','PC_Project',$project);
		
		return $db->insert(PC_TB_PROJECTS,array(
			'name' => $project->get_name(),
			'created' => time()
		));
	}
	
	/**
	 * Updates the project with given id
	 *
	 * @param PC_Project $project the project
	 */
	public function update($project)
	{
		if(!($project instanceof PC_Project))
			FWS_Helper::def_error('intgt0','project','PC_Project',$project);
		
		$db = FWS_Props::get()->db();
		$db->update(PC_TB_PROJECTS,'WHERE id = '.$project->get_id(),array(
			'name' => $project->get_name(),
			'created' => $project->get_created(),
			'type_folders' => $project->get_type_folders(),
			'type_exclude' => $project->get_type_exclude(),
			'stmt_folders' => $project->get_stmt_folders(),
			'stmt_exclude' => $project->get_stmt_exclude(),
			'report_argret_strictly' => $project->get_report_argret_strictly(),
		));
		
		$this->update_deps($project->get_id(),$project->get_project_deps());
		
		foreach($project->get_req() as $r)
		{
			$db->update(PC_TB_REQUIREMENTS,'WHERE id = '.$r['id'],array(
				'type' => $r['type'],
				'name' => $r['name'],
				'version' => $r['version'],
			));
		}
	}
	
	/**
	 * Deletes the projects with given ids
	 *
	 * @param array $ids the ids
	 * @return int the number of affected rows
	 */
	public function delete($ids)
	{
		if(!FWS_Array_Utils::is_numeric($ids) || count($ids) == 0)
			FWS_Helper::def_error('numarray>0','ids',$ids);
		
		$db = FWS_Props::get()->db();
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_PROJECT_DEPS.'
			 WHERE project_id IN (:ids) OR dep_id IN (:ids)'
		);
		$stmt->bind(':ids',$ids);
		$db->execute($stmt->get_statement());
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_PROJECTS.' WHERE id IN (:ids)'
		);
		$stmt->bind(':ids',$ids);
		$db->execute($stmt->get_statement());
		return $db->get_affected_rows();
	}
	
	/**
   * Sets the given dependencies for the given project.
   *
   * @param int $id the project id
   * @param array $deps an array with all dependencies
   */
	public function update_deps($id,$deps)
	{
		$db = FWS_Props::get()->db();
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_PROJECT_DEPS.' WHERE project_id = :id'
		);
		$stmt->bind(':id',$id);
		$db->execute($stmt->get_statement());
		
		foreach($deps as $did)
		{
			if($did == PC_Project::PHPREF_ID)
				continue;
			
			$db->insert(PC_TB_PROJECT_DEPS,array(
				'project_id' => $id,
				'dep_id' => $did,
			));
		}
	}
	
	/**
	 * Adds the given requirement to the given project.
	 *
	 * @param int $id the project-id
	 * @param string $type the type: min or max
	 * @param string $name the name of the component
	 * @param string $version the version number
	 */
	public function add_req($id,$type,$name,$version)
	{
		$db = FWS_Props::get()->db();
		
		$db->insert(PC_TB_REQUIREMENTS,array(
			'project_id' => $id,
			'type' => $type,
			'name' => $name,
			'version' => $version
		));
	}
	
	/**
	 * Deletes the given requirement
	 *
	 * @param int $vid the requirement id
	 */
	public function del_req($vid)
	{
		$db = FWS_Props::get()->db();
		
		$stmt = $db->get_prepared_statement(
			'DELETE FROM '.PC_TB_REQUIREMENTS.' WHERE id = :id'
		);
		$stmt->bind(':id',$vid);
		$db->execute($stmt->get_statement());
	}
	
	/**
	 * Builds an instance of PC_Project from the given row
	 *
	 * @param array $row the row from db
	 * @param boolean $full whether to fetch data from other tables
	 * @return PC_Project the project
	 */
	private function build_project($row,$full)
	{
		if(!$row)
			return null;
		
		$db = FWS_Props::get()->db();
		
		$proj = new PC_Project(
			$row['id'],$row['name'],$row['created'],$row['type_folders'],$row['type_exclude'],
			$row['stmt_folders'],$row['stmt_exclude'],$row['report_argret_strictly']
		);
		
		if($full)
		{
			$stmt = $db->get_prepared_statement(
				'SELECT * FROM '.PC_TB_PROJECT_DEPS.' WHERE project_id = :id'
			);
			$stmt->bind(':id',$row['id']);
			$deps = $db->get_rows($stmt->get_statement());
			foreach($deps as $d)
				$proj->add_project_dep($d['dep_id']);
			
			$stmt = $db->get_prepared_statement(
				'SELECT * FROM '.PC_TB_REQUIREMENTS.' WHERE project_id = :id'
			);
			$stmt->bind(':id',$row['id']);
			$req = $db->get_rows($stmt->get_statement());
			foreach($req as $v)
				$proj->add_req($v['id'],$v['type'],$v['name'],$v['version']);
		}
		return $proj;
	}
}
