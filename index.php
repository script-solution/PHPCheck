<?php
define('FWS_PATH','../PHPLib/');
include_once(FWS_PATH.'init.php');

/**
 * TODO: describe the file
 *
 * @version			$Id$
 * @package			Boardsolution
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

include_once('location.php');
include_once('typescanner.php');
include_once('actionscanner.php');
include_once('class.php');
include_once('method.php');
include_once('variable.php');
include_once('utils.php');
include_once('call.php');
include_once('type.php');

// TODO use abstract, final for classes and methods
// TODO use static for methods and fields
// TODO add parameter to local vars
// TODO support optional parameters

define('USE_CACHE',false);

$file = 'test.php';
//$file = '../PHPLib/email/base.php';
//$file = '../PHPLib/html/formular.php';
//$file = '../Boardsolution/acp/module/acpaccess/action_client.php';
//$file = '../Boardsolution/src/propaccessor.php';
//$file = '../Boardsolution/src/auth.php';

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
	foreach($cache as $data)
	{
		if(@list($mtime,$f,$c) = $data)
		{
			$functions = array_merge($functions,$f);
			$classes = array_merge($classes,$c);
		}
	}
	
	// check for changes
	$changed = false;
	foreach(array('../PHPLib','../Boardsolution') as $dir)
	{
		$files = FWS_FileUtils::get_dir_content($dir,true,true);
		foreach($files as $file)
		{
			if(!preg_match('/\.php$/',$file))
				continue;
			
			// do we have to re-parse the file?
			if(!isset($cache[$file]) || $cache[$file][0] < filemtime($file))
			{
				$tscanner = new PC_TypeScanner();
				$tscanner->scan_file($file);
				$functions = array_merge($functions,$tscanner->get_functions());
				$classes = array_merge($classes,$tscanner->get_classes());
				$cache[$file] = array(time(),$tscanner->get_functions(),$tscanner->get_classes());
				$changed = true;
			}
			//$ascanner->scan_file($file);
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
	$tscanner->scan_file($file);
	$tscanner->finish();
	
	$functions = $tscanner->get_functions();
	$classes = $tscanner->get_classes();
}

$ascanner = new PC_ActionScanner();
$ascanner->scan_file($file,$functions,$classes);
$vars = $ascanner->get_vars();
$calls = $ascanner->get_calls();

//echo $classes['BS_PropAccessor'];
echo FWS_PrintUtils::to_string(array(
	///*'funcs' => $functions,'classes' => array_keys($classes),*/'vars' => $vars,'calls' => $calls
	'funcs' => $functions,'classes' => $classes,'vars' => $vars,'calls' => $calls
));

// check function-calls
foreach($calls as $call)
{
	/* @var $call PC_Call */
	$name = $call->get_function();
	$obj = $call->get_class();
	if($obj !== null)
	{
		if($obj)
		{
			if($obj[0] == '$' && isset($vars[$obj]))
				$class = $vars[$obj];
			else
				$class = $obj;
			
			if(isset($classes[$class]))
			{
				$c = $classes[$class];
				/* @var $c PC_Class */
				if(!$c->contains_method($name))
					warning('The method "'.$name.'" does not exist in the class "'.$class.'"!',$call);
				else if($name == '__construct' && $c->is_abstract())
					error('You can\'t instantiate an abstract class!',$call);
			}
			else
				warning('The class "'.$class.'" does not exist!',$call);
		}
	}
	else
	{
		if(!isset($functions[$name]) && !function_exists($name))
			warning('The function "'.$name.'" does not exist!',$call);
		else if(isset($functions[$name]))
		{
			$f = $functions[$name];
			$params = $f->get_params();
			$cparams = $call->get_arguments();
			/* @var $f PC_Method */
			if(count($params) != count($cparams))
			{
				warning('The function "'.$name.'" requires '.count($params)
					.' arguments but you have given '.count($cparams),$call);
			}
			else
			{
				$i = 0;
				foreach($params as $param)
				{
					if(!$param->get_type()->equals($cparams[$i]))
						parameter_type_warning($i,$name,$param->get_type(),$cparams[$i],$call);
					$i++;
				}
			}
		}
	}
}

// check classes for issues
foreach($classes as $class)
{
	/* @var $class PC_Class */
	
	// test wether abstract is used in a usefull way
	$abstractcount = 0;
	foreach($class->get_methods() as $method)
	{
		if($method->is_abstract())
			$abstractcount++;
	}
	
	if($class->is_abstract() && $abstractcount == 0)
		warning('The class "'.$class->get_name().'" is abstract but has no abstract method!',$class);
	else if(!$class->is_abstract() && $abstractcount > 0)
		error('The class "'.$class->get_name().'" is NOT abstract but contains abstract methods!',$class);
	
	// super-class final?
	if($class->get_super_class() !== null)
	{
		$sclass = $classes[$class->get_super_class()];
		if($sclass->is_final())
			error('The class "'.$class->get_name().'" inherits from the final class "'
				.$sclass->get_name().'"!',$class);
	}
}

function parameter_type_warning($i,$name,$tactual,$trequired,$call)
{
	warning('The argument '.$i.' for "'.$name.'" requires '.get_article($tactual).' "'.$tactual
							.'" but you have given '.get_article($trequired).' "'.$trequired.'"',$call);
}

function get_article($type)
{
	$str = $type->__ToString();
	return in_array($str[0],array('i','a','o','u','e')) ? 'an' : 'a';
}

function error($msg,$loc)
{
	echo '['.$loc->get_file().', '.$loc->get_line().'] <b>Error:</b> '.$msg.'<br />';
}

function warning($msg,$loc)
{
	echo '['.$loc->get_file().', '.$loc->get_line().'] <b>Warning:</b> '.$msg.'<br />';
}
?>