<?php
/**
 * Contains the type-finalizer-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Scans for types in a given string or file. That means classes, functions and constants will
 * be collected.
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Compile_TypeFinalizer extends FWS_Object
{
	/**
	 * The type-container
	 *
	 * @var PC_Compile_TypeContainer
	 */
	private $_types;
	
	/**
	 * The classes to finalize
	 *
	 * @var array
	 */
	private $_classes;
	
	/**
	 * The storage-implementation
	 *
	 * @var PC_Compile_TypeStorage
	 */
	private $_storage;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Compile_TypeContainer $types the type-container, containing the classes to finalize
	 * @param PC_Compile_TypeStorage $storage the storage that handles the changes
	 */
	public function __construct($types,$storage)
	{
		$this->_types = $types;
		$this->_classes = $this->_types->get_classes();
		$this->_storage = $storage;
	}
	
	/**
	 * Finishes the classes. That means, inheritance will be performed and missing
	 * constructors will be added.
	 */
	public function finalize()
	{
		foreach($this->_classes as $name => $c)
		{
			$this->_add_members($this->_classes[$name],$c->get_name());
			
			// add missing constructor
			$c = $this->_classes[$name];
			if(!$c->is_interface() && $c->get_method('__construct') === null)
			{
				$method = new PC_Obj_Method($c->get_file(),-1,false);
				$method->set_name('__construct');
				$method->set_visibity(PC_Obj_Visible::V_PUBLIC);
				$c->add_method($method);
				$this->_storage->create_function($method,$c->get_id());
			}
		}
	}
	
	/**
	 * Adds the member from <var>$class</var> to <var>$data</var>. That means, inheritance is
	 * performed.
	 *
	 * @param PC_Compile_TypeContainer $types the type-container
	 * @param PC_Obj_Class $data the class to which the members should be added
	 * @param string $class the class-name
	 * @param boolean $overwrite just internal: wether the members should be overwritten
	 */
	private function _add_members($data,$class,$overwrite = true)
	{
		$cobj = $this->_types->get_class($class);
		if($cobj !== null)
		{
			if($class != $data->get_name())
			{
				// methods
				foreach($cobj->get_methods() as $function)
				{
					if($function->get_visibility() != PC_Obj_Visible::V_PRIVATE)
					{
						// if we don't want to overwrite the methods and the method is already there
						// we add just the types that are not known yet
						if(!$overwrite && ($f = $data->get_method($function->get_name())) !== null)
						{
							$changed = false;
							/* @var $f PC_Obj_Method */
							if($f->get_return_type()->is_unknown())
							{
								$f->set_return_type($function->get_return_type());
								$changed = true;
							}
							foreach($function->get_params() as $param)
							{
								$fparam = $f->get_param($param->get_name());
								// just replace the parameter if it exists and the type is unknown yet
								if($fparam !== null && $fparam->get_mtype()->is_unknown())
								{
									$f->put_param($param);
									$changed = true;
								}
							}
							
							if($changed)
								$this->_storage->update_function($f,$data->get_id());
						}
						else
						{
							$this->_storage->create_function($function,$data->get_id());
							$data->add_method($function);
						}
					}
				}
				
				// fields
				foreach($cobj->get_fields() as $field)
				{
					if($field->get_visibility() != PC_Obj_Visible::V_PRIVATE)
					{
						$data->add_field($field);
						$this->_storage->create_field($field,$data->get_id());
					}
				}
				
				// constants
				foreach($cobj->get_constants() as $const)
				{
					$data->add_constant($const);
					$this->_storage->create_constant($const,$data->get_id());
				}
			}
			
			$this->_add_members($data,$cobj->get_super_class(),false);
			foreach($cobj->get_interfaces() as $interface)
				$this->_add_members($data,$interface,false);
		}
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>