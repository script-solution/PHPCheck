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

define('FWS_PATH','../PHPLib/');
include_once(FWS_PATH.'init.php');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

// set our loader
FWS_Props::get()->set_loader(new PC_PropLoader());

// TODO use abstract, final for methods
// TODO use static for fields
// TODO handle case-sensitivy correctly
// TODO handle list() so that it declares the variables as unknown?
// TODO type-hinting in catch-blocks is not supported yet
// FIXME the parameters of BS_User_Current->login() are wrong
// FIXME parameters like $foo = array(1 => 'true') are not recognized correctly
// FIXME octal numbers are not detected correctly?

$doc = FWS_Props::get()->doc();
echo $doc->render();
?>