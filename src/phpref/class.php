<?php
/**
 * Contains the class-parser
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Loads the given page from file and parses information about the described class
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PHPRef_Class extends FWS_Object
{
	/**
	 * The file
	 * 
	 * @var string
	 */
	private $file;
	
	/**
	 * Constructor
	 * 
	 * @param string $file the file that describes the class
	 */
	public function __construct($file)
	{
		parent::__construct();
		$this->file = $file;
	}
	
	/**
	 * Fetches the page from the specified file and parses it for information about the class
	 * 
	 * @return PC_Obj_Class the class that was foudn
	 * @throws PC_PHPRef_Exception if it failed
	 */
	public function get_class()
	{
		$match = array();
		$content = file_get_contents($this->file);
		
		if(!preg_match('/<b class="classname">(.*?)<\/b>/s',$content,$match))
			throw new PC_PHPRef_Exception('Unable to find class-name in file "'.$this->file.'"');
		$name = $match[1];
		
		$class = new PC_Obj_Class('',0);
		$class->set_name($name);
		
		// determine super-class
		$res = preg_match(
			'/<span class="modifier">extends<\/span>\s*<a href=".*?" class=".*?">(.*?)<\/a>/s',
			$content,
			$match
		);
		if($res)
			$class->set_super_class($match[1]);
		
		// determine interfaces
		$matches = array();
		$res = preg_match_all(
			'/<span class="interfacename"><a href=".*?" class=".*?">(.*?)<\/a><\/span>/s',
			$content,
			$matches
		);
		if($res)
		{
			foreach($matches[0] as $k => $v)
				$class->add_interface($matches[1][$k]);
		}
		
		if(preg_match('/<h2 class="title">Interface synopsis<\/h2>/s',$content))
			$class->set_interface(true);
		// TODO
		$class->set_abstract(false);
		$class->set_final(false);
		
		if(preg_match_all('/<div class="fieldsynopsis">(.*?)<\/div>/s',$content,$matches))
		{
			foreach($matches[0] as $k => $v)
			{
				$obj = PC_PHPRef_Utils::parse_field_desc($matches[1][$k]);
				if($obj instanceof PC_Obj_Field)
					$class->add_field($obj);
				else if($obj instanceof PC_Obj_Constant)
					$class->add_constant($obj);
			}
		}
		if(preg_match_all('/<div class="constructorsynopsis dc-description">(.*?)<\/div>/s',$content,$matches))
		{
			foreach($matches[0] as $k => $v)
			{
				list($type,$classname,$method) = PC_PHPRef_Utils::parse_method_desc($matches[1][$k]);
				$class->add_method($method);
			}
		}
		if(preg_match_all('/<div class="methodsynopsis dc-description">(.*?)<\/div>/s',$content,$matches))
		{
			foreach($matches[0] as $k => $v)
			{
				list($type,$classname,$method) = PC_PHPRef_Utils::parse_method_desc($matches[1][$k]);
				$class->add_method($method);
			}
		}
		return $class;
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>