<?php
/**
 * The entry-point
 * 
 * @package			PHPCheck
 * @subpackage	main
 *
 * Copyright (C) 2008 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

include_once('config/actions.php');
include_once('config/userdef.php');
include_once(FWS_PATH.'init.php');
include_once('src/autoloader.php');
FWS_AutoLoader::register_loader('PC_autoloader');

define('PC_UNITTESTS',0);

// set error-handling
error_reporting((E_ALL | E_STRICT) & ~E_DEPRECATED);

// set our loader and accessor
$accessor = new PC_PropAccessor();
$accessor->set_loader(new PC_PropLoader());
FWS_Props::set_accessor($accessor);

// TODO don't expect doc comments for anonymous functions
// TODO we could detect if conditions are always true/false
// TODO introduce a phpdoc comment or so for unused parameters?
// TODO we could extend the type-hinting in doc-comments: array(int,ClassName,float)
// TODO detect missing parent::__construct calls for classes that have a superclass
// TODO detect if the liscovsche substitution law is violated?
// TODO the errors in the parallel-version should be appended to the document, not replaced

$doc = FWS_Props::get()->doc();
echo $doc->render();
