<?php
/**
 * Contains the class-module
 * 
 * @package			PHPCheck
 * @subpackage	module
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
 * The module to display properties of a class
 *
 * @package			PHPCheck
 * @subpackage	module
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_Module_Class extends FWS_Module
{
	/**
	 * The class
	 *
	 * @var PC_Obj_Class
	 */
	private $_class;
	
	/**
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		$input = FWS_Props::get()->input();
		
		$renderer = $doc->use_default_renderer();
		
		$name = $input->get_var('name','get',FWS_Input::STRING);
		$this->_class = PC_DAO::get_classes()->get_by_name($name);
		
		$renderer->add_breadcrumb('Types',PC_URL::build_submod_url('types'));
		$renderer->add_breadcrumb('Classes',PC_URL::build_submod_url('types','classes'));
		$renderer->add_breadcrumb($name,PC_URL::get_mod_url()->set('name',$name)->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		
		if(!$this->_class)
		{
			$this->report_error();
			return;
		}
		
		$curl = PC_URL::get_mod_url();
		
		// build class-declaration
		$declaration = '';
		if(!$this->_class->is_interface())
		{
			if($this->_class->is_abstract())
				$declaration .= 'abstract ';
			else if($this->_class->is_final())
				$declaration .= 'final ';
			$declaration .= 'class ';
		}
		else
			$declaration .= 'interface ';
		$declaration .= $this->_class->get_name().' ';
		if(!$this->_class->is_interface() && ($cn = $this->_class->get_super_class()))
			$declaration .= 'extends <a href="'.$curl->set('name',$cn)->to_url().'">'.$cn.'</a> ';
		if(count($this->_class->get_interfaces()) > 0)
		{
			$declaration .= !$this->_class->is_interface() ? 'implements ' : 'extends ';
			foreach($this->_class->get_interfaces() as $if)
				$declaration .= '<a href="'.$curl->set('name',$if)->to_url().'">'.$if.'</a>, ';
			$declaration = FWS_String::substr($declaration,0,-1);
		}
		$declaration = FWS_String::substr($declaration,0,-1).';';
		
		$tpl->add_variables(array(
			'classname' => $this->_class->get_name(),
			'declaration' => $declaration
		));
		
		$classfile = $this->_class->get_file();
		
		// constants
		$consts = array();
		foreach($this->_class->get_constants() as $const)
		{
			$consts[] = array(
				'name' => $const->get_name(),
				'type' => $const->get_type(),
				'line' => $const->get_line(),
				'url' => $this->_get_url($classfile,$const)
			);
		}
		$tpl->add_variable_ref('consts',$consts);
		
		// fields
		$fields = array();
		$cfields = $this->_class->get_fields();
		ksort($cfields);
		foreach($cfields as $field)
		{
			$fields[] = array(
				'name' => $field->get_name(),
				'type' => (string)$field,
				'line' => $field->get_line(),
				'url' => $this->_get_url($classfile,$field)
			);
		}
		$tpl->add_variable_ref('fields',$fields);
		
		// methods
		$methods = array();
		$cmethods = $this->_class->get_methods();
		ksort($cmethods);
		foreach($cmethods as $method)
		{
			$methods[] = array(
				'name' => $method->get_name(),
				'type' => $method->__ToString(),
				'line' => $method->get_line(),
				'url' => $this->_get_url($classfile,$method),
				'since' => $method->get_since()
			);
		}
		$tpl->add_variable_ref('methods',$methods);
		
		if($this->_class->get_file())
			$source = PC_Utils::highlight_file($this->_class->get_file());
		else
			$source = '';
		$tpl->add_variables(array(
			'source' => $source,
			'file' => $this->_class->get_file(),
			'line' => $this->_class->get_line()
		));
	}
	
	/**
	 * Builds an URL to the given location
	 *
	 * @param string $classfile the file of the class
	 * @param PC_Obj_Location $loc the location of the item
	 * @return string the URL
	 */
	private function _get_url($classfile,$loc)
	{
		if($loc->get_line() == 0)
			return '';
		if($loc->get_file() == $classfile)
			return '#l'.$loc->get_line();
		return PC_URL::get_code_url($loc);
	}
}
