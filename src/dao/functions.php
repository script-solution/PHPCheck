<?php
/**
 * Contains the functions-dao-class
 *
 * @version			$Id$
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
	 * Returns the number of functions for the given project
	 *
	 * @param int $class the class-id (0 = free functions)
	 * @param int $pid the project-id (0 = current)
	 * @return int the number
	 */
	public function get_count($class = 0,$pid = 0)
	{
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		if(!FWS_Helper::is_integer($pid) || $pid < 0)
			FWS_Helper::def_error('intge0','pid',$pid);
		
		$db = FWS_Props::get()->db();
		$project = FWS_Props::get()->project();
		$pid = $pid === 0 ? $project->get_id() : $pid;
		return $db->sql_num(PC_TB_FUNCTIONS,'*',' WHERE project_id = '.$pid.' AND class = '.$class);
	}
	
	/**
	 * Returns the (free) function with given name in the given project
	 *
	 * @param string $name the function-name
	 * @param int $pid the project-id (0 = current)
	 * @return PC_Method the function or null
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
			'SELECT * FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = '.$pid.' AND class = 0 AND name = "'.addslashes($name).'"'
		);
		if($row)
			return $this->_build_func($row);
		return null;
	}
	
	/**
	 * Returns all functions
	 *
	 * @param int $class the class-id (0 = free functions)
	 * @param int $start the start-position (for the LIMIT-statement)
	 * @param int $count the max. number of rows (for the LIMIT-statement) (0 = unlimited)
	 * @return array all found functions
	 */
	public function get_list($class = 0,$start = 0,$count = 0)
	{
		$db = FWS_Props::get()->db();

		if(!FWS_Helper::is_integer($start) || $start < 0)
			FWS_Helper::def_error('intge0','start',$start);
		if(!FWS_Helper::is_integer($count) || $count < 0)
			FWS_Helper::def_error('intge0','count',$count);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$project = FWS_Props::get()->project();
		$funcs = array();
		$rows = $db->sql_rows(
			'SELECT * FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = '.$project->get_id().' AND class = '.$class.'
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		foreach($rows as $row)
			$funcs[] = $this->_build_func($row);
		return $funcs;
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
		
		$db->sql_insert(PC_TB_FUNCTIONS,$this->_get_fields($function,$class));
		return $db->get_last_insert_id();
	}
	
	/**
	 * Updates the properties of the given function
	 *
	 * @param PC_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @return int the number of affected rows
	 */
	public function update($function,$class = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(!($function instanceof PC_Method))
			FWS_Helper::def_error('instance','function','PC_Method',$function);
		
		$db->sql_update(
			PC_TB_FUNCTIONS,' WHERE id = '.$function->get_id(),$this->_get_fields($function,$class)
		);
		return $db->get_affected_rows();
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
	
	/**
	 * Builds the fields to insert / update in the db
	 *
	 * @param PC_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @return array all fields
	 */
	private function _get_fields($function,$class)
	{
		$params = '';
		foreach($function->get_params() as $param)
		{
			$params .= $param->get_name();
			if($param->is_optional())
				$params .= '?';
			$params .= ':';
			$types = array();
			foreach($param->get_mtype()->get_types() as $type)
			{
				if($type->get_type() == PC_Type::OBJECT)
					$types[] = $type->get_class();
				else
					$types[] = $type->get_type();
			}
			if(count($types) > 0)
				$params .= implode('|',$types);
			else
				$params .= PC_Type::UNKNOWN;
			$params .= ';';
		}
		
		$project = FWS_Props::get()->project();
		$type = $function->get_return_type()->get_type();
		return array(
			'project_id' => $project->get_id(),
			'file' => addslashes($function->get_file()),
			'line' => $function->get_line(),
			'class' => $class,
			'name' => addslashes($function->get_name()),
			'abstract' => $function->is_abstract() ? 1 : 0,
			'final' => $function->is_final() ? 1 : 0,
			'static' => $function->is_static() ? 1 : 0,
			'visibility' => $function->get_visibility(),
			'return_type' => $type == PC_Type::OBJECT ? $function->get_return_type()->get_class() : $type,
			'params' => $params
		);
	}
	
	/**
	 * Builds a PC_Method from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Method the method
	 */
	private function _build_func($row)
	{
		$c = new PC_Method($row['file'],$row['line'],$row['class'] == 0,$row['id']);
		$c->set_name($row['name']);
		$c->set_visibity($row['visibility']);
		$c->set_abstract($row['abstract']);
		$c->set_static($row['static']);
		$c->set_final($row['final']);
		foreach(FWS_Array_Utils::advanced_explode(';',$row['params']) as $param)
		{
			$x = explode(':',$param);
			list($name,$type) = explode(':',$param);
			$p = new PC_Parameter();
			$types = array();
			foreach(explode('|',$type) as $t)
			{
				if(is_numeric($t))
					$types[] = new PC_Type($t);
				else
					$types[] = new PC_Type(PC_Type::OBJECT,null,$t);
			}
			$p->set_mtype(new PC_MultiType($types));
			if(FWS_String::ends_with($name,'?'))
			{
				$p->set_optional(true);
				$name = FWS_String::substr($name,0,-1);
			}
			$p->set_name($name);
			$c->put_param($p);
		}
		if(is_numeric($row['return_type']))
			$rettype = new PC_Type($row['return_type']);
		else
			$rettype = new PC_Type(PC_Type::OBJECT,null,$row['return_type']);
		$c->set_return_type($rettype);
		return $c;
	}
}
?>