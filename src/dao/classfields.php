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
	const MAX_VALUE_LEN			= 2048;
	
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
	 * @param int $pid the project-id (default = current)
	 * @return array an array of PC_Obj_Field objects
	 */
	public function get_all($class,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($class) || $class <= 0)
			FWS_Helper::def_error('intgt0','class',$class);
		
		$fields = array();
		$rows = $db->get_rows(
			'SELECT * FROM '.PC_TB_CLASS_FIELDS.'
			 WHERE project_id = '.PC_Utils::get_project_id($pid).' AND class = '.$class
		);
		foreach($rows as $row)
		{
			if($row['type'] == PC_Obj_Type::OBJECT)
				$type = new PC_Obj_Type($row['type'],null,$row['value']);
			else
				$type = new PC_Obj_Type($row['type'],unserialize($row['value']));
			$fields[] = new PC_Obj_Field($row['file'],$row['line'],$row['name'],$type,$row['visibility']);
		}
		return $fields;
	}
	
	/**
	 * Creates a new entry for given field
	 *
	 * @param PC_Obj_Field $field the field to create
	 * @param int $class the class-id
	 * @param int $pid the project-id (default = current)
	 * @return int the used id
	 */
	public function create($field,$class,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($field instanceof PC_Obj_Field))
			FWS_Helper::def_error('instance','field','PC_Obj_Field',$field);
		if(!FWS_Helper::is_integer($class) || $class <= 0)
			FWS_Helper::def_error('intgt0','class',$class);
		
		$otype = $field->get_type();
		$type = $otype->get_type();
		$val = $type == PC_Obj_Type::OBJECT ? $otype->get_class() : serialize($otype->get_value());
		if(strlen($val) > self::MAX_VALUE_LEN)
			$val = serialize(null);
		return $db->insert(PC_TB_CLASS_FIELDS,array(
			'project_id' => PC_Utils::get_project_id($pid),
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
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_CLASS_FIELDS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
}
?>