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
final class PC_Engine_TypeFinalizer extends FWS_Object
{
	/**
	 * The type-container
	 *
	 * @var PC_Engine_TypeContainer
	 */
	private $_types;
	
	/**
	 * The storage-implementation
	 *
	 * @var PC_Engine_TypeStorage
	 */
	private $_storage;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_TypeContainer $types the type-container, containing the classes to finalize
	 * @param PC_Engine_TypeStorage $storage the storage that handles the changes
	 */
	public function __construct($types,$storage)
	{
		parent::__construct();
		$this->_types = $types;
		$this->_storage = $storage;
	}
	
	/**
	 * Finishes the classes. That means, inheritance will be performed and missing
	 * constructors will be added. Additionally it finalizes the given potential errors,
	 * i.e. it removes the ones that are none anymore.
	 */
	public function finalize()
	{
		foreach($this->_types->get_classes() as $name => $c)
		{
			/* @var $c PC_Obj_Class */
			$this->_add_members($c,$c->get_name());
			
			foreach($c->get_methods() as $m)
				$this->_check_method($m,$c->get_name());
			
			// add missing constructor
			if(!$c->is_interface() && $c->get_method('__construct') === null &&
				$c->get_method($c->get_name()) === null)
			{
				$method = new PC_Obj_Method($c->get_file(),-1,false);
				$method->set_name('__construct');
				$method->set_visibility(PC_Obj_Visible::V_PUBLIC);
				$c->add_method($method);
				$this->_storage->create_function($method,$c->get_id());
			}
		}
		
		foreach($this->_types->get_functions() as $func)
			$this->_check_method($func);
	}
	
	/**
	 * Checks the method for missing PHPDoc-tags
	 * 
	 * @param PC_Obj_Method $method the method
	 * @param string $class the class-name, if present
	 */
	private function _check_method($method,$class = '')
	{
		foreach($method->get_params() as $param)
		{
			if(!$param->has_doc())
			{
				$this->_types->add_errors(array(
					new PC_Obj_Error(
						new PC_Obj_Location($method->get_file(),$method->get_line()),
						'The parameter "'.$param->get_name().'" ('.$param.')'
						.' of "'.($class ? $class.'::' : '').$method->get_name().'" has no PHPDoc',
						PC_Obj_Error::E_T_PARAM_WITHOUT_DOC
					),
				));
			}
		}
	}
	
	/**
	 * Adds the member from <var>$class</var> to <var>$data</var>. That means, inheritance is
	 * performed.
	 *
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
						// don't inherit a constructor if the class has a old-style-one
						if($function->get_name() == '__construct' && $data->get_method($data->get_name()) !== null)
							continue;
						
						// if we don't want to overwrite the methods and the method is already there
						// we add just the types that are not known yet
						if(!$overwrite && ($f = $data->get_method($function->get_name())) !== null)
						{
							$changed = false;
							/* @var $f PC_Obj_Method */
							if(!$f->has_return_doc())
							{
								$f->set_return_type($function->get_return_type());
								$f->set_has_return_doc($function->has_return_doc());
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
							$clone = clone $function;
							// change constructor-name, if it is an old-style-one
							if($function->get_name() == $cobj->get_name())
								$clone->set_name($data->get_name());
							$clone->set_id($this->_storage->create_function($clone,$data->get_id()));
							$data->add_method($clone);
						}
					}
				}
				
				// fields
				foreach($cobj->get_fields() as $field)
				{
					if($field->get_visibility() != PC_Obj_Visible::V_PRIVATE)
					{
						$clone = clone $field;
						$data->add_field($clone);
						$this->_storage->create_field($clone,$data->get_id());
					}
				}
				
				// constants
				foreach($cobj->get_constants() as $const)
				{
					$clone = clone $const;
					$data->add_constant($clone);
					$this->_storage->create_constant($clone,$data->get_id());
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