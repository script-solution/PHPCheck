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
	 * @param int $pid the project-id (default = current)
	 * @param string $file the file-name to search for
	 * @param string $name the function-name to search for
	 * @return int the number
	 */
	public function get_count($class = 0,$pid = PC_Project::CURRENT_ID,$file = '',$name = '')
	{
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		$db = FWS_Props::get()->db();
		$pid = PC_Utils::get_project_id($pid);
		$stmt = $db->get_prepared_statement(
			'SELECT COUNT(*) num FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = '.$pid.' AND class = '.$class
			 .($file ? ' AND file LIKE :file' : '')
			 .($name ? ' AND name LIKE :name' : '')
		);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		$row = $db->get_row($stmt->get_statement());
		return $row['num'];
	}
	
	/**
	 * Returns the function/method with given name and optionally in given class and project
	 *
	 * @param string $name the function-name
	 * @param int $pid the project-id (default = current)
	 * @param string $class the class in which the method is (default: empty, i.e. a free function)
	 * @return PC_Obj_Method the function or null
	 */
	public function get_by_name($name,$pid = PC_Project::CURRENT_ID,$class = '')
	{
		$db = FWS_Props::get()->db();
		
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$stmt = $db->get_prepared_statement(
			'SELECT f.* FROM '.PC_TB_FUNCTIONS.' f
			 LEFT JOIN '.PC_TB_CLASSES.' c ON f.class = c.id AND f.project_id = c.project_id
			 WHERE
			 	f.project_id = :pid AND
			 	((:class = "" AND c.id IS NULL) OR (:class != "" AND c.name = :funcname)) AND
			 	f.name = :funcname'
		);
		$stmt->bind(':pid',PC_Utils::get_project_id($pid));
		$stmt->bind(':class',$class ? $class : '');
		$stmt->bind(':funcname',$name);
		$row = $db->get_row($stmt->get_statement());
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
	 * @param string $file the file-name to search for
	 * @param string $name the function-name to search for
	 * @return array all found functions
	 */
	public function get_list($class = 0,$start = 0,$count = 0,$file = '',$name = '')
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
		$stmt = $db->get_prepared_statement(
			'SELECT * FROM '.PC_TB_FUNCTIONS.'
			 WHERE project_id = '.PC_Utils::get_project_id(PC_Project::CURRENT_ID).' AND class = '.$class.'
			 '.($file ? ' AND file LIKE :file' : '').'
			 '.($name ? ' AND name LIKE :name' : '').'
			 ORDER BY name
			'.($count > 0 ? 'LIMIT '.$start.','.$count : '')
		);
		if($file)
			$stmt->bind(':file','%'.$file.'%');
		if($name)
			$stmt->bind(':name','%'.$name.'%');
		$rows = $db->get_rows($stmt->get_statement());
		foreach($rows as $row)
			$funcs[] = $this->_build_func($row);
		return $funcs;
	}
	
	/**
	 * Creates a new entry for given function
	 *
	 * @param PC_Obj_Method $function the function to create
	 * @param int $class the id of the class the function belongs to
	 * @param int $pid the project-id (-1 = current)
	 * @return int the used id
	 */
	public function create($function,$class = 0,$pid = PC_Project::CURRENT_ID)
	{
		$db = FWS_Props::get()->db();

		if(!($function instanceof PC_Obj_Method))
			FWS_Helper::def_error('instance','function','PC_Obj_Method',$function);
		if(!FWS_Helper::is_integer($class) || $class < 0)
			FWS_Helper::def_error('intge0','class',$class);
		
		return $db->insert(PC_TB_FUNCTIONS,$this->_get_fields($function,$class,$pid));
	}
	
	/**
	 * Updates the properties of the given function
	 *
	 * @param PC_Obj_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @return int the number of affected rows
	 */
	public function update($function,$class = 0)
	{
		$db = FWS_Props::get()->db();
		
		if(!($function instanceof PC_Obj_Method))
			FWS_Helper::def_error('instance','function','PC_Obj_Method',$function);
		
		return $db->update(
			PC_TB_FUNCTIONS,' WHERE id = '.$function->get_id(),$this->_get_fields($function,$class)
		);
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
		
		if(!PC_Utils::is_valid_project_id($id))
			FWS_Helper::def_error('intge0','id',$id);
		
		$db->execute(
			'DELETE FROM '.PC_TB_FUNCTIONS.' WHERE project_id = '.$id
		);
		return $db->get_affected_rows();
	}
	
	/**
	 * Builds the fields to insert / update in the db
	 *
	 * @param PC_Obj_Method $function the function/method
	 * @param int $class the id of the class the function belongs to
	 * @param int $pid the project-id (default = current)
	 * @return array all fields
	 */
	private function _get_fields($function,$class,$pid = PC_Project::CURRENT_ID)
	{
		$params = '';
		foreach($function->get_params() as $param)
		{
			$params .= $param->get_name();
			if($param->is_optional())
				$params .= '?';
			else if($param->is_first_vararg())
				$params .= '*';
			$params .= ':';
			$types = array();
			foreach($param->get_mtype()->get_types() as $type)
			{
				if($type->get_type() == PC_Obj_Type::OBJECT)
					$types[] = $type->get_class();
				else
					$types[] = $type->get_type();
			}
			if(count($types) > 0)
				$params .= implode('|',$types);
			else
				$params .= PC_Obj_Type::UNKNOWN;
			$params .= ';';
		}
		
		$type = $function->get_return_type()->get_type();
		return array(
			'project_id' => PC_UTils::get_project_id($pid),
			'file' => $function->get_file(),
			'line' => $function->get_line(),
			'class' => $class,
			'name' => $function->get_name(),
			'abstract' => $function->is_abstract() ? 1 : 0,
			'final' => $function->is_final() ? 1 : 0,
			'static' => $function->is_static() ? 1 : 0,
			'visibility' => $function->get_visibility(),
			'return_type' => $type == PC_Obj_Type::OBJECT ? $function->get_return_type()->get_class() : $type,
			'params' => $params,
			'since' => $function->get_since()
		);
	}
	
	/**
	 * Builds a PC_Obj_Method from the given row
	 *
	 * @param array $row the row from db
	 * @return PC_Obj_Method the method
	 */
	private function _build_func($row)
	{
		$c = new PC_Obj_Method($row['file'],$row['line'],$row['class'] == 0,$row['id']);
		$c->set_name($row['name']);
		$c->set_visibility($row['visibility']);
		$c->set_abstract($row['abstract']);
		$c->set_static($row['static']);
		$c->set_final($row['final']);
		$c->set_since($row['since']);
		foreach(FWS_Array_Utils::advanced_explode(';',$row['params']) as $param)
		{
			list($name,$type) = explode(':',$param);
			$p = new PC_Obj_Parameter();
			$types = array();
			foreach(explode('|',$type) as $t)
			{
				if(is_numeric($t))
					$types[] = new PC_Obj_Type($t);
				else
					$types[] = new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$t);
			}
			$p->set_mtype(new PC_Obj_MultiType($types));
			if(FWS_String::ends_with($name,'?'))
			{
				$p->set_optional(true);
				$name = FWS_String::substr($name,0,-1);
			}
			else if(FWS_String::ends_with($name,'*'))
			{
				$p->set_first_vararg(true);
				$name = FWS_String::substr($name,0,-1);
			}
			$p->set_name($name);
			$c->put_param($p);
		}
		if(is_numeric($row['return_type']))
			$rettype = new PC_Obj_Type($row['return_type']);
		else
			$rettype = new PC_Obj_Type(PC_Obj_Type::OBJECT,null,$row['return_type']);
		$c->set_return_type($rettype);
		return $c;
	}
}
?>