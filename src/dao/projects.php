<?php
/**
 * Contains the projects-dao-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
			$res[] = $this->_build_project($row);
		return $res;
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
		$res = array();
		$rows = $db->get_rows('SELECT * FROM '.PC_TB_PROJECTS.' WHERE id IN ('.implode(',',$ids).')');
		foreach($rows as $row)
			$res[] = $this->_build_project($row);
		return $res;
	}
	
	/**
	 * @return PC_Project the current project
	 */
	public function get_current()
	{
		$db = FWS_Props::get()->db();
		$row = $db->get_row('SELECT * FROM '.PC_TB_PROJECTS.' WHERE current = 1');
		return $this->_build_project($row);
	}
	
	/**
	 * Sets the project with given id to the current one
	 *
	 * @param int $id the project-id
	 */
	public function set_current($id)
	{
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
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
			'type_folders' => $project->get_type_folders(),
			'type_exclude' => $project->get_type_exclude(),
			'stmt_folders' => $project->get_stmt_folders(),
			'stmt_exclude' => $project->get_stmt_exclude(),
			'report_mixed' => $project->get_report_mixed(),
			'report_unknown' => $project->get_report_unknown()
		));
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
		$db->execute('DELETE FROM '.PC_TB_PROJECTS.' WHERE id IN ('.implode(',',$ids).')');
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Project from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Project the project
	 */
	private function _build_project($row)
	{
		if(!$row)
			return null;
		return new PC_Project(
			$row['id'],$row['name'],$row['created'],$row['type_folders'],$row['type_exclude'],
			$row['stmt_folders'],$row['stmt_exclude'],$row['report_mixed'],$row['report_unknown']
		);
	}
}
?>