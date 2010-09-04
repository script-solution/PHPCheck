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

// TODO handle list() so that it declares the variables as unknown?
// TODO type-hinting in catch-blocks is not supported yet
// FIXME the parameters of BS_User_Current->login() are wrong
// TODO is it possible to get the required types of builtin functions etc.?
// TODO we should recognize /* @var $<var> <type> */ as type-hints
// TODO if a method does not exist, we could look for subclasses of the class and check if the
// method exists there. this is a bit guessing of course, but the user could enable/disable this.
// TODO we could detect if conditions are always true/false
// TODO perhaps we could detect unused variables?
// TODO we could detect unknown variables
// TODO we could check return-values. i.e. if the specified types are returned. or if there is no
// value-return at all, etc.
// TODO we could check thrown exceptions. i.e. check if @thrown is present and if it specifies
// the thrown exceptions, etc.
// TODO we can't handle "null" (we detect it as string)
// TODO handle "void" special?

$doc = FWS_Props::get()->doc();
echo $doc->render();
?>