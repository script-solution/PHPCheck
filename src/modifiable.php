<?php
/**
 * Contains the modifiable-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Stores wether an "object" is abstract or final and the name.
 *
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Modifiable extends PC_Location
{
	/**
	 * Is it abstract?
	 *
	 * @var boolean
	 */
	private $abstract = false;
	
	/**
	 * Is it final?
	 *
	 * @var boolean
	 */
	private $final = false;
	
	/**
	 * The name
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Constructor
	 *
	 * @param string $file the file of the class-def
	 * @param int $line the line of the class-def
	 */
	public function __construct($file,$line)
	{
		parent::__construct($file,$line);
	}
	
	/**
	 * @return string the class-name
	 */
	public function get_name()
	{
		return $this->name;
	}
	
	/**
	 * Sets the name
	 *
	 * @param string $name the new value
	 */
	public function set_name($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return boolean wether it is final
	 */
	public function is_final()
	{
		return $this->final;
	}
	
	/**
	 * Sets wether it is final
	 *
	 * @param boolean $final the new value
	 */
	public function set_final($final)
	{
		$this->final = (bool)$final;
	}
	
	/**
	 * @return boolean wether it is abstract
	 */
	public function is_abstract()
	{
		return $this->abstract;
	}
	
	/**
	 * Sets wether it is abstract
	 *
	 * @param boolean $abstract the new value
	 */
	public function set_abstract($abstract)
	{
		$this->abstract = (bool)$abstract;
	}
	
	protected function get_dump_vars()
	{
		return array_merge(parent::get_dump_vars(),get_object_vars($this));
	}
}
?>