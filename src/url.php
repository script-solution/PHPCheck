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
	 * Works the same like get_url but is mainly intended for usage in the templates.
	 * You can use the following shortcut for the constants (in <var>$additional</var>):
	 * <code>$<name></code>
	 * This will be mapped to the constant:
	 * <code><constants_prefix><name></code>
	 * Note that the constants will be assumed to be in uppercase!
	 * 
	 * @param string $target the action-parameter (0 = current, -1 = none)
	 * @param string $additional additional parameters
	 * @param string $separator the separator of the params (default is &amp;)
	 * @param boolean $force_sid forces the method to append the session-id
	 * @return string the url
	 */
	public static function simple_url($target = 0,$additional = '',$separator = '&amp;',
		$force_sid = false)
	{
		if($additional != '')
			$additional = preg_replace('/\$([a-z0-9_]+)/ie','TDL_\\1',$additional);
		return self::get_url($target,$additional,$separator,$force_sid);
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