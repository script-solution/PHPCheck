<?php
/**
 * Contains the function-parser
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Loads the given page from file and parses information about the described function
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PHPRef_Function extends FWS_Object
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
	 * @param string $file the file that describes the function
	 */
	public function __construct($file)
	{
		parent::__construct();
		$this->file = $file;
	}
	
	/**
	 * Fetches the page from the specified file and parses it for information about the function
	 * 
	 * @return array an array of the type and other information.
	 * @throws PC_PHPRef_Exception if it failed
	 */
	public function get_method()
	{
		$content = file_get_contents($this->file);
		$funcname = preg_replace('/^.*?function\.(.*?)\.html$/','\\1',$this->file);
		$funcname = str_replace('-','_',$funcname);
		
		// check wether this function is just an alias or deprecated
		if(preg_match('/<p class="refpurpose">(.*?)<\/p>/s',$content,$match))
		{
			$match = strip_tags($match[1]);
			$res = preg_match(
				'/'.preg_quote($funcname,'/').'\s*&mdash;\s*Alias\s*of\s*'
				.'(?:([a-zA-Z0-9_]+)(?:::|->))?([a-zA-Z0-9_]+)/s',
				$match,$alias
			);
			if($res)
				return array('alias',$funcname,$alias[1],$alias[2]);
			if(preg_match('/\[deprecated\]/',$match))
				return array('deprecated',$funcname);
		}
		
		// find method-description
		$res = preg_match_all(
			'/<div class="(?:methodsynopsis|constructorsynopsis) dc-description">(.*?)<\/div>/s',
			$content,$matches
		);
		if(!$res)
			throw new PC_PHPRef_Exception('Unable to find method-description');
		
		// find version-information
		$version = '';
		if(preg_match('/<p class="verinfo">\s*\((.*?)\)\s*<\/p>/',$content,$vmatch))
			$version = trim($vmatch[1]);
		
		$methods = array();
		foreach($matches[0] as $k => $v)
			$methods[] = PC_PHPRef_Utils::parse_method_desc($matches[1][$k]);
		$version = PC_PHPRef_Utils::parse_version($version);
		
		// if we've found more than one synopsis, we have to merge them into one. this is, of course,
		// not perfect, but adding multiple methods with the same name would break the current concept
		if(count($methods) > 1)
		{
			$method = PC_PHPRef_Utils::merge_methods($methods);
			$method->set_since($version);
			return array($methods[0][0],$methods[0][1],$method);
		}
		$methods[0][2]->set_since($version);
		return $methods[0];
	}
	
	protected function get_dump_vars()
	{
		return get_object_vars($this);
	}
}
?>