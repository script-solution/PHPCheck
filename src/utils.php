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
			$l = '<a name="l'.$x.'" href="#l'.$x.'">'.sprintf('%4d',$x).'</a>&nbsp;';
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
	 * Retrieves all data from the corresponding files and returns them.
	 * TODO just temporary ;)
	 *
	 * @return array
	 */
	public static function get_data()
	{
		$input = FWS_Props::get()->input();
		define('DEBUG_MODE',$input->isset_var('debug','get'));
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
			$files[] = 'test.php';
		else
		{
			foreach(FWS_FileUtils::get_dir_content('../Boardsolution/install/',true,true) as $item)
			{
				if(preg_match('/\.php$/',$item) && strpos($item,'.svn/') === false &&
						strpos($item,'/cache/') === false && strpos($item,'/tests/') === false)
					$files[] = $item;
			}
			/*foreach(FWS_FileUtils::get_dir_content('../Boardsolution/',true,true) as $item)
			{
				if(preg_match('/\.php$/',$item) && strpos($item,'.svn/') === false &&
						strpos($item,'/cache/') === false && strpos($item,'/tests/') === false)
					$files[] = $item;
			}*/
		}
		
		/*if(USE_CACHE)
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
		}*/
		
		// scan files for function-calls and variables
		$types = new PC_TypeContainer();
		$ascanner = new PC_StatementScanner();
		foreach($files as $file)
			$ascanner->scan_file($file,$types);
		
		return array($types,$ascanner->get_vars(),$ascanner->get_calls());
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