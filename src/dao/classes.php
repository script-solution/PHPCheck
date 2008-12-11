<?php
/**
 * Contains the classes-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
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
	 * Creates a new entry for given class
	 *
	 * @param PC_Class $class the class
	 * @return int the used id
	 */
	public function create($class)
	{
		$db = FWS_Props::get()->db();

		if(!($class instanceof PC_Class))
			FWS_Helper::def_error('instance','class','PC_Class',$class);
		
		$project = FWS_Props::get()->project();
		$db->sql_insert(PC_TB_CLASSES,array(
			'project_id' => $project->get_id(),
			'file' => addslashes($class->get_file()),
			'line' => $class->get_line(),
			'name' => addslashes($class->get_name()),
			'abstract' => $class->is_abstract() ? 1 : 0,
			'final' => $class->is_final() ? 1 : 0,
			'interface' => $class->is_interface() ? 1 : 0,
			'superclass' => $class->get_super_class() === null ? '' : $class->get_super_class(),
			'interfaces' => addslashes(implode(',',$class->get_interfaces()))
		));
		$cid = $db->get_last_insert_id();
		
		// create fields
		foreach($class->get_fields() as $field)
			PC_DAO::get_classfields()->create($field,$cid);
		
		// create methods
		foreach($class->get_methods() as $method)
			PC_DAO::get_functions()->create($method,$cid);
		
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
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		
		$db->sql_qry(
			'DELETE FROM '.PC_TB_CLASSES.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Returns all entries of the acp-access-table. Additionally you get the corresponding
	 * username (NULL if it is a group-entry)
	 *
	 * @return array all rows
	 */
	public function get_all()
	{
		$db = FWS_Props::get()->db();

		return $db->sql_rows(
			'SELECT a.*,u.`'.BS_EXPORT_USER_NAME.'` user_name
			 FROM '.BS_TB_ACP_ACCESS.' a
			 LEFT JOIN '.BS_TB_USER.' u ON u.`'.BS_EXPORT_USER_ID.'` = a.access_value'
		);
	}
	
	/**
	 * Returns all entries of the acp-access-table that belong to the given module.
	 * Additionally you get the corresponding username (NULL if it is a group-entry)
	 *
	 * @return array all found rows
	 */
	public function get_by_module($module)
	{
		$db = FWS_Props::get()->db();

		return $db->sql_rows(
			'SELECT a.*,u.`'.BS_EXPORT_USER_NAME.'` user_name
			 FROM '.BS_TB_ACP_ACCESS.' a
			 LEFT JOIN '.BS_TB_USER.' u ON u.`'.BS_EXPORT_USER_ID.'` = a.access_value
			 WHERE a.module = "'.$module.'"'
		);
	}
}
?>