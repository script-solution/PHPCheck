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
	 * The created-date
	 *
	 * @var int
	 */
	private $_created;
	
	/**
	 * A newline-separated list of folders for the type-scanner
	 *
	 * @var string
	 */
	private $_type_folders;
	
	/**
	 * A newline-separated list of excluded items for the type-scanner
	 *
	 * @var string
	 */
	private $_type_exclude;
	
	/**
	 * A newline-separated list of folders for the statement-scanner
	 *
	 * @var string
	 */
	private $_stmt_folders;
	
	/**
	 * A newline-separated list of excluded items for the statement-scanner
	 *
	 * @var string
	 */
	private $_stmt_exclude;
	
	/**
	 * Wether potential problems in which mixed types are involved should be treaten as errors
	 * 
	 * @var boolean
	 */
	private $_report_mixed;
	
	/**
	 * Wether potential problems in which unknown types are involved should be treaten as errors
	 * 
	 * @var boolean
	 */
	private $_report_unknown;
	
	/**
	 * Constructor
	 *
	 * @param int $id the project-id
	 * @param string $name the name
	 * @param int $created the timestamp
	 * @param string $type_folders the folders for the type-scanner
	 * @param string $type_exclude the excluded items for the type-scanner
	 * @param string $stmt_folders the folders for the statement-scanner
	 * @param string $stmt_exclude the excluded items for the statement-scanner
	 * @param boolean $report_mixed report errors with mixed types?
	 * @param boolean $report_unknown report errors with unknown types?
	 */
	public function __construct($id,$name,$created,$type_folders,$type_exclude,$stmt_folders,
		$stmt_exclude,$report_mixed,$report_unknown)
	{
		parent::__construct();
		
		if(!FWS_Helper::is_integer($id) || $id <= 0)
			FWS_Helper::def_error('intgt0','id',$id);
		if(!FWS_Helper::is_integer($created) || $created < 0)
			FWS_Helper::def_error('intge0','created',$created);
		
		$this->_id = $id;
		$this->_name = $name;
		$this->_created = $created;
		$this->_type_folders = $type_folders;
		$this->_type_exclude = $type_exclude;
		$this->_stmt_folders = $stmt_folders;
		$this->_stmt_exclude = $stmt_exclude;
		$this->_report_mixed = $report_mixed;
		$this->_report_unknown = $report_unknown;
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
	 * Sets the project-name
	 *
	 * @param string $name the new name
	 */
	public function set_name($name)
	{
		$this->_name = $name;
	}
	
	/**
	 * @return int the created-timestamp
	 */
	public function get_created()
	{
		return $this->_created;
	}
	
	/**
	 * @return string a newline-separated list of folders for the type-scanner
	 */
	public function get_type_folders()
	{
		return $this->_type_folders;
	}
	
	/**
	 * Sets the type-folders
	 *
	 * @param string $str the new value
	 */
	public function set_type_folders($str)
	{
		$this->_type_folders = $str;
	}
	
	/**
	 * @return string a newline-separated list of exclude-items for the type-scanner
	 */
	public function get_type_exclude()
	{
		return $this->_type_exclude;
	}
	
	/**
	 * Sets the type-exclude
	 *
	 * @param string $str the new value
	 */
	public function set_type_exclude($str)
	{
		$this->_type_exclude = $str;
	}
	
	/**
	 * @return string a newline-separated list of folders for the statement-scanner
	 */
	public function get_stmt_folders()
	{
		return $this->_stmt_folders;
	}
	
	/**
	 * Sets the statement-folders
	 *
	 * @param string $str the new value
	 */
	public function set_stmt_folders($str)
	{
		$this->_stmt_folders = $str;
	}
	
	/**
	 * @return string a newline-separated list of exclude-items for the statement-scanner
	 */
	public function get_stmt_exclude()
	{
		return $this->_stmt_exclude;
	}
	
	/**
	 * Sets the statement-exclude
	 *
	 * @param string $str the new value
	 */
	public function set_stmt_exclude($str)
	{
		$this->_stmt_exclude = $str;
	}
	
	/**
	 * @return boolean wether errors with mixed types should be treaten as errors (or ignored)
	 */
	public function get_report_mixed()
	{
		return $this->_report_mixed;
	}
	
	/**
	 * Sets the report-mixed-value
	 *
	 * @param boolean $b the new value
	 */
	public function set_report_mixed($b)
	{
		$this->_report_mixed = $b;
	}
	
	/**
	 * @return boolean wether errors with unknown types should be treaten as errors (or ignored)
	 */
	public function get_report_unknown()
	{
		return $this->_report_unknown;
	}
	
	/**
	 * Sets the report-unknown-value
	 *
	 * @param boolean $b the new value
	 */
	public function set_report_unknown($b)
	{
		$this->_report_unknown = $b;
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