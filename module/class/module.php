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
	 * @see FWS_Module::init()
	 *
	 * @param FWS_Document $doc
	 */
	public function init($doc)
	{
		$input = FWS_Props::get()->input();
		
		$renderer = $doc->use_default_renderer();
		
		$url = new FWS_URL();
		$url->set('module','classes');
		$renderer->add_breadcrumb('Classes',$url->to_url());
		
		$class = $input->get_var('name','get',FWS_Input::IDENTIFIER);
		$url = new FWS_URL();
		$url->set('module','class');
		$url->set('name',$class);
		$renderer->add_breadcrumb($class,$url->to_url());
	}

	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$tpl = FWS_Props::get()->tpl();
		$input = FWS_Props::get()->input();
		
		$class = $input->get_var('name','get',FWS_Input::IDENTIFIER);
		list(,,$classes) = PC_Utils::get_data();
		if(!$class || !isset($classes[$class]))
		{
			$this->report_error();
			return;
		}
		
		$curl = new FWS_URL();
		$curl->set('module','class');
		
		$class = $classes[$class];
		/* @var $class PC_Class */
		
		// build class-declaration
		$declaration = '';
		if(!$class->is_interface())
		{
			if($class->is_abstract())
				$declaration .= 'abstract ';
			else if($class->is_final())
				$declaration .= 'final ';
			$declaration .= 'class ';
		}
		else
			$declaration .= 'interface ';
		$declaration .= $class->get_name().' ';
		if(!$class->is_interface() && ($cn = $class->get_super_class()))
			$declaration .= 'extends <a href="'.$curl->set('name',$cn)->to_url().'">'.$cn.'</a> ';
		if(count($class->get_interfaces()) > 0)
		{
			$declaration .= !$class->is_interface() ? 'implements ' : 'extends ';
			foreach($class->get_interfaces() as $if)
				$declaration .= '<a href="'.$curl->set('name',$if)->to_url().'">'.$if.'</a>, ';
			$declaration = FWS_String::substr($declaration,0,-1);
		}
		$declaration = FWS_String::substr($declaration,0,-1).';';
		
		$tpl->add_variables(array(
			'classname' => $class->get_name(),
			'declaration' => $declaration
		));
		
		// constants
		$consts = $class->get_constants();
		ksort($consts);
		$tpl->add_variable_ref('consts',$consts);
		
		// fields
		$fields = array();
		$cfields = $class->get_fields();
		ksort($cfields);
		foreach($cfields as $field)
		{
			$fields[] = array(
				'name' => $field->get_name(),
				'type' => $field->get_type(),
				'line' => 1//$field->get_line()
			);
		}
		$tpl->add_variable_ref('fields',$fields);
		
		// methods
		$methods = array();
		$cmethods = $class->get_methods();
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
		if(is_file($class->get_file()))
			$source = FWS_FileUtils::read($class->get_file());
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