<?php
/**
 * Contains the classes-dao-class
 *
 * @version			$Id$
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
	 * Returns the number of classes for the given project
	 *
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count($pid = PC_Project::CURRENT_ID)
	{
		return $this->get_count_for_file('',$pid);
	}
	
	/**
	 * Returns the number of items for the given file
	 *
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for_file($file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file = :file' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file',$file);
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the number of items for the given file and class
	 *
	 * @param string $class the class-name
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return int the number
	 */
	public function get_count_for($class = '',$file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND name LIKE :class' : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the classes with given file in the given project
	 *
	 * @param string $file the file-name
	 * @param int $pid the project-id (default = current)
	 * @return array all found classes
	 */
	public function get_by_file($file,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($file))
			FWS_Helper::def_error('notempty','file',$file);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = ? AND file = ?'
		);
		$stmt->bind(0,PC_Utils::get_project_id($pid));
		$stmt->bind(1,$file);
		$classes = array();
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$classes[] = $this->_build_class($row);
		return $classes;
	}
	
	/**
	 * Returns the class with given name in the given project
	 *
	 * @param string $name the class-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Obj_Class the class or null
	 */
	public function get_by_name($name,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = ? AND name = ?'
		);
		$stmt->bind(0,PC_Utils::get_project_id($pid));
		$stmt->bind(1,$name);
		$row = $db->get_row($stmt->get_statement());
		if($row)
			return $this->_build_class($row);
		return null;
	}
	
	/**
	 * Returns all classes
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param string $class the class-name
	 * @param string $file the file
	 * @param int $pid the project-id (default = current)
	 * @return array all found classes
	 */
	public function get_list($start = 0,$count = 0,$class = '',$file = '',$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		
		$classes = array();
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = :pid'
				.($file ? ' AND file LIKE :file' : '')
				.($class ? ' AND name LIKE :class' : '')
			.' ORDER BY name ASC
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($class)
			$stmt->bind(':class','%'.$class.'%');
		foreach($db->get_rows($stmt->get_statement()) as $row)
			$classes[] = $this->_build_class($row);
		return $classes;
	}
	
	/**
	 * Creates a new entry for given class
	 *
	 * @param PC_Obj_Class $class the class
	 * @param int $pid the project-id (default = current)
	 * @return int the used id
	 */
	public function create($class,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($class instanceof PC_Obj_Class))
			FWS_Helper::def_error('instance','class','PC_Obj_Class',$class);
		
		$pid = PC_Utils::get_project_id($pid);
		$cid = $db->insert(PC_TB_CLASSES,array(
			'project_id' => $pid,
			'file' => $class->get_file(),
			'line' => $class->get_line(),
			'name' => $class->get_name(),
			'abstract' => $class->is_abstract() ? 1 : 0,
			'final' => $class->is_final() ? 1 : 0,
			'interface' => $class->is_interface() ? 1 : 0,
			'superclass' => $class->get_super_class() === null ? '' : $class->get_super_class(),
			'interfaces' => implode(',',$class->get_interfaces())
		));
		
		// create constants
		foreach($class->get_constants() as $const)
			PC_DAO::get_constants()->create($const,$cid,$pid);
		
		// create fields
		foreach($class->get_fields() as $field)
			PC_DAO::get_classfields()->create($field,$cid,$pid);
		
		// create methods
		foreach($class->get_methods() as $method)
			PC_DAO::get_functions()->create($method,$cid,$pid);
		
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
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_CLASSES.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds an instance of PC_Obj_Class from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Obj_Class the class
	 */
	private function _build_class($row)
	{
		$c = new PC_Obj_Class($row['file'],$row['line'],$row['id']);
		$c->set_name($row['name']);
		$c->set_super_class($row['superclass']);
		$c->set_abstract($row['abstract']);
		$c->set_interface($row['interface']);
		$c->set_final($row['final']);
		foreach(FWS_Array_Utils::advanced_explode(',',$row['interfaces']) as $if)
			$c->add_interface($if);
		foreach(PC_DAO::get_constants()->get_list($row['id']) as $const)
			$c->add_constant($const);
		foreach(PC_DAO::get_classfields()->get_all($row['id']) as $field)
			$c->add_field($field);
		foreach(PC_DAO::get_functions()->get_list($row['id']) as $method)
			$c->add_method($method);
		return $c;
	}
}
?>