<?php
/**
 * The entry-point
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	main
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

include_once('config/actions.php');
include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

// TODO use abstract, final for methods
// TODO watch out if abstract methods are called! (parent::abstractMethod())
// TODO use static for fields
// TODO handle case-sensitivy correctly
// TODO handle list() so that it declares the variables as unknown?
// TODO type-hinting in catch-blocks is not supported yet
// FIXME the parameters of BS_User_Current->login() are wrong
// FIXME parameters like $foo = array(1 => 'true') are not recognized correctly
// FIXME octal numbers are not detected correctly?
// TODO type-hinting for array is not supported
// TODO class-fields don't store the value when its an array?
// TODO is it possible to get the required types of builtin functions etc.?

$doc = FWS_Props::get()->doc();
echo $doc->render();
?>