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
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($pid = 0)
	{
		return $this->get_count_for_file('',$pid);
	}
	
	/**
	 * Returns the number of items for the given file
	 *
	 * @param string $file the file
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count_for_file($file = '',$pid = 0)
	{
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		return $db->sql_num(
			PC_TB_CLASSES,'*',' WHERE project_id = '.$pid
				.($file ? ' AND file = "'.addslashes($file).'"' : '')
		);
	}
	
	/**
	 * Returns the classes with given file in the given project
	 *
	 * @param string $file the file-name
	 * @param int $pid the project-id (0 = current)
	 * @return array all found classes
	 */
	public function get_by_file($file,$pid = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($file))
			FWS_Helper::def_error('notempty','file',$file);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$rows = $db->sql_rows(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = '.$pid.' AND file = "'.addslashes($file).'"'
		);
		$classes = array();
		foreach($rows as $row)
			$classes[] = $this->_build_class($row);
		return $classes;
	}
	
	/**
	 * Returns the class with given name in the given project
	 *
	 * @param string $name the class-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Class the class or null
	 */
	public function get_by_name($name,$pid = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$row = $db->sql_fetch(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = '.$pid.' AND name = "'.addslashes($name).'"'
		);
		if($row)
			return $this->_build_class($row);
		return null;
	}
	
	/**
	 * Returns all classes
	 *
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @param int $pid the project-id (0 = current)
	 * @return array all found classes
	 */
	public function get_list($start = 0,$count = 0,$pid = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		$classes = array();
		$rows = $db->sql_rows(
			'SELECT * FROM '.PC_TB_CLASSES.'
			 WHERE project_id = '.$pid.'
			 ORDER BY id ASC
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
			$classes[] = $this->_build_class($row);
		return $classes;
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
		
		// create constants
		foreach($class->get_constants() as $const)
			PC_DAO::get_constants()->create($const,$cid);
		
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
	 * Builds an instance of PC_Class from the given row
	 *
	 * @param array $row the row from the db
	 * @return PC_Class the class
	 */
	private function _build_class($row)
	{
		$c = new PC_Class($row['file'],$row['line'],$row['id']);
		$c->set_name($row['name']);
		$c->set_super_class($row['superclass']);
		$c->set_abstract($row['abstract']);
		$c->set_interface($row['interface']);
		$c->set_final($row['final']);
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