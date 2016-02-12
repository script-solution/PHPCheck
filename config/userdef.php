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
define('FWS_PATH','fws/');

/**
 * The number of jobs you want to execute in parallel. Since our tasks can be easily
 * run in parallel, it makes sense to use separate processes (via proc_open()) for them. This way
 * we can take advantage of multiple cores/cpus.
 * 
 * With this setting you can specify how many processes should run in parallel. You can also set
 * '0' to indicate that you want to use the 'traditional' way. I.e. don't use AJAX to start the
 * jobs but do that with PHP and process a bunch of files per request.
 * 
 * If PC_PARALLEL_JOB_COUNT is not zero, PC_*_PER_CYCLE sets the number of files/items to execute
 * per process. I.e. each process gets PC_*_PER_CYCLE arguments and processes them.
 * Otherwise its is the number of files/items to execute per cycle/request.
 */
define('PC_PARALLEL_JOB_COUNT',8);
/**
 * If PC_PARALLEL_JOB_COUNT is not zero, you can specify here the number of microseconds that
 * the job-control-request should wait until each check for finished processes. Additionally
 * this specifies the number of microseconds between the AJAX-requests that check the status,
 * as well.
 */
define('PC_JOB_CTRL_POLL_INTERVAL',100000);

/**
 * The number of files per process/cycle in the type-scanner
 */
define('PC_TYPE_FILES_PER_CYCLE',20);
/**
 * The number of files per process/cycle in the statement-scanner
 */
define('PC_STMT_FILES_PER_CYCLE',20);
/**
 * The number of items per cycle in the analyzer (not yet used by the parallel version)
 */
define('PC_ANALYZE_ITEMS_PER_CYCLE',8000);
/**
 * The number of pages to parse per process/cycle
 */
define('PC_PHPREF_PAGES_PER_CYCLE',100);

/**
 * The number of entries per page for lists
 */
define('PC_ENTRIES_PER_PAGE',20);

/**
 * The mutex-file used to store shared information between multiple, parallel running processes
 */
define('PC_CLI_MUTEX_FILE','cache/mutex');

/**
 * The version
 */
define('PC_VERSION','PHPCheck v1.0');
?>