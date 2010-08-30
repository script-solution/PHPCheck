<?php
/**
 * Contains the function-parser
 *
 * @version			$Id: dao.php 23 2008-12-13 11:07:36Z nasmussen $
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
final class PC_PHPRef_Function
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
		$this->file = $file;
	}
	
	/**
	 * Fetches the page from the specified file and parses it for information about the function
	 * 
	 * @return PC_Obj_Method the method that was found
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
		$res = preg_match(
			'/<div class="(?:methodsynopsis|constructorsynopsis) dc-description">(.*?)<\/div>/s',
			$content,$match
		);
		if(!$res)
			throw new PC_PHPRef_Exception('Unable to find method-description in "'.$this->file.'"!');
		
		// find version-information
		$version = '';
		if(preg_match('/<p class="verinfo">\s*\((.*?)\)\s*<\/p>/',$content,$vmatch))
			$version = trim($vmatch[1]);
		return $this->parse_method_desc($match[1],$version);
	}
	
	/**
	 * Parses a method-description into a PC_Obj_Method
	 * 
	 * @param string $desc the description
	 * @param string $version the version-description
	 * @return array an array of the class-name and the PC_Obj_Method
	 * @throws PC_PHPRef_Exception if it failed
	 */
	private function parse_method_desc($desc,$version)
	{
		$classname = '';
		// prepare description
		$desc = trim(strip_tags($desc));
		$desc = FWS_StringHelper::htmlspecialchars_back($desc);
		// filter out modifier, return-type, name and params
		$res = preg_match(
			// first modifier
			'/^(?:(static|final|public|protected)\s*)?'
			// second modifier
			.'(?:(static|final|public|protected)\s*)?'
			// third modifier
			.'(?:(static|final|public|protected)\s*)?'
			// return-type
			.'(\S+)?\s*'
			// class- and function-name
			.'([a-zA-Z0-9_:\-\>]+)\s*\((.*?)\)$/s',$desc,$match
		);
		if(!$res)
			throw new PC_PHPRef_Exception('Unable to parse "'.$desc.'" in "'.$this->file.'"');
		list(,$modifier1,$modifier2,$modifier3,$return,$name,$params) = $match;
		
		// detect class-names
		if(($pos = strpos($name,'::')) !== false || ($pos = strpos($name,'->')) !== false)
		{
			$classname = substr($name,0,$pos);
			$name = substr($name,$pos + 2);
		}
		
		// build basic method
		$method = new PC_Obj_Method('',0,true);
		if($modifier1 == 'static' || $modifier2 == 'static' || $modifier3 == 'static')
			$method->set_static(true);
		if($modifier1 == 'final' || $modifier2 == 'final' || $modifier3 == 'final')
			$method->set_final(true);
		if(in_array($modifier1,array('private','protected')))
			$method->set_visibility($modifier1);
		else if(in_array($modifier2,array('private','protected')))
			$method->set_visibility($modifier2);
		else if(in_array($modifier3,array('private','protected')))
			$method->set_visibility($modifier3);
		if($return != 'void')
			$method->set_return_type(PC_Obj_Type::get_type_by_name($return));
		$method->set_name($name);
		
		// parse version-information
		if($version)
		{
			$versions = explode(',',$version);
			natsort($versions);
			$lowest = $versions[0];
			if(($pos = strpos($lowest,'PHP')) !== false)
			{
				$lowest = substr($lowest,$pos + 3);
				if(($pos = strpos($lowest,'&lt;=')) !== false)
					$lowest = '-'.trim(substr($lowest,$pos + 5));
				else if(($pos = strpos($lowest,'&gt;=')) !== false)
					$lowest = '+'.trim(substr($lowest,$pos + 5));
				else
					$lowest = '+'.trim($lowest);
			}
			else
				$lowest = '';
			$method->set_since($lowest);
		}
		
		// check what kind of params we have
		$optional = '';
		$firstopt = strpos($params,'[');
		if($firstopt !== false)
		{
			$required = substr($params,0,$firstopt);
			$optional = substr($params,$firstopt + 1);
			$optional = str_replace(array('[',']'),'',$optional);
		}
		else
			$required = $params;
		
		// add required ones
		$required = trim($required);
		if($required && $required != 'void')
		{
			$reqparts = explode(', ',$required);
			foreach($reqparts as $part)
			{
				list($type,$name) = explode(' ',trim($part));
				$param = new PC_Obj_Parameter();
				$param->set_name(trim($name));
				$param->set_mtype(new PC_Obj_MultiType(array(PC_Obj_Type::get_type_by_name(trim($type)))));
				$method->put_param($param);
			}
		}
		
		// add optional ones
		$optional = trim($optional);
		if($optional)
		{
			$optparts = explode(', ',$optional);
			foreach($optparts as $part)
			{
				$part = trim($part);
				if($part == '')
					continue;
				$default = null;
				$param = new PC_Obj_Parameter();
				$param->set_optional(true);
				// has it a known default-value?
				if(($pos = strpos($part,'=')) !== false)
				{
					$nametype = trim(substr($part,0,$pos));
					$default = trim(substr($part,$pos + 1));
					$parts = preg_split('/\s+/',$nametype);
					if(count($parts) != 2)
						throw new PC_PHPRef_Exception(
							'Parameter description has not 2 parts: "'.$nametype.'" in "'.$this->file.'"');
					list($type,$name) = $parts;
				}
				else
				{
					// variable arguments are simply skipped; TODO actually we should store that in some way
					if(strpos($part,'...') !== false)
						continue;
					$parts = preg_split('/\s+/',$part);
					if(count($parts) != 2)
						throw new PC_PHPRef_Exception(
							'Parameter description has not 2 parts: "'.$part.'" in "'.$this->file.'"');
					list($type,$name) = $parts;
				}
				$param->set_name(trim($name));
				$otype = PC_Obj_Type::get_type_by_name(trim($type));
				if($default !== null)
					$otype->set_value($default);
				$param->set_mtype(new PC_Obj_MultiType(array($otype)));
				$method->put_param($param);
			}
		}
		return array('func',$classname,$method);
	}
}
?>