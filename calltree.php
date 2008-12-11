<?php
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

define('XML_FILE','../phpcheck_perf/result.xml');

define('FWS_PATH','../PHPLib/');
include(FWS_PATH.'init.php');

$contents = FWS_FileUtils::read(XML_FILE);
$xml = new SimpleXMLElement($contents);

foreach($xml->functionCalls as $funcCall)
{
	echo $funcCall."<br>";
}
?>