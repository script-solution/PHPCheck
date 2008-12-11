<?php
/**
 * Contains the functions-dao-class
 *
 * @version			$Id: acpaccess.php 54 2008-12-01 10:26:23Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The DAO-class for the functions-table. Contains all methods to manipulate the table-content and
 * retrieve rows from it.
 *
 * @package			PHPCheck
 * @subpackage	src.dao
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_DAO_Functions extends FWS_Singleton
{
	/**
	 * @return PC_DAO_Functions the instance of this class
	 */
	public static function get_instance()
	{
		return parent::_get_instance(get_class());
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Method $function the function to create
	 * @param int $class the id of the class the function belongs to
	 * @return int the used id
	 */
	public function create($function,$class = 0)
	{
		$db = FWS_Props::get()->db();

		if(!($function instanceof PC_Method))
			FWS_Helper::def_error('instance','function','PC_Method',$function);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$params = '';
		foreach($function->get_params() as $param)
		{
			$params .= $param->get_name().':';
			$types = array();
			foreach($param->get_mtype()->get_types() as $type)
				$types[] = $type->get_type() === null ? PC_Type::UNKNOWN : $type->get_type();
			$params .= implode(',',$types).';';
		}
		
		$project = FWS_Props::get()->project();
		$db->sql_insert(PC_TB_FUNCTIONS,array(
			'project_id' => $project->get_id(),
			'file' => addslashes($function->get_file()),
			'line' => $function->get_line(),
			'class' => $class,
			'name' => addslashes($function->get_name()),
			'abstract' => $function->is_abstract() ? 1 : 0,
			'final' => $function->is_final() ? 1 : 0,
			'static' => $function->is_static() ? 1 : 0,
			'visibility' => $function->get_visibility(),
			'return_type' => $function->get_return_type()->get_type(),
			'params' => $params
		));
		return $db->get_last_insert_id();
	}
	
	/**
	 * Deletes all functions from the project with given id
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
			'DELETE FROM '.PC_TB_FUNCTIONS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
}
?>