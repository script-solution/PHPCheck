<?php
/**
 * Contains the url-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The URL-class for phpcheck. Provides some convenience methods
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_URL extends FWS_URL
{
	/**
	 * Builds an URL to the code of the given location
	 *
	 * @param PC_Obj_Location $loc
	 * @return string the URL
	 */
	public static function get_code_url($loc)
	{
		$file = $loc->get_file();
		$line = $loc->get_line();
		$classes = PC_DAO::get_classes()->get_by_file($file);
		if(count($classes) == 1)
			$url = PC_URL::get_mod_url('class')->set('name',$classes[0]->get_name());
		else
			$url = PC_URL::get_mod_url('file')->set('path',$file);
		$url->set_anchor('l'.$line);
		return $url->to_url();
	}
	
	/**
	 * Builds an URL for the given module.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return string the url
	 */
	public static function build_mod_url($mod = 0,$separator = '&amp;')
	{
		$url = self::get_mod_url($mod,$separator);
		return $url->to_url();
	}
	
	/**
	 * Builds an URL-instance for the given module.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @param boolean $force_sid forces the method to append the session-id
	 * @return PC_URL the url-instance
	 */
	public static function get_mod_url($mod = 0,$separator = '&amp;',$force_sid = false)
	{
		$url = new PC_URL();
		if($force_sid)
			$url->set_sid_policy(self::SID_FORCE);
		$url->set_separator($separator);

		if($mod === 0)
		{
			$input = FWS_Props::get()->input();
			$action = $input->get_var('module','get',FWS_Input::STRING);
			if($action != null)
				$url->set('module',$action);
		}
		else
			$url->set('module',$mod);
		
		return $url;
	}
	
	/**
	 * Builds an URL for the given submodule.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string|int $sub the submodule-name (0 = current)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return string the url
	 */
	public static function build_submod_url($mod = 0,$sub = 0,$separator = '&amp;')
	{
		$url = self::get_submod_url($mod,$sub,$separator);
		return $url->to_url();
	}
	
	/**
	 * Builds an URL-instance for the given submodule.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string|int $sub the submodule-name (0 = current)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @param boolean $force_sid forces the method to append the session-id
	 * @return PC_URL the url-instance
	 */
	public static function get_submod_url($mod = 0,$sub = 0,$separator = '&amp;',$force_sid = false)
	{
		$url = self::get_mod_url($mod,$separator,$force_sid);
		
		if($sub === 0)
		{
			$input = FWS_Props::get()->input();
			$sub = $input->get_var('sub','get',FWS_Input::STRING);
			if($sub != null)
				$url->set('sub',$sub);
		}
		else
			$url->set('sub',$sub);
		
		return $url;
	}
}
?>