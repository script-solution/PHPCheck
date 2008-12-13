<?php
/**
 * Contains the typescan-task
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The task to scan for types
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_TypeScan_Task_Scan extends FWS_Object implements FWS_Progress_Task
{
	/**
	 * @see FWS_Progress_Task::get_total_operations()
	 *
	 * @return int
	 */
	public function get_total_operations()
	{
		$user = FWS_Props::get()->user();
		return count($user->get_session_data('typescan_files',array()));
	}

	/**
	 * @see FWS_Progress_Task::run()
	 *
	 * @param int $pos
	 * @param int $ops
	 */
	public function run($pos,$ops)
	{
		$user = FWS_Props::get()->user();
		
		$tscanner = new PC_TypeScanner();
		$files = $user->get_session_data('typescan_files',array());
		$end = min($pos + $ops,count($files));
		for($i = $pos;$i < $end;$i++)
			$tscanner->scan_file($files[$i]);
		
		foreach($tscanner->get_classes() as $class)
			PC_DAO::get_classes()->create($class);
		foreach($tscanner->get_constants() as $const)
			PC_DAO::get_constants()->create($const);
		foreach($tscanner->get_functions() as $func)
			PC_DAO::get_functions()->create($func);
		
		// finish all classes if the typescan is finished
		if($pos + $ops >= $this->get_total_operations())
		{
			$classes = array();
			foreach(PC_DAO::get_classes()->get_list() as $class)
				$classes[$class->get_name()] = $class;
			$this->_finish($classes);
		}
	}
	
	/**
	 * Finishes the given classes. That means, inheritance will be performed and missing
	 * constructors will be added.
	 *
	 * @param array $classes the classes
	 */
	private function _finish($classes)
	{
		$types = new PC_TypeContainer();
		foreach($classes as $name => $c)
		{
			$this->_add_members($types,$classes[$name],$c->get_name());
			
			// add missing constructor
			$c = $classes[$name];
			if(!$c->is_interface() && $c->get_method('__construct') === null)
			{
				$method = new PC_Method($c->get_file(),-1,false);
				$method->set_name('__construct');
				$method->set_visibity(PC_Visible::V_PUBLIC);
				$c->add_method($method);
				PC_DAO::get_functions()->create($method,$c->get_id());
			}
		}
	}
	
	/**
	 * Adds the member from <var>$class</var> to <var>$data</var>. That means, inheritance is
	 * performed.
	 *
	 * @param PC_TypeContainer $types the type-container
	 * @param PC_Class $data the class to which the members should be added
	 * @param string $class the class-name
	 * @param boolean $overwrite just internal: wether the members should be overwritten
	 */
	private function _add_members($types,$data,$class,$overwrite = true)
	{
		if(isset($this->classes[$class]))
			$cobj = $this->classes[$class];
		else
			$cobj = $types->get_class($class);
		
		if($cobj !== null)
		{
			if($class != $data->get_name())
			{
				// methods
				foreach($cobj->get_methods() as $function)
				{
					if($function->get_visibility() != PC_Visible::V_PRIVATE)
					{
						// if we don't want to overwrite the methods and the method is already there
						// we add just the types that are not known yet
						if(!$overwrite && ($f = $data->get_method($function->get_name())) !== null)
						{
							$changed = false;
							/* @var $f PC_Method */
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
								PC_DAO::get_functions()->update($f,$data->get_id());
						}
						else
						{
							PC_DAO::get_functions()->create($function,$data->get_id());
							$data->add_method($function);
						}
					}
				}
				
				// fields
				foreach($cobj->get_fields() as $field)
				{
					if($field->get_visibility() != PC_Visible::V_PRIVATE)
					{
						$data->add_field($field);
						PC_DAO::get_classfields()->create($field,$data->get_id());
					}
				}
				
				// constants
				foreach($cobj->get_constants() as $const)
				{
					$data->add_constant($const);
					PC_DAO::get_constants()->create($const,$data->get_id());
				}
			}
			
			$this->_add_members($types,$data,$cobj->get_super_class(),false);
			foreach($cobj->get_interfaces() as $interface)
				$this->_add_members($types,$data,$interface,false);
		}
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