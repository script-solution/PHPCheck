<?php
/**
 * Contains the url-class
 * 
 * @package			PHPCheck
 * @subpackage	src
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
 * The URL-class for phpcheck. Provides some convenience methods
 * 
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class PC_URL extends FWS_URL
{
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
