<?php
/**
 * Contains the class-fields-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the class-fields-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_ClassFields extends FWS_Singleton
{
	/**
	 * @return PC_DAO_ClassFields the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Creates a new entry for given field
	 *
	 * @param PC_Field $field the field to create
	 * @param int $class the class-id
	 * @return int the used id
	 */
	public function create($field,$class)
	{
		$db = FWS_Props::get()->db();

		if(!($field instanceof PC_Field))
			FWS_Helper::def_error('instance','field','PC_Field',$field);
		if(!FWS_Helper::is_integer($class) || $class <= 0)
			FWS_Helper::def_error('intgt0','class',$class);
		
		$project = FWS_Props::get()->project();
		$db->sql_insert(PC_TB_CLASS_FIELDS,array(
			'project_id' => $project->get_id(),
			'class' => $class,
			'line' => $field->get_line(),
			'name' => addslashes($field->get_name()),
			'type' => $field->get_type()->get_type(),
			'value' => addslashes($field->get_type()->get_value()),
			'visibility' => $field->get_visibility(),
			'static' => $field->is_static() ? 1 : 0
		));
		return $db->get_last_insert_id();
	}
	
	/**
	 * Deletes all class-fields from the project with given id
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
			'DELETE FROM '.PC_TB_CLASS_FIELDS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
}
?>