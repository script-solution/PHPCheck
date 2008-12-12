<?php
/**
 * Contains constants that may be changed by the user
 *
 * @version			$Id$
 * @package			PHPCheck
 * @subpackage	config
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2008 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The number of entries per page for lists
 */
define('PC_ENTRIES_PER_PAGE',20);

/**
 * The number of files per cycle in the type-scanner
 */
define('PC_TYPE_FILES_PER_CYCLE',300);

/**
 * The number of files per cycle in the statement-scanner
 */
define('PC_STMT_FILES_PER_CYCLE',100);
?>