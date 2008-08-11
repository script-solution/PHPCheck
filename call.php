<?php
/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

class PC_Call extends PC_Location
{
	private $function;
	private $class = null;
	private $arguments = array();

	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
	}

	public function get_function()
	{
		return $this->function;
	}

	public function set_function($function)
	{
		$this->function = $function;
	}

	public function get_class()
	{
		return $this->class;
	}

	public function set_class($class)
	{
		$this->class = $class;
	}

	public function get_arguments()
	{
		return $this->arguments;
	}

	public function add_argument($type)
	{
		$this->arguments[] = $type;
	}
	
	protected function get_print_vars()
	{
		return array_merge(parent::get_print_vars(),get_object_vars($this));
	}
}
?>