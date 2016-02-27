<?php
/**
 * Contains the type-finalizer-class
 * 
 * @package			PHPCheck
 * @subpackage	src.engine
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * Scans for types in a given string or file. That means classes, functions and constants will
 * be collected.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
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
		foreach($this->_types->get_classes() as $c)
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
		// check if its a "magic" method
		if($class && $this->handle_magic($class,$method))
			return;
		
		// don't require comments for anonymous functions
		if($method->is_anonymous())
			return;
		
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
	 * Checks if the given method is "magic". If so it adds automatically parameter-types (if not
	 * already set).
	 * 
	 * @param string $classname the class-name
	 * @param PC_Obj_Method $method the method
	 * @return bool true if handled
	 */
	private function handle_magic($classname,$method)
	{
		// the magic-methods:
		// public void __set ( string $name , mixed $value )
		// public mixed __get ( string $name )
		// public bool __isset ( string $name )
		// public void __unset ( string $name )
		// public mixed __call ( string $name , array $arguments )
		// public mixed __callStatic ( string $name , array $arguments )
		// * array __sleep( void )
		// * void__wakeup( void )
		// public string __toString( void )
		// public void __invoke( ... )
		// public static object __set_state( array $props )
		// * void __clone( void )
		
		$ismagic = true;
		$visibility = PC_Obj_Visible::V_PUBLIC;
		$static = false;
		$expected = null;
		switch(strtolower($method->get_name()))
		{
			case '__set':
				$expected = array(
					new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string()),
					new PC_Obj_Parameter('value',new PC_Obj_MultiType())
				);
				break;
			case '__isset':
			case '__unset':
			case '__get':
				$expected = array(
					new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string())
				);
				break;
			
			case '__call':
			case '__callstatic':
				$expected = array(
					new PC_Obj_Parameter('name',PC_Obj_MultiType::create_string()),
					new PC_Obj_Parameter('arguments',PC_Obj_MultiType::create_array()),
				);
				break;
				
			case '__tostring':
				$expected = array();
				break;
				
			case '__sleep':
			case '__wakeup':
			case '__clone':
				$visibility = null;	// may be private or protected
				$expected = array();
				break;
				
			case '__invoke':
				$static = true;
				break;
				
			case '__set_state':
				$expected = array(
					new PC_Obj_Parameter('props',PC_Obj_MultiType::create_array())
				);
				break;
				
			default:
				$ismagic = false;
				break;
		}
		
		if(!$ismagic)
			return false;
		
		if($visibility !== null && $method->get_visibility() != $visibility)
		{
			$this->report_error(
				$method,
				'The magic method "#'.$classname.'#::'.$method->get_name().'" should be public',
				PC_Obj_Error::E_T_MAGIC_NOT_PUBLIC
			);
		}
		
		if($static && !$method->is_static())
		{
			$this->report_error(
				$method,
				'The magic method "#'.$classname.'#::'.$method->get_name().'" should be static',
				PC_Obj_Error::E_T_MAGIC_NOT_STATIC
			);
		}
		else if(!$static && $method->is_static())
		{
			$this->report_error(
				$method,
				'The magic method "#'.$classname.'#::'.$method->get_name().'" should not be static',
				PC_Obj_Error::E_T_MAGIC_IS_STATIC
			);
		}
		
		if($expected !== null)
		{
			$changed = false;
			if($this->check_params($classname,$method,$expected,$method->get_params()))
			{
				// set parameter-descriptions
				$i = 0;
				foreach($method->get_params() as $param)
				{
					$param->set_mtype($expected[$i++]->get_mtype());
					$param->set_has_doc(true);
					$changed = true;
				}
			}
		
			$return = new PC_Obj_MultiType();
			switch(strtolower($method->get_name()))
			{
				case '__set':
				case '__unset':
				case '__wakeup':
				case '__clone':
					$return = PC_Obj_MultiType::create_void();
					break;
				case '__set_state':
					$return = PC_Obj_MultiType::create_object();
					break;
				case '__isset':
					$return = PC_Obj_MultiType::create_bool();
					break;
				case '__sleep':
					$return = PC_Obj_MultiType::create_array();
					break;
				case '__tostring':
					$return = PC_Obj_MultiType::create_string();
					break;
			}
			
			if($method->has_return_doc() && !$method->get_return_type()->equals($return))
			{
				// its ok to specify a more specific return-value if the expected one is "mixed"
				if(!$return->is_unknown())
				{
					$this->report_error(
						$method,
						'The return-type of the magic-method "#'.$classname.'#::'.$method->get_name().'" is invalid '
						.'(expected="'.$return.'", found="'.$method->get_return_type().'")',
						PC_Obj_Error::E_T_MAGIC_METHOD_RET_INVALID
					);
				}
			}
			
			if($return !== null && !$method->has_return_doc())
			{
				$method->set_return_type($return);
				$method->set_has_return_doc(true);
				$changed = true;
			}
			
			if($changed)
				$this->_storage->update_function($method,$method->get_class());
		}
		return true;
	}
	
	/**
	 * Checks wether the expected parameters match the actual
	 * 
	 * @param string $classname the class-name
	 * @param PC_Obj_Method $method the method
	 * @param array $expected an array of the expected parameters
	 * @param array $actual an array of the actual parameters
	 * @return bool true if are equal, false if they differ
	 */
	private function check_params($classname,$method,$expected,$actual)
	{
		if(!$this->compare_params($expected,$actual))
		{
			$this->report_error(
				$method,
				'The parameters of the magic-method "#'.$classname.'#::'.$method->get_name().'" are invalid '
				.'(expected="'.implode(',',$expected).'", found="'.implode(',',$actual).'")',
				PC_Obj_Error::E_T_MAGIC_METHOD_PARAMS_INVALID
			);
			return false;
		}
		return true;
	}
	
	/**
	 * Compares the given parameters
	 * 
	 * @param array $expected an array of the expected parameters
	 * @param array $actual an array of the actual parameters
	 * @return bool true if are equal, false if they differ
	 */
	private function compare_params($expected,$actual)
	{
		$ecount = count($expected);
		if($ecount != count($actual))
			return false;
		$exp = array_values($expected);
		$act = array_values($actual);
		for($i = 0; $i < $ecount; $i++)
		{
			// just check, if we have a parameter-description via PHPDoc
			// and if the expected is not unknown. since, if it is unknown (=mixed), the user may
			// specify something more specific
			if($exp[$i]->has_doc() && !$exp[$i]->get_mtype()->is_unknown() &&
					!$exp[$i]->get_mtype()->equals($act[$i]))
				return false;
		}
		return true;
	}

	/**
	 * Reports the given error
	 * 
	 * @param PC_Obj_Location $locsrc an object from which the location will be copied
	 * @param string $msg the error-message
	 * @param int $type the error-type
	 */
	private function report_error($locsrc,$msg,$type)
	{
		$this->_types->add_errors(array(
			new PC_Obj_Error(new PC_Obj_Location($locsrc->get_file(),$locsrc->get_line()),$msg,$type)
		));
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
							
							// add missing throws
							foreach(array_keys($function->get_throws()) as $tclass)
							{
								if(!$f->contains_throw($tclass))
								{
									$f->add_throw($tclass,PC_Obj_Method::THROW_PARENT);
									$changed = true;
								}
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
			
			// protect ourself from recursion here. in fact, Iterator implements itself, so that this is
			// actually necessary.
			if($class != $cobj->get_super_class())
				$this->_add_members($data,$cobj->get_super_class(),false);
			foreach($cobj->get_interfaces() as $interface)
			{
				if($class != $interface)
					$this->_add_members($data,$interface,false);
			}
		}
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
