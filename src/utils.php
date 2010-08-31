<?php
/**
 * Contains the utility-class
 *
 * @version			$Id$
 * @package			PHPCheck
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * Some utility functions
 *
 * @package			PHPCheck
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
	 * Generates the parser
	 */
	public static function generate()
	{
		if(!is_file('src/compile/parser.php') ||
			filemtime('src/compile/parser.y') > filemtime('src/compile/parser.php'))
		{
			exec('php /usr/share/php/PHP/ParserGenerator/cli.php src/compile/parser.y');
		}
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
		
		$decorator = new FWS_Highlighting_Decorator_HTML();
		$lang = new FWS_Highlighting_Language_XML('php.xml');
		$hl = new FWS_Highlighting_Processor($source,$lang,$decorator);
		$res = $hl->highlight();
		$lines = array();
		$x = 1;
		foreach(explode('<br />',$res) as $str)
		{
			$l = '<a name="l'.$x.'" href="#l'.$x.'">'.sprintf('%04d',$x).'</a>&nbsp;';
			if($x == $line)
				$l .= '<span style="background-color: #ffff00;">'.$str.'</span>';
			else
				$l .= $str;
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
?>