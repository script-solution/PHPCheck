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

// set error-handling
error_reporting((E_ALL | E_STRICT) & ~E_DEPRECATED);

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

// TODO type-hinting in catch-blocks is not supported yet
// TODO we could detect if conditions are always true/false
// TODO perhaps we could detect unused variables?
// TODO we could check return-values. i.e. if the specified types are returned. or if there is no
// value-return at all, etc.
// TODO we could check thrown exceptions. i.e. check if @throws is present and if it specifies
// the thrown exceptions, etc.
// TODO handle "void" special?
// TODO detect calls of private/protected methods
// TODO we could extend the type-hinting in doc-comments: array(int,ClassName,float)
// TODO we could check params with default-value, that don't specify that in the type
// TODO the method-links in calls are wrong if the call belongs to a super-class
// TODO detect missing parent::__construct calls for classes that have a superclass
// TODO detect if the liscovsche substitution law is violated?
// TODO the errors in the parallel-version should be appended to the document, not replaced
// TODO why can't we find the front-action-base when searching for Action_Base??
// TODO detecting return-values in nested functions does not work

$doc = FWS_Props::get()->doc();
echo $doc->render();
?>