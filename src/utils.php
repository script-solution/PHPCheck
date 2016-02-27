<?php
/**
 * Contains the utility-class
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
 * Some utility functions
 *
 * @package			PHPCheck
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class PC_Utils extends FWS_UtilBase
{
	/**
	 * @param int $pid the project-id
	 * @return bool true if the project-id is valid
	 */
	public static function is_valid_project_id($pid)
	{
		return $pid == PC_Project::PHPREF_ID || $pid > 0;
	}
	
	/**
	 * Helper to get the project-id to use for the database
	 * 
	 * @param int $pid the project-id you have
	 * @return int the real project-id to use
	 */
	public static function get_project_id($pid)
	{
		$project = FWS_Props::get()->project();
		if($pid == PC_Project::PHPREF_ID)
			return $pid;
		if($pid == PC_Project::CURRENT_ID)
		{
			if($project === null)
				return PC_Project::PHPREF_ID;
			return $project->get_id();
		}
		return $pid;
	}
	
	/**
	 * Highlights the given file
	 *
	 * @param string $file the file
	 * @param int $line optional the line to mark
	 * @return string the highlighted source
	 */
	public static function highlight_file($file,$line = 0)
	{
		if(is_file($file))
			$source = FWS_FileUtils::read($file);
		else
			$source = '';
		return self::highlight_string($source,1,$line);
	}
	
	/**
	 * Highlights the given string
	 *
	 * @param string $source the string
	 * @param int $start_line the first line (0 = display no lines)
	 * @param int $line optional the line to mark
	 * @param bool $links whether to use links for the line-numbers
	 * @return string the highlighted source
	 */
	public static function highlight_string($source,$start_line = 0,$line = 0,$links = true)
	{
		$decorator = new FWS_Highlighting_Decorator_HTML();
		$lang = new FWS_Highlighting_Language_XML('src/php.xml');
		$hl = new FWS_Highlighting_Processor($source,$lang,$decorator);
		$res = $hl->highlight();
		$lines = array();
		$x = $start_line;
		foreach(explode('<br />',$res) as $str)
		{
			$l = '';
			if($x == $line)
				$l = '<span style="background-color: #ffff00;">';
			if($start_line > 0)
			{
				if($links)
					$l .= '<a name="l'.$x.'" href="#l'.$x.'">';
				$l .= sprintf('%04d',$x);
				if($links)
					$l .= '</a>';
				$l .= '&nbsp;';
			}
			$l .= $str;
			if($x == $line)
				$l .= '</span>';
			$lines[] = $l;
			$x++;
		}
		return implode('<br />',$lines);
	}
	
	/**
	 * Returns all tokens from the given source
	 *
	 * @param string $source the source
	 * @return array the tokens: <code>array(array(<token>,<string>,<line>), ...)</code>
	 */
	public static function get_tokens($source)
	{
		$line = 1;
		$res = array();
		$tokens = token_get_all($source);
		foreach($tokens as $token)
		{
			if(is_array($token))
				list($t,$str,) = $token;
			else
			{
				$t = $token;
				$str = $token;
			}
			
			$res[] = array(
				$t,
				$str,
				$line
			);
			
			$lines = FWS_String::substr_count($str,"\n");
			if($lines >= 1)
				$line += $lines;
		}
		
		return $res;
	}
}
