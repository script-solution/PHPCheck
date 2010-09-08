<?php
/**
 * Contains the scope-class
 *
 * @version			$Id: basescanner.php 72 2010-09-06 20:16:22Z nasmussen $
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Manages the scope for the statement-scanner
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Engine_Scope extends FWS_Object
{
	/**
	 * The current class we're in or empty; we can't nest classes
	 * (Although the grammar allows defining classes in class-methods, PHP silently dies in this case :))
	 * 
	 * @var string
	 */
	private $class = '';
	/**
	 * Wether the current class-definition is in a function
	 * 
	 * @var bool
	 */
	private $classInFunc = false;
	/**
	 * A stack of function-names we're currently in
	 * 
	 * @var array
	 */
	private $funcscope = array(PC_Obj_Variable::SCOPE_GLOBAL);
	
	/**
	 * @return string the name of the current scope
	 */
	public function get_name()
	{
		$name = '';
		if(($class = $this->get_name_of(T_CLASS_C)))
			$name .= $class.'::';
		$name .= $this->funcscope[count($this->funcscope) - 1];
		return $name;
	}
	
	/**
	 * Extracts the given part of the scope
	 * 
	 * @param int $part the part: T_METHOD_C, T_FUNCTION_C or T_CLASS_C
	 * @return string the scope-part-name
	 */
	public function get_name_of($part)
	{
		$str = '';
		switch($part)
		{
			case T_METHOD_C:
			case T_FUNC_C:
				if(count($this->funcscope) > 1)
					$str = $this->funcscope[count($this->funcscope) - 1];
				break;
			case T_CLASS_C:
				// if we define a function in a class-method, it is global
				if($this->class && !$this->classInFunc && count($this->funcscope) > 2)
					$str = '';
				else
					$str = $this->class;
				break;
		}
		return $str;
	}
	
	/**
	 * Enters the class with given name
	 * 
	 * @param string $name the class-name
	 */
	public function enter_class($name)
	{
		assert($this->class == '');
		$this->classInFunc = count($this->funcscope) > 1;
		$this->class = $name;
	}
	
	/**
	 * Enters the function with given name
	 * 
	 * @param string $name the function-name
	 */
	public function enter_function($name)
	{
		array_push($this->funcscope,$name);
	}
	
	/**
	 * Leaves the current class
	 */
	public function leave_class()
	{
		assert($this->class != '');
		$this->class = '';
		$this->classInFunc = false;
	}
	
	/**
	 * Leaves the current function
	 */
	public function leave_function()
	{
		// we can't leave the global scope
		assert(count($this->funcscope) > 1);
		array_pop($this->funcscope);
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>