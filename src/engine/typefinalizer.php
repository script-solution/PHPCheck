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
 * Performs finalizing operations on all collected classes.
 *
 * @package			PHPCheck
 * @subpackage	src.engine
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Engine_TypeFinalizer extends FWS_Object
{
	/**
	 * The environment
	 *
	 * @var PC_Engine_Env
	 */
	private $env;
	
	/**
	 * Constructor
	 * 
	 * @param PC_Engine_Env $env the environment
	 */
	public function __construct($env)
	{
		parent::__construct();
		
		$this->env = $env;
	}
	
	/**
	 * Finishes the classes. That means, inheritance will be performed and missing
	 * constructors will be added.
	 */
	public function finalize()
	{
		foreach($this->env->get_types()->get_classes() as $c)
		{
			if($c->get_pid() != $this->env->get_options()->get_current_project())
				continue;
			
			/* @var $c PC_Obj_Class */
			$this->add_members($c,$c->get_name());
			
			// add missing constructor
			if(!$c->is_interface() && $c->get_method('__construct') === null &&
				$c->get_method($c->get_name()) === null)
			{
				$method = new PC_Obj_Method($c->get_file(),-1,false);
				$method->set_name('__construct');
				$method->set_visibility(PC_Obj_Visible::V_PUBLIC);
				$c->add_method($method);
				$this->env->get_storage()->create_function($method,$c->get_id());
			}
		}
	}
	
	/**
	 * Adds the member from <var>$class</var> to <var>$data</var>. That means, inheritance is
	 * performed.
	 *
	 * @param PC_Obj_Class $data the class to which the members should be added
	 * @param string $class the class-name
	 * @param boolean $overwrite just internal: whether the members should be overwritten
	 */
	private function add_members($data,$class,$overwrite = true)
	{
		$cobj = $this->env->get_types()->get_class($class);
		if($cobj !== null)
		{
			if(strcasecmp($class,$data->get_name()) != 0)
			{
				// methods
				foreach($cobj->get_methods() as $function)
				{
					if($function->get_visibility() != PC_Obj_Visible::V_PRIVATE)
					{
						// don't inherit a constructor if the class has a old-style-one
						if(strcasecmp($function->get_name(),'__construct') == 0 &&
							$data->get_method($data->get_name()) !== null)
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
								$this->env->get_storage()->update_function($f,$data->get_id());
						}
						else
						{
							$clone = clone $function;
							// change constructor-name, if it is an old-style-one
							if(strcasecmp($function->get_name(),$cobj->get_name()) == 0)
								$clone->set_name($data->get_name());
							$clone->set_id($this->env->get_storage()->create_function($clone,$data->get_id()));
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
						$this->env->get_storage()->create_field($clone,$data->get_id());
					}
				}
				
				// constants
				foreach($cobj->get_constants() as $const)
				{
					$clone = clone $const;
					$data->add_constant($clone);
					$this->env->get_storage()->create_constant($clone,$data->get_id());
				}
			}
			
			// protect ourself from recursion here. in fact, Iterator implements itself, so that this is
			// actually necessary.
			if(strcasecmp($class,$cobj->get_super_class()) != 0)
				$this->add_members($data,$cobj->get_super_class(),false);
			foreach($cobj->get_interfaces() as $interface)
			{
				if(strcasecmp($class,$interface) != 0)
					$this->add_members($data,$interface,false);
			}
		}
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
