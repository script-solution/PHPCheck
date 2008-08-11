<?php
/**
 * Contains the location-class
 * 
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Is used as base-class for all objects that have a location (file,line)
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Location extends FWS_Object
{
	/**
	 * The file
	 *
	 * @var string
	 */
	private $file;
	
	/**
	 * The line
	 *
	 * @var int
	 */
	private $line;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the def
	 * @param int $line the line of the def
	 */
	public function __construct($file,$line)
	{
		parent::__construct();
		
		$this->file = $file;
		$this->line = $line;
	}

	/**
	 * @return string the file
	 */
	public function get_file()
	{
		return $this->file;
	}

	/**
	 * @return int the line
	 */
	public function get_line()
	{
		return $this->line;
	}
	
	protected function get_print_vars()
	{
		return get_object_vars($this);
	}
}
?>