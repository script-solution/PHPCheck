<?php
/**
 * Contains the class-fields-dao-class
 *
 * @version			$Id$
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
	 * Returns all fields of the given class
	 *
	 * @param int $class the class-id
	 * @param int $pid the project-id (0 = current)
	 * @return array an array of PC_Field objects
	 */
	public function get_all($class,$pid = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($class) || $class <= 0)
			FWS_Helper::def_error('intgt0','class',$class);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$fields = array();
		$rows = $db->get_rows(
			'SELECT * FROM '.PC_TB_CLASS_FIELDS.'
			 WHERE project_id = '.$pid.' AND class = '.$class
		);
		foreach($rows as $row)
		{
			if($row['type'] == PC_Type::OBJECT)
				$type = new PC_Type($row['type'],null,$row['value']);
			else
				$type = new PC_Type($row['type'],$row['value']);
			$fields[] = new PC_Field($row['file'],$row['line'],$row['name'],$type,$row['visibility']);
		}
		return $fields;
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
		$otype = $field->get_type();
		$type = $otype->get_type();
		$val = $type == PC_Type::OBJECT ? $otype->get_class() : $otype->get_value();
		return $db->insert(PC_TB_CLASS_FIELDS,array(
			'project_id' => $project->get_id(),
			'class' => $class,
			'file' => $field->get_file(),
			'line' => $field->get_line(),
			'name' => $field->get_name(),
			'type' => $type,
			'value' => $val,
			'visibility' => $field->get_visibility(),
			'static' => $field->is_static() ? 1 : 0
		));
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
		
		$db->execute(
			'DELETE FROM '.PC_TB_CLASS_FIELDS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
}
?>