<?php
/**
 * Contains utils for the php-ref-parser
 * 
 * @package			PHPCheck
 * @subpackage	src.phpref
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
 * Utility-methods for the php-ref-parser
 * 
 * @package			PHPCheck
 * @subpackage	src.phpref
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_PHPRef_Utils extends FWS_UtilBase
{
	/**
	 * Merges the given methods into one
	 * 
	 * @param array $methods the methods
	 * @return PC_Obj_Method the merged method
	 */
	public static function merge_methods($methods)
	{
		list(,,$first) = $methods[0];
		$method = new PC_Obj_Method('',0,true);
		$method->set_name($first->get_name());
		$method->set_visibility($first->get_visibility());
		$method->set_static($first->is_static());
		$method->set_final($first->is_final());
		$method->set_abstract($first->is_abstract());
		$mobjs = array();
		foreach($methods as $m)
			$mobjs[] = $m[2];
		foreach(self::merge_params($mobjs) as $param)
			$method->put_param($param);
		return $method;
	}
	
	/**
	 * Merges the parameters of all given methods
	 * 
	 * @param array $methods the methods
	 * @return array an array of PC_Obj_Parameter's
	 */
	private static function merge_params($methods)
	{
		$merged = array();
		$mparams = array();
		foreach($methods as $m)
			$mparams[] = array_values($m->get_params());
		for($i = 0; ; $i++)
		{
			$name = '';
			$optional = false;
			$firstvar = false;
			$mtypes = array();
			for($j = 0; $j < count($methods); $j++)
			{
				if($i < count($mparams[$j]))
				{
					$mtypes[] = $mparams[$j][$i]->get_mtype();
					$name = $mparams[$j][$i]->get_name();
					if($mparams[$j][$i]->is_optional())
						$optional = true;
					if($mparams[$j][$i]->is_first_vararg())
						$firstvar = true;
				}
			}
			if(count($mtypes) == 0)
				break;
			$mtype = self::merge_types($mtypes);
			$param = new PC_Obj_Parameter();
			// the name doesn't really matter. to be safe, use a unique name for each param
			$param->set_name('param'.$i);
			$param->set_mtype($mtype);
			// implicit optional if its not required for all methods
			$param->set_optional(count($mtypes) < count($methods) || $optional);
			$param->set_first_vararg($firstvar);
			$merged[] = $param;
		}
		return $merged;
	}
	
	/**
	 * Merges the given multi-types into one
	 * 
	 * @param array $mtypes an array of PC_Obj_MultiType
	 * @return PC_Obj_MultiType the merged type
	 */
	private static function merge_types($mtypes)
	{
		$types = array();
		foreach($mtypes as $mt)
		{
			if($mt->is_unknown())
				return new PC_Obj_MultiType();
			foreach($mt->get_types() as $t)
				$types[$t->get_type()] = $t;
		}
		return new PC_Obj_MultiType(array_values($types));
	}
	
	/**
	 * Parses the given version
	 * 
	 * @param string $version the version
	 * @return string the parsed version
	 */
	public static function parse_version($version)
	{
		$lowest = '';
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
		}
		return $lowest;
	}
	
	/**
	 * Parses the given field-description
	 * 
	 * @param string $desc the description
	 * @return PC_Obj_Field|PC_Obj_Constant the field or constant
	 * @throws PC_PHPRef_Exception if it fails
	 */
	public static function parse_field_desc($desc)
	{
		// prepare description
		$desc = trim(strip_tags($desc));
		$desc = FWS_StringHelper::htmlspecialchars_back($desc);
		// filter out modifier, return-type, name and params
		$match = array();
		$res = preg_match(
			// first modifier
			'/^(?:(const|readonly|static|public|protected|private)\s*)?'
			// second modifier
			.'(?:(const|readonly|static|public|protected|private)\s*)?'
			// third modifier
			.'(?:(const|readonly|static|public|protected|private)\s*)?'
			// type
			.'(?:(\S+)\s+)?'
			// class- and function-name
			.'\$?([a-zA-Z0-9_:]+)\s*(?:=\s*([^'."\n".';]+)\s*)?;$/s',$desc,$match
		);
		if(!$res)
			throw new PC_PHPRef_Exception('Unable to parse "'.$desc.'"');
		list(,$modifier1,$modifier2,$modifier3,$type,$name) = $match;
		
		if($modifier1 == 'const' || $modifier2 == 'const' || $modifier3 == 'const')
			$field = new PC_Obj_Constant('',0,$name);
		else
		{
			$field = new PC_Obj_Field('',0,$name);
			if($modifier1 == 'static' || $modifier2 == 'static' || $modifier3 == 'static')
				$field->set_static(true);
			if(in_array($modifier1,array('private','protected')))
				$field->set_visibility($modifier1);
			else if(in_array($modifier2,array('private','protected')))
				$field->set_visibility($modifier2);
			else if(in_array($modifier3,array('private','protected')))
				$field->set_visibility($modifier3);
		}
		if($type)
			$field->set_type(PC_Obj_MultiType::get_type_by_name($type));
		if(isset($match[6]) && $match[6] !== '' && !$field->get_type()->is_multiple())
		{
			if($type)
				$field->get_type()->get_first()->set_value($match[6]);
			else
				$field->set_type(new PC_Obj_MultiType(PC_Obj_Type::get_type_by_value($match[6])));
		}
		return $field;
	}
	
	/**
	 * Parses a method-description into a PC_Obj_Method
	 * 
	 * @param string $desc the description
	 * @return array an array of the class-name and the PC_Obj_Method
	 * @throws PC_PHPRef_Exception if it failed
	 */
	public static function parse_method_desc($desc)
	{
		$classname = '';
		// prepare description
		$desc = trim(strip_tags($desc));
		$desc = FWS_StringHelper::htmlspecialchars_back($desc);
		// filter out modifier, return-type, name and params
		$match = array();
		$res = preg_match(
			// first modifier
			'/^(?:(abstract|static|final|public|protected|private)\s*)?'
			// second modifier
			.'(?:(abstract|static|final|public|protected|private)\s*)?'
			// third modifier
			.'(?:(abstract|static|final|public|protected|private)\s*)?'
			// return-type
			.'(?:(\S+)\s+)?'
			// class- and function-name
			.'([a-zA-Z0-9_:\-\>]+)\s*\((.*?)\)$/s',$desc,$match
		);
		if(!$res)
			throw new PC_PHPRef_Exception('Unable to parse "'.$desc.'"');
		list(,$modifier1,$modifier2,$modifier3,$return,$name,$params) = $match;
		
		// detect class-names
		if(($pos = strpos($name,'::')) !== false || ($pos = strpos($name,'->')) !== false)
		{
			$classname = substr($name,0,$pos);
			$name = substr($name,$pos + 2);
		}
		
		// build basic method
		$method = new PC_Obj_Method('',0,$classname != '');
		if($modifier1 == 'static' || $modifier2 == 'static' || $modifier3 == 'static')
			$method->set_static(true);
		if($modifier1 == 'final' || $modifier2 == 'final' || $modifier3 == 'final')
			$method->set_final(true);
		if($modifier1 == 'abstract' || $modifier2 == 'abstract' || $modifier3 == 'abstract')
			$method->set_abstract(true);
		if(in_array($modifier1,array('private','protected')))
			$method->set_visibility($modifier1);
		else if(in_array($modifier2,array('private','protected')))
			$method->set_visibility($modifier2);
		else if(in_array($modifier3,array('private','protected')))
			$method->set_visibility($modifier3);
		if($return && $return != 'void')
		{
			$method->set_return_type(PC_Obj_MultiType::get_type_by_name($return));
			// set this always for builtin types since it makes no sense to report errors for
			// inherited classes or similar
			$method->set_has_return_doc(true);
		}
		$method->set_name($name);
		
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
				$param->set_mtype(self::get_param_type($type));
				$param->set_has_doc(true);
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
							'Parameter description has not 2 parts: "'.$nametype.'"');
					list($type,$name) = $parts;
				}
				else
				{
					// detect variable arguments
					if(strpos($part,'...') !== false)
					{
						$param->set_first_vararg(true);
						// sometimes there is a space bewteen $ and ...
						$part = preg_replace('/\$\s+\.\.\./','$...',$part);
					}
					$parts = preg_split('/\s+/',$part);
					if(count($parts) != 2)
						throw new PC_PHPRef_Exception(
							'Parameter description has not 2 parts: "'.$part.'"');
					list($type,$name) = $parts;
				}
				$param->set_name(trim($name));
				$param->set_mtype(self::get_param_type($type,$default));
				$param->set_has_doc(true);
				$method->put_param($param);
			}
		}
		return array('func',$classname,$method);
	}
	
	/**
	 * Determines the multi-type from given type-name and default-value
	 * 
	 * @param string $type the type-name
	 * @param string $default the default-value
	 * @return PC_Obj_MultiType the multitype
	 */
	private static function get_param_type($type,$default = null)
	{
		$type = trim($type);
		// "callback" is a pseudo-type that may be an array (with classname and funcname) or
		// a string (the funcname)
		if(strcasecmp($type,'callback') == 0)
		{
			return new PC_Obj_MultiType(array(
				new PC_Obj_Type(PC_Obj_Type::TARRAY),
				new PC_Obj_Type(PC_Obj_Type::STRING)
			));
		}
		
		$otype = PC_Obj_MultiType::get_type_by_name(trim($type));
		if(!$otype->is_unknown() && !$otype->is_multiple() &&
			$default !== null && strcasecmp($default,'null') != 0)
		{
			if($default == 'array()')
				$otype->get_first()->set_value(array());
			else
				$otype->get_first()->set_value($default);
		}
		return $otype;
	}
}
