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

final class PC_Module_Class extends FWS_Module
{
	/**
	 * The class
	 *
	 * @var PC_Class
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
		
		$name = $input->get_var('name','get',FWS_Input::IDENTIFIER);
		$this->_class = PC_DAO::get_classes()->get_by_name($name);
		
		$renderer->add_breadcrumb('Classes',PC_URL::build_submod_url('types','classes'));
		$renderer->add_breadcrumb($name,PC_URL::get_mod_url()->set('name',$name)->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		
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
		
		// constants
		$consts = $this->_class->get_constants();
		ksort($consts);
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
				'line' => $field->get_line()
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
				'line' => $method->get_line()
			);
		}
		$tpl->add_variable_ref('methods',$methods);
		
		// source-lines
		if(is_file($this->_class->get_file()))
			$source = FWS_FileUtils::read($this->_class->get_file());
		else
			$source = '';
		
		$decorator = new FWS_Highlighting_Decorator_HTML();
		$lang = new FWS_Highlighting_Language_XML('../Boardsolution/bbceditor/highlighter/php.xml');
		$hl = new FWS_Highlighting_Processor($source,$lang,$decorator);
		$res = $hl->highlight();
		$lines = array();
		$x = 1;
		foreach(explode('<br />',$res) as $line)
			$lines[] = '<span id="l'.$x++.'"></span>'.$line;
		$tpl->add_variables(array('source' => implode('<br />',$lines)));
	}
}
?>