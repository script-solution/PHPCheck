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
 * Path to FrameWorkSolution with trailing slash
 */
define('FWS_PATH','../FrameWorkSolution/');
/**
 * The version
 */
define('PC_VERSION','PHPCheck v1.0');
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

/**
 * The number of items per cycle in the analyzer
 */
define('PC_ANALYZE_ITEMS_PER_CYCLE',5000);

/**
 * The number of pages to parse per cycle
 */
define('PC_PHPREF_PAGES_PER_CYCLE',500);
?>