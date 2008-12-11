<?php
/**
 * Contains the project-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Represents a project
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Project extends FWS_Object
{
	/**
	 * The project-id
	 *
	 * @var int
	 */
	private $_id;
	
	/**
	 * The project-name
	 *
	 * @var string
	 */
	private $_name;
	
	/**
	 * Constructor
	 *
	 * @param int $id the project-id
	 * @param string $name the name
	 */
	public function __construct($id,$name)
	{
		parent::__construct();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		if(empty($name))
			FWS_Helper::def_error('notempty','name',$name);
		
		$this->_id = $id;
		$this->_name = $name;
	}
	
	/**
	 * @return int the project-id
	 */
	public function get_id()
	{
		return $this->_id;
	}
	
	/**
	 * @return string the project-name
	 */
	public function get_name()
	{
		return $this->_name;
	}

	/**
	 * @see FWS_Object::get_dump_vars()
	 *
	 * @return array
	 */
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>