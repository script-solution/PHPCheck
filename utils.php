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
	 * Determines the return-type of the given function / class
	 *
	 * @param array $funcs an array of all known functions
	 * @param array $classes an array of all known classes
	 * @param string $function the function-name
	 * @param string $class optional, the class-name
	 * @return PC_Type the type
	 */
	public static function get_return_type($funcs,$classes,$function,$class = '')
	{
		if(!$class)
		{
			if(isset($funcs[$function]))
				return $funcs[$function]->get_return_type();
		}
		else
		{
			if(isset($classes[$class]))
			{
				$cfuncs = $classes[$class]->get_methods();
				if(isset($cfuncs[$function]))
					return $cfuncs[$function]->get_return_type();
			}
		}
		return PC_Type::$UNKNOWN;
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
			
			$lines = substr_count($str,"\n");
			if($lines >= 1)
				$line += $lines;
		}
		
		return $res;
	}
}
?>