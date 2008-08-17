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
	 * Retrieves all data from the corresponding files and returns them.
	 * TODO just temporary ;)
	 *
	 * @return array
	 */
	public static function get_data()
	{
		define('DEBUG_MODE',false);
		define('USE_CACHE',!DEBUG_MODE);
		define('REPORT_MIXED',DEBUG_MODE);
		define('REPORT_UNKNOWN',DEBUG_MODE);
		
		$files = array(
			//'test.php',
			//'test2.php',
			//'../PHPLib/email/base.php',
			//'../PHPLib/html/formular.php',
			//'../Boardsolution/acp/module/acpaccess/sub_client.php',
			//'../Boardsolution/src/propaccessor.php',
			//'../Boardsolution/src/auth.php',
		);
		if(DEBUG_MODE)
			$files[] = 'test2.php';
		else
		{
			foreach(FWS_FileUtils::get_dir_content('../Boardsolution/front/module/calendar',true,true) as $item)
			{
				if(preg_match('/\.php$/',$item) && strpos($item,'.svn/') === false)
					$files[] = $item;
			}
		}
		
		if(USE_CACHE)
		{
			// read cache
			$scache = FWS_FileUtils::read('cache.php');
			$cache = @unserialize($scache);
			if(!is_array($cache))
				$cache = array();
			
			// build our functions and classes from the cache
			$functions = array();
			$classes = array();
			$constants = array();
			foreach($cache as $data)
			{
				if(@list(,$f,$c,$con) = $data)
				{
					$functions = array_merge($functions,$f);
					$classes = array_merge($classes,$c);
					$constants = array_merge($constants,$con);
				}
			}
			
			// check for changes
			$changed = false;
			foreach(array('../PHPLib','../Boardsolution') as $dir)
			{
				$dirfiles = FWS_FileUtils::get_dir_content($dir,true,true);
				foreach($dirfiles as $filename)
				{
					if(!preg_match('/\.php$/',$filename) || FWS_String::strpos($filename,'.svn/') !== false)
						continue;
					
					// do we have to re-parse the file?
					if(!isset($cache[$filename]) || $cache[$filename][0] < filemtime($filename))
					{
						$tscanner = new PC_TypeScanner();
						$tscanner->scan_file($filename);
						$functions = array_merge($functions,$tscanner->get_functions());
						$classes = array_merge($classes,$tscanner->get_classes());
						$constants = array_merge($constants,$tscanner->get_constants());
						$cache[$filename] = array(
							time(),$tscanner->get_functions(),$tscanner->get_classes(),$tscanner->get_constants()
						);
						$changed = true;
					}
					//$ascanner->scan_file($filename);
				}
			}
			
			// write back to cache
			if($changed)
			{
				$tscanner = new PC_TypeScanner();
				$tscanner->finish($classes);
				FWS_FileUtils::write('cache.php',serialize($cache));
			}
		}
		else
		{
			$tscanner = new PC_TypeScanner();
			foreach($files as $file)
				$tscanner->scan_file($file);
			$tscanner->finish();
			
			$functions = $tscanner->get_functions();
			$classes = $tscanner->get_classes();
			$constants = $tscanner->get_constants();
		}
		
		// scan files for function-calls and variables
		$ascanner = new PC_ActionScanner();
		foreach($files as $file)
			$ascanner->scan_file($file,$functions,$classes,$constants);
		$vars = $ascanner->get_vars();
		$calls = $ascanner->get_calls();
		
		return array($constants,$functions,$classes,$vars,$calls);
	}
	
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
			
			$lines = FWS_String::substr_count($str,"\n");
			if($lines >= 1)
				$line += $lines;
		}
		
		return $res;
	}
}
?>