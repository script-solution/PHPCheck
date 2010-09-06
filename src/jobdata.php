<?php
/**
 * Contains the job-data-class
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * A class that holds all data that is shared between all currently running jobs
 * 
 * @package			PHPCheck
 * @subpackage	cli
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_JobData
{
	/**
	 * The number of jobs done
	 * 
	 * @var int
	 */
	private $_done = 0;
	/**
	 * The errors that occurred
	 * 
	 * @var array
	 */
	private $_errors = array();
	/**
	 * Misc data for other purposes
	 * 
	 * @var mixed
	 */
	private $_misc = null;
	
	/**
	 * @return int the number of jobs done so far
	 */
	public function get_done()
	{
		return $this->_done;
	}
	
	/**
	 * Increases the number of finished jobs by 1
	 */
	public function increase_done()
	{
		$this->_done++;
	}
	
	/**
	 * @return array the occurred errors
	 */
	public function get_errors()
	{
		return $this->_errors;
	}
	
	/**
	 * Adds the given error
	 * 
	 * @param string $msg the error-message
	 */
	public function add_error($msg)
	{
		if(!is_string($msg))
			FWS_Helper::def_error('string','msg',$msg);
		$this->_errors[] = $msg;
	}
	
	/**
	 * Adds all given errors
	 * 
	 * @param array $errors
	 */
	public function add_errors($errors)
	{
		if(!is_array($errors))
			FWS_Helper::def_error('array','errors',$errors);
		$this->_errors = array_merge($this->_errors,$errors);
	}
	
	/**
	 * @return mixed the misc-data
	 */
	public function get_misc()
	{
		return $this->_misc;
	}
	
	/**
	 * Sets the misc-data
	 * 
	 * @param mixed $misc the new value
	 */
	public function set_misc($misc)
	{
		$this->_misc = $misc;
	}
}
?>